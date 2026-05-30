<?php
/**
 * DomainManager — Tracking Domain Auto-Configuration
 *
 * Handles:
 *  - DNS A record verification (domain → server IP)
 *  - Apache / Nginx VirtualHost auto-creation (VPS/dedicated)
 *  - cPanel UAPI addon-domain creation (shared cPanel hosting)
 *  - Let's Encrypt SSL via certbot (when exec() available)
 *  - Status tracking in the tracking_domains table
 *
 * Server detection order:
 *  1. cPanel  — /usr/local/cpanel/cpanel exists
 *  2. Nginx   — /etc/nginx/sites-available exists
 *  3. Apache  — default
 */
class DomainManager {

    // ── Column bootstrap ──────────────────────────────────────────────────

    public static function ensureColumns(): void {
        static $done = false;
        if ($done) return;
        $done = true;
        $cols = [
            "ALTER TABLE `tracking_domains` ADD COLUMN `dns_status`    ENUM('pending','pointing','error')   NOT NULL DEFAULT 'pending'",
            "ALTER TABLE `tracking_domains` ADD COLUMN `server_status` ENUM('pending','configured','error') NOT NULL DEFAULT 'pending'",
            "ALTER TABLE `tracking_domains` ADD COLUMN `ssl_status`    ENUM('none','active','error')        NOT NULL DEFAULT 'none'",
            "ALTER TABLE `tracking_domains` ADD COLUMN `dns_ip`        VARCHAR(45) DEFAULT NULL",
            "ALTER TABLE `tracking_domains` ADD COLUMN `last_check_at` DATETIME    DEFAULT NULL",
            "ALTER TABLE `tracking_domains` ADD COLUMN `check_message` VARCHAR(500) DEFAULT NULL",
        ];
        foreach ($cols as $sql) {
            try { Database::query($sql); } catch (\Throwable $_e) {}
        }
    }

    // ── Server IP detection ───────────────────────────────────────────────

    public static function getServerIp(): string {
        // Try several methods to get the public IP of this server
        static $ip = null;
        if ($ip !== null) return $ip;

        // 1. From HTTP_SERVER_ADDR / SERVER_ADDR (most reliable inside PHP)
        $candidates = [
            $_SERVER['SERVER_ADDR']      ?? '',
            $_SERVER['HTTP_SERVER_ADDR'] ?? '',
        ];
        foreach ($candidates as $c) {
            if ($c && filter_var($c, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return ($ip = $c);
            }
        }

        // 2. Outbound IP check via external service (fast, cached)
        foreach (['https://api.ipify.org', 'https://ifconfig.me/ip', 'https://icanhazip.com'] as $svc) {
            try {
                $ch = curl_init($svc);
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 4, CURLOPT_SSL_VERIFYPEER => false]);
                $res = trim((string)curl_exec($ch));
                curl_close($ch);
                if (filter_var($res, FILTER_VALIDATE_IP)) {
                    return ($ip = $res);
                }
            } catch (\Throwable $_e) {}
        }

        // 3. Hostname resolution
        $host = gethostname();
        if ($host) {
            $resolved = gethostbyname($host);
            if ($resolved !== $host && filter_var($resolved, FILTER_VALIDATE_IP)) {
                return ($ip = $resolved);
            }
        }

        return ($ip = '');
    }

    // ── DNS A record lookup ───────────────────────────────────────────────

    /**
     * Returns the resolved A record IP for $domain, or empty string on failure.
     */
    public static function getDnsIp(string $domain): string {
        // Strip protocol/path just in case
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = explode('/', $domain)[0];

        // PHP native DNS lookup
        $records = @dns_get_record($domain, DNS_A);
        if (!empty($records)) {
            return $records[0]['ip'] ?? '';
        }

        // Fallback: gethostbyname
        $ip = @gethostbyname($domain);
        return ($ip !== $domain) ? $ip : '';
    }

    // ── Check if domain A record points to THIS server ───────────────────

    public static function isDnsPointing(string $domain): bool {
        $serverIp = self::getServerIp();
        if (!$serverIp) return false;
        $dnsIp = self::getDnsIp($domain);
        return $dnsIp === $serverIp;
    }

    // ── Full domain status check (DNS + HTTP reachability) ───────────────

    /**
     * Checks DNS + HTTP(S) for $domain, updates tracking_domains row, returns status array.
     */
    public static function checkDomain(string $domain): array {
        self::ensureColumns();

        $serverIp  = self::getServerIp();
        $dnsIp     = self::getDnsIp($domain);
        $dnsOk     = ($serverIp && $dnsIp === $serverIp);

        $dnsStatus     = $dnsOk ? 'pointing' : ($dnsIp ? 'error' : 'pending');
        $serverStatus  = 'pending';
        $sslStatus     = 'none';
        $checkMsg      = '';

        if ($dnsOk) {
            // DNS is pointing — check HTTP reachability
            $httpOk  = self::checkHttp('http://'  . $domain . '/');
            $httpsOk = self::checkHttp('https://' . $domain . '/');

            if ($httpsOk) {
                $serverStatus = 'configured';
                $sslStatus    = 'active';
            } elseif ($httpOk) {
                $serverStatus = 'configured';
                $sslStatus    = 'none';
            } else {
                $serverStatus = 'error';
                $checkMsg     = 'DNS OK but server not responding. VirtualHost may not be configured for this domain.';
            }
        } else {
            $checkMsg = $dnsIp
                ? "A record resolves to {$dnsIp} but server IP is {$serverIp}. Update your DNS."
                : "A record not found yet. DNS propagation may take up to 24h.";
        }

        // Persist result
        try {
            Database::query(
                "UPDATE tracking_domains SET dns_status=?, server_status=?, ssl_status=?, dns_ip=?, last_check_at=NOW(), check_message=? WHERE domain=?",
                [$dnsStatus, $serverStatus, $sslStatus, $dnsIp ?: null, $checkMsg ?: null, $domain]
            );
        } catch (\Throwable $_e) {}

        return [
            'domain'        => $domain,
            'server_ip'     => $serverIp,
            'dns_ip'        => $dnsIp,
            'dns_status'    => $dnsStatus,
            'server_status' => $serverStatus,
            'ssl_status'    => $sslStatus,
            'message'       => $checkMsg,
        ];
    }

    // ── HTTP reachability probe ───────────────────────────────────────────

    private static function checkHttp(string $url): bool {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT      => 'DomainChecker/1.0',
            CURLOPT_NOBODY         => false,
            CURLOPT_HEADER         => true,
        ]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        return !$err && $code > 0 && $code < 500;
    }

    // ── Detect server environment ─────────────────────────────────────────

    public static function detectEnvironment(): string {
        if (@file_exists('/usr/local/cpanel/cpanel'))   return 'cpanel';
        if (@file_exists('/etc/nginx/sites-available'))  return 'nginx';
        return 'apache';
    }

    public static function canExec(): bool {
        if (!function_exists('exec')) return false;
        $disabled = array_map('trim', explode(',', ini_get('disable_functions') ?? ''));
        return !in_array('exec', $disabled);
    }

    // ── Get tracker document root ─────────────────────────────────────────

    public static function getDocRoot(): string {
        return defined('BASE_PATH') ? BASE_PATH . '/public' : dirname(__DIR__) . '/public';
    }

    // ── Apache VirtualHost auto-configure ────────────────────────────────

    public static function configureApache(string $domain): array {
        if (!self::canExec()) {
            return ['ok' => false, 'message' => 'exec() is disabled — cannot auto-configure. See manual setup instructions below.'];
        }

        $docRoot    = self::getDocRoot();
        $confDir    = '/etc/apache2/sites-available';
        $enableDir  = '/etc/apache2/sites-enabled';
        $safeSlug   = preg_replace('/[^a-z0-9\-\.]/', '', $domain);
        $confFile   = "{$confDir}/{$safeSlug}.conf";

        if (!is_dir($confDir)) {
            // Try RedHat/CentOS path
            $confDir   = '/etc/httpd/conf.d';
            $enableDir = $confDir;
            $confFile  = "{$confDir}/{$safeSlug}.conf";
        }
        if (!is_dir($confDir)) {
            return ['ok' => false, 'message' => 'Apache config directory not found. Please configure manually.'];
        }

        $vhost = <<<VHOST
<VirtualHost *:80>
    ServerName {$domain}
    DocumentRoot {$docRoot}
    DirectoryIndex index.php

    <Directory {$docRoot}>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  /var/log/apache2/{$domain}-error.log
    CustomLog /var/log/apache2/{$domain}-access.log combined
</VirtualHost>
VHOST;

        if (@file_put_contents($confFile, $vhost) === false) {
            return ['ok' => false, 'message' => "Cannot write {$confFile}. Check file permissions."];
        }

        // Enable site (Debian/Ubuntu) or just reload (RHEL)
        if (is_dir('/etc/apache2/sites-enabled')) {
            exec("a2ensite {$safeSlug}.conf 2>&1", $out, $rc);
        }
        exec('systemctl reload apache2 2>&1 || service apache2 reload 2>&1 || apachectl graceful 2>&1', $out2, $rc2);

        try {
            Database::query(
                "UPDATE tracking_domains SET server_status='configured', check_message='VirtualHost created automatically' WHERE domain=?",
                [$domain]
            );
        } catch (\Throwable $_e) {}

        return ['ok' => true, 'message' => "Apache VirtualHost created for {$domain}. Reload complete."];
    }

    // ── Nginx server block auto-configure ─────────────────────────────────

    public static function configureNginx(string $domain): array {
        if (!self::canExec()) {
            return ['ok' => false, 'message' => 'exec() is disabled — cannot auto-configure.'];
        }

        $docRoot  = self::getDocRoot();
        $safeSlug = preg_replace('/[^a-z0-9\-\.]/', '', $domain);
        $confFile = "/etc/nginx/sites-available/{$safeSlug}";

        $block = <<<NGINX
server {
    listen 80;
    server_name {$domain};
    root {$docRoot};
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
}
NGINX;

        if (@file_put_contents($confFile, $block) === false) {
            return ['ok' => false, 'message' => "Cannot write {$confFile}. Check permissions."];
        }

        exec("ln -sf {$confFile} /etc/nginx/sites-enabled/{$safeSlug} 2>&1");
        exec('nginx -t 2>&1 && systemctl reload nginx 2>&1', $out, $rc);

        try {
            Database::query(
                "UPDATE tracking_domains SET server_status='configured', check_message='Nginx server block created automatically' WHERE domain=?",
                [$domain]
            );
        } catch (\Throwable $_e) {}

        return ['ok' => $rc === 0, 'message' => $rc === 0
            ? "Nginx server block created for {$domain}."
            : "Nginx config written but reload failed. Check nginx -t manually."];
    }

    // ── cPanel UAPI addon domain ───────────────────────────────────────────

    /**
     * Uses cPanel UAPI (localhost:2083 HTTPS) to add the domain as an addon domain.
     * Requires cPanel credentials stored in config: cpanel.username + cpanel.password
     */
    public static function configureCpanel(string $domain): array {
        $cfg      = Config::get('config') ?? [];
        $cpUser   = $cfg['cpanel.username'] ?? '';
        $cpPass   = $cfg['cpanel.password'] ?? '';
        $cpHost   = $cfg['cpanel.host']     ?? 'localhost';
        $cpPort   = (int)($cfg['cpanel.port'] ?? 2083);

        if (!$cpUser || !$cpPass) {
            return ['ok' => false, 'message' => 'cPanel credentials not configured. Add cpanel.username and cpanel.password in General Settings or configure the VirtualHost manually in cPanel → Addon Domains.'];
        }

        $docRoot   = self::getDocRoot();
        $subdomain = str_replace('.', '_', $domain);

        // cPanel UAPI endpoint: POST /execute/AddonDomain/add
        $url  = "https://{$cpHost}:{$cpPort}/execute/AddonDomain/add";
        $body = http_build_query([
            'newdomain'       => $domain,
            'subdomain'       => $subdomain,
            'dir'             => $docRoot,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERPWD        => "{$cpUser}:{$cpPass}",
        ]);
        $res  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err || $code !== 200) {
            return ['ok' => false, 'message' => "cPanel UAPI call failed (HTTP {$code}): {$err}. Add the domain manually in cPanel → Addon Domains."];
        }

        $data = json_decode($res, true);
        if (($data['status'] ?? 0) == 1) {
            try {
                Database::query(
                    "UPDATE tracking_domains SET server_status='configured', check_message='Added via cPanel UAPI' WHERE domain=?",
                    [$domain]
                );
            } catch (\Throwable $_e) {}
            return ['ok' => true, 'message' => "Domain {$domain} added to cPanel successfully."];
        }

        $cpMsg = $data['errors'][0] ?? $data['message'] ?? 'Unknown cPanel error';
        return ['ok' => false, 'message' => "cPanel error: {$cpMsg}"];
    }

    // ── Let's Encrypt SSL ────────────────────────────────────────────────

    public static function requestSsl(string $domain): array {
        if (!self::canExec()) {
            return ['ok' => false, 'message' => 'exec() disabled — cannot auto-issue SSL. Use your cPanel SSL manager or certbot manually.'];
        }

        $cfg   = Config::get('config') ?? [];
        $email = $cfg['admin.email'] ?? $cfg['smtp.from_email'] ?? 'admin@' . $domain;

        exec("which certbot 2>&1", $which, $rc);
        if ($rc !== 0) {
            return ['ok' => false, 'message' => 'certbot not found on this server. Install it with: apt install certbot python3-certbot-apache'];
        }

        exec("certbot --apache -d {$domain} --non-interactive --agree-tos -m {$email} 2>&1", $out, $rc);
        $output = implode("\n", $out);

        if ($rc === 0) {
            try {
                Database::query("UPDATE tracking_domains SET ssl_status='active' WHERE domain=?", [$domain]);
            } catch (\Throwable $_e) {}
            return ['ok' => true, 'message' => "SSL certificate issued for {$domain}."];
        }

        return ['ok' => false, 'message' => "certbot failed: " . substr($output, 0, 300)];
    }

    // ── Auto-configure: pick best method ─────────────────────────────────

    /**
     * Tries the most appropriate method to configure the VirtualHost
     * based on the server environment. Returns status array.
     */
    public static function autoConfigure(string $domain): array {
        self::ensureColumns();

        $env = self::detectEnvironment();

        if ($env === 'cpanel') {
            $result = self::configureCpanel($domain);
        } elseif ($env === 'nginx') {
            $result = self::configureNginx($domain);
        } else {
            $result = self::configureApache($domain);
        }

        return array_merge($result, ['environment' => $env]);
    }

    // ── Generate manual setup instructions ────────────────────────────────

    public static function getSetupInstructions(string $domain, string $env = ''): string {
        if (!$env) $env = self::detectEnvironment();
        $serverIp = self::getServerIp();
        $docRoot  = self::getDocRoot();

        if ($env === 'cpanel') {
            return "1. Go to <strong>cPanel → Addon Domains</strong><br>"
                 . "2. Enter <code>{$domain}</code> as the domain name<br>"
                 . "3. Set the document root to: <code>{$docRoot}</code><br>"
                 . "4. Click <strong>Add Domain</strong> — then SSL is auto-managed by cPanel<br>"
                 . "5. Return here and click <strong>Check DNS</strong> to verify";
        }

        if ($env === 'nginx') {
            return "Create <code>/etc/nginx/sites-available/{$domain}</code> with:<br>"
                 . "<pre>server {\n  listen 80;\n  server_name {$domain};\n  root {$docRoot};\n  index index.php;\n"
                 . "  location / { try_files \$uri \$uri/ /index.php?\$query_string; }\n"
                 . "  location ~ \\.php\$ { fastcgi_pass unix:/run/php/php-fpm.sock; include fastcgi_params; }\n}</pre>"
                 . "Then: <code>ln -s /etc/nginx/sites-available/{$domain} /etc/nginx/sites-enabled/ && nginx -s reload</code>";
        }

        return "Create <code>/etc/apache2/sites-available/{$domain}.conf</code> with:<br>"
             . "<pre>&lt;VirtualHost *:80&gt;\n  ServerName {$domain}\n  DocumentRoot {$docRoot}\n"
             . "  &lt;Directory {$docRoot}&gt;\n    AllowOverride All\n    Require all granted\n  &lt;/Directory&gt;\n&lt;/VirtualHost&gt;</pre>"
             . "Then: <code>a2ensite {$domain}.conf && systemctl reload apache2</code><br>"
             . "SSL: <code>certbot --apache -d {$domain}</code>";
    }
}
