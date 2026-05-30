<?php
/**
 * Totp — RFC 6238 Time-Based One-Time Password (Google Authenticator compatible).
 *
 * No external dependencies — pure PHP. SHA1, 30-second period, 6 digits.
 *
 *   $secret = Totp::generateSecret();         // base32 string
 *   $uri    = Totp::otpauthUri('Service', $userEmail, $secret);
 *   $qrUrl  = Totp::qrCodeUrl($uri);           // chart.googleapis.com URL
 *   $ok     = Totp::verify($secret, '123456'); // ±1 step time-window tolerance
 *
 *   $cipher = Totp::encrypt($secret);          // for storage
 *   $secret = Totp::decrypt($cipher);          // for verification
 */
final class Totp
{
    private const ALPHABET    = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // RFC 4648 base32
    private const PERIOD_SECS = 30;
    private const DIGITS      = 6;
    private const HASH_ALGO   = 'sha1';
    /** Accept ±1 time step (~30s drift) so a code typed late still works. */
    private const WINDOW      = 1;

    // ── Secret generation ───────────────────────────────────────────────────
    public static function generateSecret(int $length = 16): string
    {
        // 16 base32 chars = 80 bits, plenty for HMAC-SHA1 TOTP.
        $bytes  = random_bytes($length);
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::ALPHABET[ord($bytes[$i]) & 31];
        }
        return $secret;
    }

    // ── otpauth:// URI for the QR code ──────────────────────────────────────
    public static function otpauthUri(string $issuer, string $accountName, string $base32Secret): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($accountName);
        return 'otpauth://totp/' . $label
             . '?secret=' . rawurlencode($base32Secret)
             . '&issuer=' . rawurlencode($issuer)
             . '&algorithm=SHA1&digits=' . self::DIGITS
             . '&period='   . self::PERIOD_SECS;
    }

    /**
     * Server-side QR fallback — used only if the client-side renderer fails.
     * Google's chart.googleapis.com endpoint was deprecated, so we point at
     * api.qrserver.com which is the long-standing free replacement.
     * The view also embeds a small client-side QR library so the page works
     * even when this fallback URL is unreachable.
     */
    public static function qrCodeUrl(string $otpauthUri, int $size = 220): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/'
             . '?size=' . $size . 'x' . $size
             . '&ecc=M'
             . '&margin=8'
             . '&data=' . rawurlencode($otpauthUri);
    }

    // ── Code computation / verification ─────────────────────────────────────
    public static function verify(string $base32Secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) return false;
        $key  = self::base32Decode($base32Secret);
        if ($key === '') return false;
        $now  = (int)floor(time() / self::PERIOD_SECS);
        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            if (hash_equals(self::computeCode($key, $now + $i), $code)) return true;
        }
        return false;
    }

    public static function currentCode(string $base32Secret): string
    {
        $key = self::base32Decode($base32Secret);
        return self::computeCode($key, (int)floor(time() / self::PERIOD_SECS));
    }

    private static function computeCode(string $rawKey, int $counter): string
    {
        $bin  = pack('N*', 0) . pack('N*', $counter);     // 8-byte big-endian counter
        $hash = hash_hmac(self::HASH_ALGO, $bin, $rawKey, true);
        $off  = ord($hash[strlen($hash) - 1]) & 0x0F;
        $val  = ((ord($hash[$off]) & 0x7F) << 24)
              | ((ord($hash[$off+1]) & 0xFF) << 16)
              | ((ord($hash[$off+2]) & 0xFF) << 8)
              |  (ord($hash[$off+3]) & 0xFF);
        return str_pad((string)($val % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    // ── Base32 (no padding, uppercase) ──────────────────────────────────────
    private static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
        if ($b32 === '') return '';
        $bits = '';
        $len  = strlen($b32);
        for ($i = 0; $i < $len; $i++) {
            $idx = strpos(self::ALPHABET, $b32[$i]);
            if ($idx === false) return '';
            $bits .= str_pad(decbin($idx), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $out .= chr(bindec(substr($bits, $i, 8)));
        }
        return $out;
    }

    // ── At-rest encryption for the secret column ────────────────────────────
    public static function encrypt(string $plaintext): string
    {
        $key   = self::keyMaterial();
        $iv    = random_bytes(12);
        $tag   = '';
        $ct    = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ct === false) return '';
        // v1: <12-byte iv><16-byte tag><ciphertext>, base64 encoded
        return 'g2fa1:' . base64_encode($iv . $tag . $ct);
    }

    public static function decrypt(string $cipher): string
    {
        if (!is_string($cipher) || $cipher === '') return '';
        // Backwards compat: legacy installs may store the secret unencrypted.
        if (strpos($cipher, 'g2fa1:') !== 0) return $cipher;
        $raw = base64_decode(substr($cipher, 6), true);
        if ($raw === false || strlen($raw) < 28) return '';
        $iv  = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ct  = substr($raw, 28);
        $key = self::keyMaterial();
        $pt  = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $pt === false ? '' : $pt;
    }

    /** Derive a stable 32-byte key from the app's existing config — no extra setup needed. */
    private static function keyMaterial(): string
    {
        $material = (string)(Config::get('config', 'app.key')
                          ?? Config::get('config', 'app.url')
                          ?? Config::get('config', 'app.name')
                          ?? 'eliteali');
        return hash('sha256', 'totp-v1|' . $material, true);
    }
}
