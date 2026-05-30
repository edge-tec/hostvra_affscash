<?php
/**
 * RegistrationSecurity — IP rate limiting, password complexity validation, and attempt logging
 */
class RegistrationSecurity {

    private static bool $tableReady = false;

    /**
     * Ensures the registration_attempts table exists in the database.
     */
    public static function ensureTable(): void {
        if (self::$tableReady) {
            return;
        }
        try {
            Database::query("CREATE TABLE IF NOT EXISTS registration_attempts (
                id             INT AUTO_INCREMENT PRIMARY KEY,
                ip_address     VARCHAR(45) NOT NULL,
                role           VARCHAR(30) NOT NULL,
                email          VARCHAR(255) NOT NULL,
                is_successful  TINYINT(1) NOT NULL,
                attempt_time   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_reg_ip_time (ip_address, attempt_time),
                INDEX idx_reg_time    (attempt_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            self::$tableReady = true;
        } catch (Exception $e) {
            // Silence exception or log if needed, let database connection errors bubble up if critical
        }
    }

    /**
     * Prunes expired registration attempts (older than 24 hours).
     */
    public static function pruneExpiredAttempts(): void {
        self::ensureTable();
        try {
            // Delete entries older than 24 hours
            Database::query("DELETE FROM registration_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        } catch (Exception $e) {
            // Silence exception
        }
    }

    /**
     * Checks if the given IP address has exceeded the rate limit (5 attempts in 15 minutes).
     * Automatically prunes expired attempts.
     * 
     * @param string $ip The client IP address
     * @return bool True if within limits, False if rate limited (blocked)
     */
    public static function checkRateLimit(string $ip): bool {
        // First prune older records to keep the table clean
        self::pruneExpiredAttempts();

        try {
            // Count attempts in the last 15 minutes
            $count = Database::count('registration_attempts', "ip_address = ? AND attempt_time >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$ip]);
            return $count < 5;
        } catch (Exception $e) {
            // If table doesn't exist or query fails, default to allowing
            return true;
        }
    }

    /**
     * Logs a registration attempt to the database.
     * 
     * @param string $ip Client IP
     * @param string $role User role (e.g., 'affiliate', 'advertiser')
     * @param string $email Proposed email address
     * @param bool $isSuccessful Whether registration was successful
     */
    public static function logAttempt(string $ip, string $role, string $email, bool $isSuccessful): void {
        self::ensureTable();
        try {
            Database::insert('registration_attempts', [
                'ip_address'    => $ip,
                'role'          => $role,
                'email'         => $email,
                'is_successful' => $isSuccessful ? 1 : 0,
                'attempt_time'  => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Silence exception
        }
    }

    /**
     * Validates a password against strong security complexity rules:
     * - Minimum 8 characters
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one number
     * - At least one special character
     * 
     * @param string $password The raw password to validate
     * @return array Array of validation error messages. Empty if valid.
     */
    public static function validatePassword(string $password): array {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character (e.g., !, @, #, $, etc.).';
        }

        return $errors;
    }

    /**
     * Checks if the email domain is a known disposable email provider using a local blocklist and Kickbox API.
     * 
     * @param string $email The email address to check
     * @return bool True if disposable, False otherwise
     */
    public static function isDisposableEmail(string $email): bool {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false; // Invalid email format, let regular validation handle it
        }
        $domain = strtolower(trim($parts[1]));

        // 1. Local huge blocklist check (5000+ domains)
        $listPath = __DIR__ . '/disposable_domains.txt';
        if (file_exists($listPath)) {
            $domains = file($listPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (in_array($domain, $domains, true)) {
                return true;
            }
        } else {
            // Local quick check fallback
            $commonDisposable = ['mailinator.com', '10minutemail.com', 'guerrillamail.com', 'yopmail.com', 'temp-mail.org', 'throwawaymail.com', 'tempmail.com'];
            if (in_array($domain, $commonDisposable)) {
                return true;
            }
        }

        // 2. External API Check Fallback
        try {
            if (function_exists('curl_init')) {
                $ch = curl_init("https://open.kickbox.com/v1/disposable/" . urlencode($domain));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3); // 3 seconds max timeout
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $response) {
                    $data = json_decode($response, true);
                    if (isset($data['disposable']) && $data['disposable'] === true) {
                        return true;
                    }
                }
            }
        } catch (Exception $e) {
            // Silently fail to false if API is down, so we don't block legitimate users
        }

        return false;
    }
}
