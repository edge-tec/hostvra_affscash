<?php
/**
 * Mailer — lightweight SMTP mailer using native PHP sockets.
 * Falls back to mail() if SMTP host is not configured.
 */
class Mailer
{
    // ── Public API ───────────────────────────────────────────────────────────

    public static ?string $lastError = null;

    /**
     * Send a transactional email based on an event type.
     * Loads the template, renders placeholders, applies master theme, then sends.
     *
     * @param string $toEmail
     * @param string $toName
     * @param string $eventType  e.g. 'affiliate_created', 'affiliate_approved' …
     * @param array  $vars       Placeholder values: ['name'=>'John', 'status'=>'active', …]
     */
    public static function sendEvent(string $toEmail, string $toName, string $eventType, array $vars = []): bool
    {
        self::$lastError = null;
        $tpl = Database::fetchOne(
            "SELECT subject, html_body, is_active FROM email_templates WHERE event_type=? LIMIT 1",
            [$eventType]
        );

        if (!$tpl) {
            // Auto-ensure reward templates if missing
            if (class_exists('RewardsService')) {
                try { RewardsService::ensureSchema(); } catch (\Throwable $_) {}
            }
            $tpl = Database::fetchOne(
                "SELECT subject, html_body, is_active FROM email_templates WHERE event_type=? LIMIT 1",
                [$eventType]
            );
        }

        if (!$tpl || !$tpl['is_active']) {
            self::$lastError = "Email template for event type '{$eventType}' is disabled or missing in database.";
            return false;
        }

        $vars    = self::enrichVars($vars);
        $subject = self::replacePlaceholders($tpl['subject'],   $vars);
        $body    = self::replacePlaceholders($tpl['html_body'], $vars);
        $body    = self::applyTheme($body, $subject, $vars);

        return self::send($toEmail, $toName, $subject, $body, $eventType);
    }

    /**
     * Send a raw email (used for promotional blasts & custom notifications).
     */
    public static function sendRaw(string $toEmail, string $toName, string $subject, string $htmlBody, string $eventType = 'blast', array $vars = []): bool
    {
        $vars     = self::enrichVars($vars);
        $htmlBody = self::replacePlaceholders($htmlBody, $vars);
        $htmlBody = self::applyTheme($htmlBody, $subject, $vars);
        return self::send($toEmail, $toName, $subject, $htmlBody, $eventType);
    }

    /**
     * Test SMTP connection and authentication.
     * Throws an exception on failure or returns true on success.
     *
     * @param array $smtpCfg Optional SMTP config. If empty, uses the global config.
     */
    public static function testConnection(array $smtpCfg = []): bool
    {
        if (empty($smtpCfg)) {
            $cfg = Config::get('config') ?? [];
            $smtpCfg = $cfg['smtp'] ?? [];
        }

        $host = $smtpCfg['host'] ?? '';
        if (!$host) {
            throw new \RuntimeException("No SMTP host configured.");
        }
        $port       = (int)($smtpCfg['port'] ?? 587);
        $username   = $smtpCfg['username'] ?? '';
        $password   = $smtpCfg['password'] ?? '';
        $encryption = strtolower($smtpCfg['encryption'] ?? 'tls');

        $timeout = 10;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ]);
        $connStr = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

        $sock = @stream_socket_client($connStr, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$sock) {
            throw new \RuntimeException("SMTP connect failed: $errstr ($errno)");
        }
        stream_set_timeout($sock, $timeout);

        $read = function() use ($sock): string {
            $line = '';
            while (!feof($sock)) {
                $l = fgets($sock, 1024);
                $line .= $l;
                if (isset($l[3]) && $l[3] === ' ') break;
            }
            return $line;
        };
        $cmd = function(string $c) use ($sock, $read): string {
            fwrite($sock, $c . "\r\n");
            return $read();
        };

        $read(); // banner
        $cmd("EHLO " . gethostname());

        if ($encryption === 'tls') {
            $cmd("STARTTLS");
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $cmd("EHLO " . gethostname()); // re-hello after TLS
        }

        if ($username) {
            $resp = $cmd("AUTH LOGIN");
            if (strpos($resp, '334') === false) {
                throw new \RuntimeException("AUTH LOGIN rejected: $resp");
            }
            $cmd(base64_encode($username));
            $resp = $cmd(base64_encode($password));
            if (strpos($resp, '235') === false) {
                throw new \RuntimeException("SMTP AUTH failed: $resp");
            }
        }

        $cmd("QUIT");
        fclose($sock);

        return true;
    }

    /**
     * Send a test message to verify email delivery.
     *
     * @param string $toEmail
     * @param string $toName
     */
    public static function testMessage(string $toEmail, string $toName = 'Test User'): bool
    {
        $subject = 'SMTP Connection Test';
        $htmlBody = '<h2>Hello!</h2><p>This is a test email to verify that your SMTP connection is working correctly.</p><p>If you received this, your email configuration is successful.</p>';
        return self::sendRaw($toEmail, $toName, $subject, $htmlBody, 'test_message');
    }

    /**
     * Send an event email with a PDF file attachment.
     *
     * @param string $attachPath  Absolute filesystem path to the PDF
     * @param string $attachName  Filename shown to the recipient
     */
    public static function sendEventWithPdf(
        string $toEmail,
        string $toName,
        string $eventType,
        array  $vars,
        string $attachPath,
        string $attachName
    ): bool {
        $tpl = Database::fetchOne(
            "SELECT subject, html_body, is_active FROM email_templates WHERE event_type=? LIMIT 1",
            [$eventType]
        );
        if (!$tpl || !$tpl['is_active']) {
            // Send basic HTML if template missing
            $subject  = 'Your Invoice is Ready';
            $htmlBody = '<p>Please find your invoice attached.</p>';
        } else {
            $subject  = self::replacePlaceholders($tpl['subject'],   $vars);
            $htmlBody = self::replacePlaceholders($tpl['html_body'], $vars);
        }
        $htmlBody = self::applyTheme($htmlBody, $subject);
        return self::sendWithPdf($toEmail, $toName, $subject, $htmlBody, $eventType, $attachPath, $attachName);
    }

    private static function sendWithPdf(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $eventType,
        string $attachPath,
        string $attachName
    ): bool {
        $cfg        = Config::get('config') ?? [];
        $smtpHost   = $cfg['smtp']['host'] ?? '';
        $fromEmail  = $cfg['smtp']['from_email'] ?? ($cfg['app']['name'] ?? 'System') . '@noreply.local';
        $fromName   = $cfg['smtp']['from_name']  ?? ($cfg['app']['name'] ?? 'System');

        $status = 'failed';
        $errMsg = '';

        try {
            if ($smtpHost) {
                self::sendSmtpWithPdf($toEmail, $toName, $fromEmail, $fromName, $subject, $htmlBody, $cfg['smtp'] ?? [], $attachPath, $attachName);
            } else {
                // Fallback: PHP mail() with attachment
                $bound   = md5(uniqid('', true));
                $pdfData = base64_encode(file_get_contents($attachPath));
                $headers  = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: multipart/mixed; boundary=\"{$bound}\"\r\n";
                $headers .= "From: " . self::encodeHeader($fromName) . " <{$fromEmail}>\r\n";
                $body  = "--{$bound}\r\n";
                $body .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
                $body .= base64_encode($htmlBody) . "\r\n";
                $body .= "--{$bound}\r\n";
                $body .= "Content-Type: application/pdf\r\nContent-Transfer-Encoding: base64\r\n";
                $body .= "Content-Disposition: attachment; filename=\"{$attachName}\"\r\n\r\n";
                $body .= $pdfData . "\r\n";
                $body .= "--{$bound}--";
                if (!mail($toEmail, $subject, $body, $headers)) {
                    throw new \RuntimeException('mail() returned false');
                }
            }
            $status = 'sent';
        } catch (\Throwable $e) {
            $errMsg = $e->getMessage();
        }

        try {
            Database::insert('email_logs', [
                'to_email'   => $toEmail,
                'subject'    => $subject,
                'event_type' => $eventType,
                'status'     => $status,
                'error'      => $errMsg ?: null,
            ]);
        } catch (\Throwable $e) {}

        return $status === 'sent';
    }

    private static function sendSmtpWithPdf(
        string $toEmail, string $toName,
        string $fromEmail, string $fromName,
        string $subject, string $htmlBody,
        array  $smtpCfg,
        string $attachPath, string $attachName
    ): void {
        $host       = $smtpCfg['host'];
        $port       = (int)($smtpCfg['port'] ?? 587);
        $username   = $smtpCfg['username'] ?? '';
        $password   = $smtpCfg['password'] ?? '';
        $encryption = strtolower($smtpCfg['encryption'] ?? 'tls');
        $timeout    = 15;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ]);
        $connStr = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

        $sock = @stream_socket_client($connStr, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$sock) throw new \RuntimeException("SMTP connect failed: $errstr ($errno)");
        stream_set_timeout($sock, $timeout);

        $read = function () use ($sock): string {
            $line = '';
            while (!feof($sock)) {
                $l = fgets($sock, 1024);
                $line .= $l;
                if (isset($l[3]) && $l[3] === ' ') break;
            }
            return $line;
        };
        $cmd = function (string $c) use ($sock, $read): string {
            fwrite($sock, $c . "\r\n");
            return $read();
        };

        $read(); // banner
        $cmd("EHLO " . gethostname());
        if ($encryption === 'tls') {
            $cmd("STARTTLS");
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $cmd("EHLO " . gethostname());
        }
        if ($username) {
            $resp = $cmd("AUTH LOGIN");
            if (strpos($resp, '334') === false) throw new \RuntimeException("AUTH rejected: $resp");
            $cmd(base64_encode($username));
            $resp = $cmd(base64_encode($password));
            if (strpos($resp, '235') === false) throw new \RuntimeException("SMTP AUTH failed: $resp");
        }

        $cmd("MAIL FROM:<{$fromEmail}>");
        $cmd("RCPT TO:<{$toEmail}>");
        $cmd("DATA");

        $mixBound = md5(uniqid('mx', true));
        $altBound = md5(uniqid('al', true));
        $date     = date('r');
        $msgId    = '<' . uniqid('', true) . '@' . ($smtpCfg['host'] ?? 'local') . '>';
        $pdfB64   = chunk_split(base64_encode(file_get_contents($attachPath)));
        $safeAtt  = preg_replace('/[^A-Za-z0-9._\-]/', '_', $attachName);

        $headers  = "Date: $date\r\nMessage-ID: $msgId\r\n";
        $headers .= "From: " . self::encodeHeader($fromName) . " <{$fromEmail}>\r\n";
        $headers .= "To: "   . self::encodeHeader($toName)   . " <{$toEmail}>\r\n";
        $headers .= "Subject: " . self::encodeHeader($subject) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$mixBound}\"\r\n";
        $headers .= "X-Mailer: AffiliateTracker\r\n";

        $body  = "--{$mixBound}\r\n";
        $body .= "Content-Type: multipart/alternative; boundary=\"{$altBound}\"\r\n\r\n";
        $body .= "--{$altBound}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode(strip_tags(str_replace(['<br>', '<br/>', '</p>', '</div>'], "\n", $htmlBody)))) . "\r\n";
        $body .= "--{$altBound}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body .= "--{$altBound}--\r\n";
        $body .= "--{$mixBound}\r\n";
        $body .= "Content-Type: application/pdf\r\nContent-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"{$safeAtt}\"\r\n\r\n";
        $body .= $pdfB64 . "\r\n";
        $body .= "--{$mixBound}--\r\n";

        $resp = $cmd($headers . "\r\n" . $body . "\r\n.");
        if (strpos($resp, '250') === false) throw new \RuntimeException("Message rejected: $resp");
        $cmd("QUIT");
        fclose($sock);
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    /**
     * Render a template string with these features:
     *   {{var}}                                — simple substitution
     *   {{#if_VAR}}...{{/if_VAR}}              — render block only when VAR (or is_VAR) is truthy
     *   {{#unless_VAR}}...{{/unless_VAR}}      — render block only when VAR (or is_VAR) is falsy
     * Any unresolved {{tag}} left after rendering is stripped so it never
     * appears as a literal to the recipient.
     *
     * "Truthy" means: not '', not null, not false, not '0', not 'false'.
     */
    /**
     * Dynamically calculate/format the RevShare percentage from offer or context data.
     */
    public static function calculateRevSharePercentage($data = null): string
    {
        if (is_numeric($data) && (float)$data > 0) {
            return number_format((float)$data, 2) . '%';
        }

        if (is_array($data)) {
            if (!empty($data['revshare_percent'])) {
                $val = (float)str_replace('%', '', (string)$data['revshare_percent']);
                if ($val > 0) return number_format($val, 2) . '%';
            }
            if (isset($data['revshare']) && is_numeric($data['revshare']) && (float)$data['revshare'] > 0) {
                return number_format((float)$data['revshare'], 2) . '%';
            }

            $payoutType = strtoupper((string)($data['payout_type'] ?? ''));
            $payoutAmt  = (float)($data['payout_amount'] ?? ($data['payout'] ?? 0));
            $revenueAmt = (float)($data['revenue_amount'] ?? ($data['revenue'] ?? 0));

            if ($payoutType === 'REVSHARE' && $payoutAmt > 0) {
                return number_format($payoutAmt, 2) . '%';
            }

            if ($revenueAmt > 0 && $payoutAmt > 0) {
                $ratio = ($payoutAmt / $revenueAmt) * 100;
                return number_format($ratio, 2) . '%';
            }
        }

        // Global default RevShare percentage from configuration or 25.00%
        $default = Config::get('config', 'app.default_revshare') ?? '25.00';
        return number_format((float)$default, 2) . '%';
    }

    /**
     * Enrich email variables with site metadata, logo, and RevShare info.
     */
    public static function enrichVars(array $vars): array
    {
        $cfg    = Config::get('config') ?? [];
        $app    = $cfg['app']['name'] ?? 'AffsCash';
        $appUrl = rtrim((string)($cfg['app']['url'] ?? ''), '/');

        if (empty($vars['site_name'])) $vars['site_name'] = $app;
        if (empty($vars['app_url']))   $vars['app_url']   = $appUrl;

        // Auto-calculate RevShare
        $revsharePct = self::calculateRevSharePercentage($vars);
        if (empty($vars['revshare_percent'])) {
            $vars['revshare_percent'] = $revsharePct;
        }
        if (empty($vars['revshare'])) {
            $vars['revshare'] = 'RevShare: ' . $revsharePct;
        }

        // Ensure commission string incorporates RevShare if not already present
        if (!empty($vars['commission']) && is_string($vars['commission']) && stripos($vars['commission'], 'revshare') === false) {
            $vars['commission'] .= ' &middot; RevShare: ' . $revsharePct;
        }

        // Logo HTML placeholder
        if (empty($vars['logo_html'])) {
            $logoSetting = $cfg['app']['logo_url'] ?? ($cfg['app']['logo'] ?? '');
            if ($logoSetting) {
                $logoUrl = (str_starts_with($logoSetting, 'http://') || str_starts_with($logoSetting, 'https://'))
                    ? $logoSetting
                    : $appUrl . '/' . ltrim($logoSetting, '/');
                $vars['logo_html'] = '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($app, ENT_QUOTES, 'UTF-8') . '" style="max-height:48px;max-width:220px;height:auto;width:auto;display:inline-block;vertical-align:middle;border:0;outline:none;" border="0">';
            } else {
                $vars['logo_html'] = '<div style="font-size:22px;font-weight:800;color:#ffffff;letter-spacing:.02em;">' . htmlspecialchars($app, ENT_QUOTES, 'UTF-8') . '</div>';
            }
        }

        return $vars;
    }

    /**
     * Sanitize inner body HTML by extracting body content and removing redundant headers/wrappers.
     */
    private static function sanitizeInnerBody(string $html): string
    {
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            $html = $matches[1];
        }
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
        $html = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $html);
        $html = preg_replace('/\{\{logo_html\}\}/i', '', $html);

        return trim($html);
    }

    /**
     * Master centralized email template wrapper containing Header Logo, Responsive Body, and Footer.
     */
    public static function applyTheme(string $body, string $title = '', array $vars = []): string
    {
        $cfg     = Config::get('config') ?? [];
        $app     = $cfg['app']['name'] ?? 'AffsCash';
        $appUrl  = rtrim((string)($cfg['app']['url'] ?? ''), '/');

        // Resolve logo URL
        $logoSetting = $cfg['app']['logo_url'] ?? ($cfg['app']['logo'] ?? '');
        if ($logoSetting) {
            $logoUrl = (str_starts_with($logoSetting, 'http://') || str_starts_with($logoSetting, 'https://'))
                ? $logoSetting
                : $appUrl . '/' . ltrim($logoSetting, '/');
        } else {
            $logoUrl = $appUrl ? $appUrl . '/assets/img/logo.png' : '';
        }

        $appEsc  = htmlspecialchars($app, ENT_QUOTES, 'UTF-8');
        $logoEsc = htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8');

        if ($logoUrl) {
            $logoHtml = '<img src="' . $logoEsc . '" alt="' . $appEsc . '" style="max-height:48px;max-width:220px;width:auto;height:auto;display:inline-block;vertical-align:middle;border:0;outline:none;text-decoration:none;" border="0">';
        } else {
            $logoHtml = '<div style="font-size:22px;font-weight:800;color:#ffffff;letter-spacing:.02em;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">' . $appEsc . '</div>';
        }

        $titleHtml = '';
        if ($title) {
            $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $titleHtml = '<div style="font-size:12px;color:rgba(255,255,255,0.9);margin-top:6px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;">' . $titleEsc . '</div>';
        }

        $cleanBody = self::sanitizeInnerBody($body);
        $dateYear  = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{$appEsc}</title>
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        @media screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .content-cell { padding: 20px 16px !important; }
            .header-cell { padding: 24px 16px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#F8FAFC;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0F172A;">
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#F8FAFC;padding:24px 12px;">
    <tr>
        <td align="center">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width:620px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #E2E8F0;box-shadow:0 4px 12px rgba(15,23,42,0.06);">
                <!-- HEADER / BRANDING -->
                <tr>
                    <td align="center" class="header-cell" style="background:linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%);padding:28px 24px;text-align:center;">
                        {$logoHtml}
                        {$titleHtml}
                    </td>
                </tr>
                <!-- CONTENT AREA -->
                <tr>
                    <td class="content-cell" style="padding:32px 28px;color:#334155;font-size:14.5px;line-height:1.65;">
                        {$cleanBody}
                    </td>
                </tr>
                <!-- FOOTER -->
                <tr>
                    <td align="center" style="padding:20px 24px;background-color:#F8FAFC;border-top:1px solid #E2E8F0;text-align:center;font-size:12px;color:#64748B;line-height:1.5;">
                        <p style="margin:0 0 4px 0;font-weight:600;color:#475569;">{$appEsc}</p>
                        <p style="margin:0;font-size:11px;color:#94A3B8;">&copy; {$dateYear} {$appEsc}. All rights reserved. &bull; Automated System Notification</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
HTML;
    }

    private static function replacePlaceholders(string $text, array $vars): string
    {
        $isTruthy = function ($key) use ($vars): bool {
            // Look up VAR first, then is_VAR — lets template authors write
            // {{#if_hot}} for a variable called is_hot without thinking.
            $candidates = [$key, 'is_' . $key];
            foreach ($candidates as $k) {
                if (!array_key_exists($k, $vars)) continue;
                $v = $vars[$k];
                if ($v === null || $v === false || $v === '' || $v === '0' || $v === 'false') return false;
                return true;
            }
            return false;
        };

        // 1) Conditional blocks. `s` flag = `.` matches newlines.
        $text = preg_replace_callback(
            '/\{\{#if_([a-zA-Z0-9_]+)\}\}(.*?)\{\{\/if_\1\}\}/s',
            function ($m) use ($isTruthy) {
                return $isTruthy($m[1]) ? $m[2] : '';
            },
            $text
        );
        $text = preg_replace_callback(
            '/\{\{#unless_([a-zA-Z0-9_]+)\}\}(.*?)\{\{\/unless_\1\}\}/s',
            function ($m) use ($isTruthy) {
                return $isTruthy($m[1]) ? '' : $m[2];
            },
            $text
        );

        // 2) Simple variable substitution.
        foreach ($vars as $key => $value) {
            $text = str_replace('{{' . $key . '}}', (string)$value, $text);
        }

        // 3) Strip any leftover unresolved tags so they don't show up as
        //    literal "{{whatever}}" text in the delivered email.
        $text = preg_replace('/\{\{\s*\/?[#a-zA-Z0-9_.\-\s]+\s*\}\}/', '', $text);

        return $text;
    }

    private static function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $eventType): bool
    {
        $cfg = Config::get('config') ?? [];
        $smtpHost  = $cfg['smtp']['host'] ?? $cfg['email']['host'] ?? '';
        $fromEmail = $cfg['smtp']['from_email'] ?? $cfg['email']['from_address'] ?? (($cfg['app']['name'] ?? 'System') . '@noreply.local');
        $fromName  = $cfg['smtp']['from_name']  ?? $cfg['email']['from_name'] ?? ($cfg['app']['name'] ?? 'System');

        $status = 'failed';
        $errMsg = '';

        try {
            if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid recipient email address: '{$toEmail}'");
            }

            if ($smtpHost) {
                self::sendSmtp($toEmail, $toName, $fromEmail, $fromName, $subject, $htmlBody, $cfg['smtp'] ?? []);
            } else {
                // Fallback: PHP mail()
                $headers  = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: " . self::encodeHeader($fromName) . " <$fromEmail>\r\n";
                $headers .= "Reply-To: $fromEmail\r\n";
                if (!@mail($toEmail, $subject, $htmlBody, $headers)) {
                    throw new \RuntimeException("SMTP host is not configured in Admin Settings -> Email/SMTP, and server mail() function returned false.");
                }
            }
            $status = 'sent';
        } catch (\Throwable $e) {
            $errMsg = $e->getMessage();
            self::$lastError = $errMsg;
        }

        // Log attempt
        try {
            Database::insert('email_logs', [
                'to_email'   => $toEmail,
                'subject'    => $subject,
                'event_type' => $eventType,
                'status'     => $status,
                'error'      => $errMsg ?: null,
            ]);
        } catch (\Throwable $e) {
            // Silently ignore log failures
        }

        return $status === 'sent';
    }

    /**
     * Minimal SMTP client over TCP (supports STARTTLS via stream_socket_enable_crypto).
     */
    private static function sendSmtp(
        string $toEmail, string $toName,
        string $fromEmail, string $fromName,
        string $subject, string $htmlBody,
        array  $smtpCfg
    ): void {
        $host       = $smtpCfg['host'];
        $port       = (int)($smtpCfg['port'] ?? 587);
        $username   = $smtpCfg['username'] ?? '';
        $password   = $smtpCfg['password'] ?? '';
        $encryption = strtolower($smtpCfg['encryption'] ?? 'tls'); // tls | ssl | none

        $timeout = 15;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ]);
        $connStr = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

        $sock = @stream_socket_client($connStr, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$sock) {
            throw new \RuntimeException("SMTP connect failed: $errstr ($errno)");
        }
        stream_set_timeout($sock, $timeout);

        $read = function() use ($sock): string {
            $line = '';
            while (!feof($sock)) {
                $l = fgets($sock, 1024);
                $line .= $l;
                if (isset($l[3]) && $l[3] === ' ') break;
            }
            return $line;
        };
        $cmd = function(string $c) use ($sock, $read): string {
            fwrite($sock, $c . "\r\n");
            return $read();
        };

        $read(); // banner

        $ehlo = $cmd("EHLO " . gethostname());

        if ($encryption === 'tls') {
            $cmd("STARTTLS");
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $cmd("EHLO " . gethostname()); // re-hello after TLS
        }

        if ($username) {
            $resp = $cmd("AUTH LOGIN");
            if (strpos($resp, '334') === false) {
                throw new \RuntimeException("AUTH LOGIN rejected: $resp");
            }
            $cmd(base64_encode($username));
            $resp = $cmd(base64_encode($password));
            if (strpos($resp, '235') === false) {
                throw new \RuntimeException("SMTP AUTH failed: $resp");
            }
        }

        $cmd("MAIL FROM:<$fromEmail>");
        $cmd("RCPT TO:<$toEmail>");
        $cmd("DATA");

        $boundary = md5(uniqid('', true));
        $date     = date('r');
        $msgId    = '<' . uniqid('', true) . '@' . ($smtpCfg['host'] ?? 'local') . '>';
        $encoded  = base64_encode($htmlBody);

        $headers  = "Date: $date\r\n";
        $headers .= "Message-ID: $msgId\r\n";
        $headers .= "From: " . self::encodeHeader($fromName) . " <$fromEmail>\r\n";
        $headers .= "To: " . self::encodeHeader($toName) . " <$toEmail>\r\n";
        $headers .= "Subject: " . self::encodeHeader($subject) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $headers .= "X-Mailer: AffiliateTracker\r\n";

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode(strip_tags(str_replace(['<br>','<br/>','<br />','</p>','</div>'], "\n", $htmlBody)))) . "\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body .= "--$boundary--\r\n";

        $resp = $cmd($headers . "\r\n" . $body . "\r\n.");
        if (strpos($resp, '250') === false) {
            throw new \RuntimeException("Message rejected: $resp");
        }
        $cmd("QUIT");
        fclose($sock);
    }

    private static function encodeHeader(string $str): string
    {
        if (preg_match('/[^\x20-\x7E]/', $str)) {
            return '=?UTF-8?B?' . base64_encode($str) . '?=';
        }
        return $str;
    }
}
