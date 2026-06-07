<?php
/**
 * Mailer — lightweight SMTP mailer using native PHP sockets.
 * Falls back to mail() if SMTP host is not configured.
 */
class Mailer
{
    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Send a transactional email based on an event type.
     * Loads the template, renders placeholders, then sends.
     *
     * @param string $toEmail
     * @param string $toName
     * @param string $eventType  e.g. 'affiliate_created', 'affiliate_approved' …
     * @param array  $vars       Placeholder values: ['name'=>'John', 'status'=>'active', …]
     */
    public static function sendEvent(string $toEmail, string $toName, string $eventType, array $vars = []): bool
    {
        $tpl = Database::fetchOne(
            "SELECT subject, html_body, is_active FROM email_templates WHERE event_type=? LIMIT 1",
            [$eventType]
        );
        if (!$tpl || !$tpl['is_active']) {
            return false; // template disabled or missing
        }

        $subject = self::replacePlaceholders($tpl['subject'],  $vars);
        $body    = self::replacePlaceholders($tpl['html_body'], $vars);
        $body    = self::applyTheme($body, $subject);

        return self::send($toEmail, $toName, $subject, $body, $eventType);
    }

    /**
     * Send a raw email (used for promotional blasts).
     */
    public static function sendRaw(string $toEmail, string $toName, string $subject, string $htmlBody, string $eventType = 'blast'): bool
    {
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
     * Wrap raw HTML in a beautiful branded email template with the site logo.
     */
    public static function applyTheme(string $body, string $title = ''): string
    {
        // Don't double-wrap if it already looks like a full layout
        if (stripos($body, 'max-width:620px') !== false || stripos($body, 'max-width:600px') !== false) {
            return $body;
        }

        $cfg     = Config::get('config') ?? [];
        $appName = $cfg['app']['name'] ?? 'Affiliate Network';
        $appUrl  = rtrim($cfg['app']['url'] ?? '', '/');
        $appLogo = $cfg['app']['logo'] ?? '';
        
        $appEsc = htmlspecialchars($appName, ENT_QUOTES);
        
        $headerBranding = '';
        if (!empty($appLogo)) {
            $logoUrl = filter_var($appLogo, FILTER_VALIDATE_URL) ? $appLogo : $appUrl . '/' . ltrim($appLogo, '/');
            $logoEsc = htmlspecialchars($logoUrl, ENT_QUOTES);
            $headerBranding = "<img src=\"{$logoEsc}\" alt=\"{$appEsc}\" style=\"max-height:40px;max-width:200px;object-fit:contain\">";
        } else {
            $headerBranding = "<div style=\"font-size:20px;font-weight:800;color:#fff;letter-spacing:.02em\">{$appEsc}</div>";
        }

        return <<<HTML
<div style="background:#F8FAFC;padding:40px 20px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
  <div style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(148,163,184,0.15);">
    <div style="padding:32px;text-align:center;background:linear-gradient(135deg, rgba(167,139,250,0.15) 0%, rgba(124,58,237,0.15) 100%);border-bottom:1px solid rgba(124,58,237,0.2);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);">
      {$headerBranding}
      <div style="font-size:14px;color:#4F46E5;margin-top:8px;font-weight:600;letter-spacing:0.5px">{$title}</div>
    </div>
    <div style="padding:32px;color:#334155;font-size:15px;line-height:1.7;">
      {$body}
    </div>
    <div style="padding:20px 32px;background:#F8FAFC;text-align:center;border-top:1px solid #E2E8F0;">
      <p style="color:#64748B;font-size:12px;margin:0">{$appEsc} &bull; This is an automated message.</p>
    </div>
  </div>
</div>
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
        $smtpHost = $cfg['smtp']['host'] ?? '';
        $fromEmail = $cfg['smtp']['from_email'] ?? ($cfg['app']['name'] ?? 'System') . '@noreply.local';
        $fromName  = $cfg['smtp']['from_name']  ?? ($cfg['app']['name'] ?? 'System');

        $status = 'failed';
        $errMsg = '';

        try {
            if ($smtpHost) {
                self::sendSmtp($toEmail, $toName, $fromEmail, $fromName, $subject, $htmlBody, $cfg['smtp'] ?? []);
            } else {
                // Fallback: PHP mail()
                $headers  = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: " . self::encodeHeader($fromName) . " <$fromEmail>\r\n";
                $headers .= "Reply-To: $fromEmail\r\n";
                if (!mail($toEmail, $subject, $htmlBody, $headers)) {
                    throw new \RuntimeException('mail() returned false');
                }
            }
            $status = 'sent';
        } catch (\Throwable $e) {
            $errMsg = $e->getMessage();
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
