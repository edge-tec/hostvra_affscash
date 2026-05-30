<?php
/**
 * Cloudflare Turnstile — server-side token verification helper.
 *
 * Usage:
 *   if (Turnstile::isEnabled()) {
 *       $ok = Turnstile::verify($_POST['cf-turnstile-response'] ?? '');
 *       if (!$ok) { $errors[] = 'CAPTCHA verification failed. Please try again.'; }
 *   }
 */
class Turnstile
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /** Is Turnstile enabled and configured? */
    public static function isEnabled(): bool
    {
        return (Config::get('config', 'turnstile.enabled') === '1')
            && (Config::get('config', 'turnstile.site_key')   !== '')
            && (Config::get('config', 'turnstile.secret_key') !== '');
    }

    /** Return the site key (used in view templates). */
    public static function siteKey(): string
    {
        return Config::get('config', 'turnstile.site_key') ?? '';
    }

    /**
     * Verify a Turnstile response token with Cloudflare.
     * Returns true on success, false on failure or network error.
     *
     * @param string $token   Value of cf-turnstile-response field from POST
     * @param string $remoteIp  Optional — pass visitor IP for extra validation
     */
    public static function verify(string $token, string $remoteIp = ''): bool
    {
        if (!self::isEnabled()) {
            return true; // Not configured — let through
        }

        if ($token === '') {
            return false;
        }

        $secretKey = Config::get('config', 'turnstile.secret_key') ?? '';

        $payload = [
            'secret'   => $secretKey,
            'response' => $token,
        ];
        if ($remoteIp !== '') {
            $payload['remoteip'] = $remoteIp;
        }

        $ch = curl_init(self::VERIFY_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'AffiliateTracker/1.0 TurnstileVerify',
        ]);

        $response = curl_exec($ch);
        $curlErr  = curl_errno($ch);
        curl_close($ch);

        if ($curlErr || !$response) {
            // Network failure — fail closed (block the request)
            return false;
        }

        $data = json_decode($response, true);
        return !empty($data['success']);
    }
}
