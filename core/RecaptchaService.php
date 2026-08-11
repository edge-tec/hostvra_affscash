<?php
/**
 * RecaptchaService - Google reCAPTCHA v3 integration helper & server-side verification service.
 *
 * Features:
 * - Secure environment variable loading (.env file and getenv/$_ENV)
 * - Server-side verification via Google siteverify API
 * - Configurable score thresholds (RECAPTCHA_MIN_SCORE)
 * - Form action validation
 * - Safe error handling & user-friendly messages
 * - Safe security logging without exposing tokens or secret keys
 */
class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    private static ?array $envCache = null;

    /**
     * Parse .env file if present and cache values.
     */
    public static function getEnv(string $key, ?string $default = null): ?string
    {
        if (self::$envCache === null) {
            self::$envCache = [];
            $envPath = BASE_PATH . '/.env';
            if (file_exists($envPath) && is_readable($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || strpos($line, '#') === 0) continue;
                    if (strpos($line, '=') !== false) {
                        list($k, $v) = explode('=', $line, 2);
                        $k = trim($k);
                        $v = trim($v);
                        // Strip quotes if present
                        if ((strpos($v, '"') === 0 && substr($v, -1) === '"') ||
                            (strpos($v, "'") === 0 && substr($v, -1) === "'")) {
                            $v = substr($v, 1, -1);
                        }
                        self::$envCache[$k] = $v;
                    }
                }
            }
        }

        // Priority order: 1. $_ENV / getenv 2. .env file 3. Config.json 4. $default
        $envVal = getenv($key);
        if ($envVal !== false && $envVal !== '') {
            return (string)$envVal;
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string)$_ENV[$key];
        }

        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return (string)$_SERVER[$key];
        }

        if (isset(self::$envCache[$key]) && self::$envCache[$key] !== '') {
            return (string)self::$envCache[$key];
        }

        // Config fallback (mapping RECAPTCHA_SITE_KEY -> recaptcha.site_key)
        $configKey = strtolower(str_replace('RECAPTCHA_', '', $key));
        $cfgVal = Config::get('config', 'recaptcha.' . $configKey);
        if ($cfgVal !== null && $cfgVal !== '') {
            return (string)$cfgVal;
        }

        return $default;
    }

    /** Is Google reCAPTCHA v3 enabled? */
    public static function isEnabled(): bool
    {
        $enabled = self::getEnv('RECAPTCHA_ENABLED', 'true');
        $isTrue  = in_array(strtolower(trim($enabled)), ['true', '1', 'yes', 'on'], true);

        return $isTrue
            && self::siteKey() !== ''
            && self::secretKey() !== '';
    }

    /** Get Site Key (safe for frontend HTML/JS) */
    public static function siteKey(): string
    {
        return trim((string)self::getEnv('RECAPTCHA_SITE_KEY', ''));
    }

    /** Get Secret Key (backend ONLY — never expose!) */
    private static function secretKey(): string
    {
        return trim((string)self::getEnv('RECAPTCHA_SECRET_KEY', ''));
    }

    /** Minimum score threshold (default 0.5) */
    public static function minScore(): float
    {
        $val = self::getEnv('RECAPTCHA_MIN_SCORE', '0.5');
        return is_numeric($val) ? (float)$val : 0.5;
    }

    /**
     * Verify a Google reCAPTCHA v3 response token with Google.
     *
     * @param string      $token          Value of g-recaptcha-response / recaptcha_token
     * @param string      $expectedAction Expected form action (e.g., 'login', 'contact')
     * @param string|null $remoteIp       Visitor IP address
     * @return array Verification result array
     */
    public static function verify(?string $token, string $expectedAction = '', ?string $remoteIp = null): array
    {
        if (!self::isEnabled()) {
            return [
                'success'      => true,
                'score'        => 1.0,
                'action'       => $expectedAction,
                'skipped'      => true,
                'user_message' => '',
            ];
        }

        $token = trim((string)$token);
        if ($token === '') {
            self::logSecurityEvent($expectedAction, 0.0, false, 'Missing token', $remoteIp);
            return [
                'success'      => false,
                'score'        => 0.0,
                'action'       => '',
                'error'        => 'Missing reCAPTCHA token',
                'user_message' => 'Security verification failed. Please try again.',
            ];
        }

        $secret = self::secretKey();
        $payload = [
            'secret'   => $secret,
            'response' => $token,
        ];

        if ($remoteIp) {
            $payload['remoteip'] = $remoteIp;
        }

        $ch = curl_init(self::VERIFY_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Affscash/1.0 (Google reCAPTCHA v3 Verification)',
        ]);

        $response = curl_exec($ch);
        $curlErr  = curl_errno($ch);
        $curlMsg  = curl_error($ch);
        if (function_exists('curl_close') && PHP_VERSION_ID < 80500) { @curl_close($ch); }

        if ($curlErr || !$response) {
            // Handle timeout/connection error safely
            error_log('[reCAPTCHA v3] API call failed: ' . ($curlMsg ?: 'Empty response'));
            self::logSecurityEvent($expectedAction, 0.0, false, 'API timeout/connection failure', $remoteIp);
            return [
                'success'      => false,
                'score'        => 0.0,
                'action'       => '',
                'error'        => 'Google reCAPTCHA verification service unavailable',
                'user_message' => 'Security verification failed. Please try again.',
            ];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            self::logSecurityEvent($expectedAction, 0.0, false, 'Invalid API response format', $remoteIp);
            return [
                'success'      => false,
                'score'        => 0.0,
                'action'       => '',
                'error'        => 'Invalid verification response',
                'user_message' => 'Security verification failed. Please try again.',
            ];
        }

        $success   = !empty($data['success']);
        $score     = (float)($data['score'] ?? 0.0);
        $resAction = (string)($data['action'] ?? '');
        $hostname  = (string)($data['hostname'] ?? '');
        $minScore  = self::minScore();

        // Action check
        $actionMatches = true;
        if ($expectedAction !== '') {
            $expectedLower = strtolower($expectedAction);
            $resLower      = strtolower($resAction);
            // Allow exact match or standard aliases
            if ($expectedLower !== $resLower && strpos($expectedLower, $resLower) === false && strpos($resLower, $expectedLower) === false) {
                $actionMatches = false;
            }
        }

        $isValid = $success && $actionMatches && ($score >= $minScore);

        $failReason = '';
        if (!$success) {
            $errorCodes = isset($data['error-codes']) && is_array($data['error-codes']) ? implode(', ', $data['error-codes']) : 'unknown';
            $failReason = 'Google returned failure codes: ' . $errorCodes;
        } elseif (!$actionMatches) {
            $failReason = "Action mismatch (expected '$expectedAction', got '$resAction')";
        } elseif ($score < $minScore) {
            $failReason = "Score $score below threshold $minScore";
        }

        self::logSecurityEvent($expectedAction, $score, $isValid, $failReason ?: 'Passed', $remoteIp, $hostname);

        if (!$isValid) {
            return [
                'success'      => false,
                'score'        => $score,
                'action'       => $resAction,
                'error'        => $failReason,
                'user_message' => 'Security verification failed. Please try again.',
            ];
        }

        return [
            'success'      => true,
            'score'        => $score,
            'action'       => $resAction,
            'hostname'     => $hostname,
            'user_message' => '',
        ];
    }

    /**
     * Render the Google reCAPTCHA v3 script tag for view templates.
     */
    public static function renderHeadScript(): string
    {
        if (!self::isEnabled()) {
            return '<!-- reCAPTCHA v3 Disabled -->';
        }
        $siteKey = htmlspecialchars(self::siteKey(), ENT_QUOTES, 'UTF-8');
        return '<script src="https://www.google.com/recaptcha/api.js?render=' . $siteKey . '" async defer></script>';
    }

    /**
     * Log security verification metadata safely (NO full tokens or secret keys).
     */
    private static function logSecurityEvent(string $action, float $score, bool $success, string $reason, ?string $remoteIp = null, string $hostname = ''): void
    {
        $ip = $remoteIp ?: ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $ipHash = substr(hash('sha256', $ip . 'recaptcha_salt'), 0, 16);
        $status = $success ? 'SUCCESS' : 'REJECTED';
        $logMsg = sprintf(
            '[reCAPTCHA v3 Log] %s | Action: %s | Score: %.2f | Status: %s | Reason: %s | Host: %s | IP Hash: %s',
            date('Y-m-d H:i:s'),
            $action ?: 'unspecified',
            $score,
            $status,
            $reason,
            $hostname ?: 'N/A',
            $ipHash
        );
        error_log($logMsg);
    }
}
