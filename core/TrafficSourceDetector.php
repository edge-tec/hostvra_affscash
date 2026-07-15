<?php
/**
 * TrafficSourceDetector — Detects traffic source from click data.
 *
 * Priority:
 *   1. Traffic Source Override (source_override_applied=1)
 *   2. Explicit source/utm_source parameter
 *   3. HTTP Referer domain matching
 *   4. HTTP_X_REQUESTED_WITH package name matching (In-App Browsers)
 *   5. User-Agent keyword matching (Chat Apps, etc.)
 *   6. Medium-based mapping
 *   7. Fallback: Direct (no referer/UA) or Unknown
 *
 * Returns an array with: source, type, color, detected_by, utm_* fields.
 */
final class TrafficSourceDetector
{
    // ── Source → Badge color map ──────────────────────────────────────────
    const SOURCE_COLORS = [
        'Facebook'      => '#1877F2',
        'Facebook Ads'  => '#0D47A1',
        'Instagram'     => '#E4405F',
        'Instagram DM'  => '#E4405F',
        'Threads'       => '#000000',
        'Messenger'     => '#00B2FF',
        'WhatsApp'      => '#25D366',
        'Telegram'      => '#26A5E4',
        'Discord'       => '#5865F2',
        'Skype'         => '#00AFF0',
        'Signal'        => '#3A76F0',
        'WeChat'        => '#07C160',
        'LINE'          => '#00C300',
        'Viber'         => '#7360F2',
        'Snapchat'      => '#FFFC00',
        'Slack'         => '#4A154B',
        'Teams'         => '#6264A7',
        'Gmail'         => '#EA4335',
        'Outlook'       => '#0078D4',
        'Google Search' => '#34A853',
        'Google Ads'    => '#FBBC04',
        'YouTube'       => '#FF0000',
        'Reddit'        => '#FF4500',
        'Quora'         => '#B92B27',
        'X'             => '#1DA1F2',
        'TikTok'        => '#010101',
        'Pinterest'     => '#E60023',
        'LinkedIn'      => '#0A66C2',
        'Email'         => '#7C3AED',
        'Display'       => '#F97316',
        'Push'          => '#EAB308',
        'Native'        => '#14B8A6',
        'SMS'           => '#06B6D4',
        'Referral'      => '#8B5CF6',
        'Organic'       => '#22C55E',
        'Direct'        => '#64748B',
        'Unknown'       => '#94A3B8',
    ];

    // ── Source → Type map ─────────────────────────────────────────────────
    const SOURCE_TYPES = [
        'Facebook'      => 'Social',
        'Facebook Ads'  => 'Paid',
        'Instagram'     => 'Social',
        'Instagram DM'  => 'Chat',
        'Threads'       => 'Social',
        'Messenger'     => 'Chat',
        'WhatsApp'      => 'Chat',
        'Telegram'      => 'Chat',
        'Discord'       => 'Chat',
        'Skype'         => 'Chat',
        'Signal'        => 'Chat',
        'WeChat'        => 'Chat',
        'LINE'          => 'Chat',
        'Viber'         => 'Chat',
        'Snapchat'      => 'Chat',
        'Slack'         => 'Chat',
        'Teams'         => 'Chat',
        'Gmail'         => 'Email',
        'Outlook'       => 'Email',
        'Google Search' => 'Organic',
        'Google Ads'    => 'Paid',
        'YouTube'       => 'Social',
        'Reddit'        => 'Social',
        'Quora'         => 'Social',
        'X'             => 'Social',
        'TikTok'        => 'Social',
        'Pinterest'     => 'Social',
        'LinkedIn'      => 'Social',
        'Email'         => 'Email',
        'Display'       => 'Display',
        'Push'          => 'Push',
        'Native'        => 'Native',
        'SMS'           => 'SMS',
        'Referral'      => 'Referral',
        'Organic'       => 'Organic',
        'Direct'        => 'Direct',
        'Unknown'       => 'Unknown',
    ];

    // ── Referer domain → source mapping ──────────────────────────────────
    private static array $refererMap = [
        // Facebook family
        'facebook.com'      => 'Facebook',
        'fb.com'            => 'Facebook',
        'fb.me'             => 'Facebook',
        'l.facebook.com'    => 'Facebook',
        'lm.facebook.com'   => 'Facebook',
        'm.facebook.com'    => 'Facebook',
        'web.facebook.com'  => 'Facebook',
        // Instagram
        'instagram.com'     => 'Instagram',
        'l.instagram.com'   => 'Instagram',
        // Threads
        'threads.net'       => 'Threads',
        // Messenger
        'm.me'              => 'Messenger',
        'messenger.com'     => 'Messenger',
        'l.messenger.com'   => 'Messenger',
        // WhatsApp
        'wa.me'             => 'WhatsApp',
        'whatsapp.com'      => 'WhatsApp',
        'api.whatsapp.com'  => 'WhatsApp',
        'web.whatsapp.com'  => 'WhatsApp',
        // Telegram
        't.me'              => 'Telegram',
        'telegram.org'      => 'Telegram',
        'web.telegram.org'  => 'Telegram',
        'telegram.me'       => 'Telegram',
        // Google
        'google.com'        => 'Google Search',
        'google.co'         => 'Google Search',
        'google.co.uk'      => 'Google Search',
        'google.co.in'      => 'Google Search',
        'google.de'         => 'Google Search',
        'google.fr'         => 'Google Search',
        'google.es'         => 'Google Search',
        'google.it'         => 'Google Search',
        'google.com.br'     => 'Google Search',
        'google.ru'         => 'Google Search',
        'google.ca'         => 'Google Search',
        'google.com.au'     => 'Google Search',
        'google.co.jp'      => 'Google Search',
        'google.com.bd'     => 'Google Search',
        // YouTube
        'youtube.com'       => 'YouTube',
        'youtu.be'          => 'YouTube',
        'm.youtube.com'     => 'YouTube',
        // Reddit
        'reddit.com'        => 'Reddit',
        'old.reddit.com'    => 'Reddit',
        'out.reddit.com'    => 'Reddit',
        // Quora
        'quora.com'         => 'Quora',
        // X / Twitter
        'twitter.com'       => 'X',
        'x.com'             => 'X',
        't.co'              => 'X',
        'mobile.twitter.com'=> 'X',
        // TikTok
        'tiktok.com'        => 'TikTok',
        'vm.tiktok.com'     => 'TikTok',
        // Pinterest
        'pinterest.com'     => 'Pinterest',
        'pin.it'            => 'Pinterest',
        // LinkedIn
        'linkedin.com'      => 'LinkedIn',
        'lnkd.in'           => 'LinkedIn',
        // Search engines (Organic)
        'bing.com'          => 'Organic',
        'yahoo.com'         => 'Organic',
        'duckduckgo.com'    => 'Organic',
        'baidu.com'         => 'Organic',
        'yandex.com'        => 'Organic',
        'yandex.ru'         => 'Organic',
        'ecosia.org'        => 'Organic',
    ];

    // ── In-App Browser App Package Headers (HTTP_X_REQUESTED_WITH) ───────
    private static array $appPackageMap = [
        'com.whatsapp'                   => 'WhatsApp',
        'com.whatsapp.w4b'               => 'WhatsApp',
        'org.telegram.messenger'         => 'Telegram',
        'org.telegram.plus'              => 'Telegram',
        'com.facebook.orca'              => 'Messenger',
        'com.facebook.katana'            => 'Facebook',
        'com.instagram.android'          => 'Instagram',
        'com.instagram.direct'           => 'Instagram DM',
        'com.discord'                    => 'Discord',
        'com.skype.raider'               => 'Skype',
        'org.thoughtcrime.securesms'     => 'Signal',
        'com.tencent.mm'                 => 'WeChat',
        'jp.naver.line.android'          => 'LINE',
        'com.viber.voip'                 => 'Viber',
        'com.snapchat.android'           => 'Snapchat',
        'com.Slack'                      => 'Slack',
        'com.microsoft.teams'            => 'Teams',
        'com.google.android.gm'          => 'Gmail',
        'com.microsoft.office.outlook'   => 'Outlook',
        'com.zhiliaoapp.musically'       => 'TikTok',
        'com.twitter.android'            => 'X',
        'com.reddit.frontpage'           => 'Reddit',
        'com.linkedin.android'           => 'LinkedIn',
    ];

    // ── User-Agent → source mapping ──────────────────────────────────────
    private static array $uaMap = [
        'WhatsApp'          => 'WhatsApp',
        'TelegramBot'       => 'Telegram',
        'Telegram'          => 'Telegram',
        'FBAN/Messenger'    => 'Messenger',
        'FB_IAB/Messenger'  => 'Messenger',
        'Messenger/'        => 'Messenger',
        'Discord'           => 'Discord',
        'Skype'             => 'Skype',
        'Signal'            => 'Signal',
        'MicroMessenger'    => 'WeChat',
        'WeChat'            => 'WeChat',
        'Line/'             => 'LINE',
        'Viber'             => 'Viber',
        'Snapchat'          => 'Snapchat',
        'Slack'             => 'Slack',
        'Teams'             => 'Teams',
        'Gmail'             => 'Gmail',
        'Outlook'           => 'Outlook',
        'Instagram'         => 'Instagram',
        'FBAN/'             => 'Facebook',
        'FB_IAB/'           => 'Facebook',
        'Pinterest/'        => 'Pinterest',
        'LinkedInApp'       => 'LinkedIn',
        'TikTok'            => 'TikTok',
        'Twitter'           => 'X',
        'Reddit'            => 'Reddit',
    ];

    // ── Source keyword → source mapping (for ?source= / ?utm_source=) ────
    private static array $sourceKeywordMap = [
        'facebook'    => 'Facebook',
        'fb'          => 'Facebook',
        'instagram'   => 'Instagram',
        'ig'          => 'Instagram',
        'threads'     => 'Threads',
        'messenger'   => 'Messenger',
        'whatsapp'    => 'WhatsApp',
        'telegram'    => 'Telegram',
        'tg'          => 'Telegram',
        'discord'     => 'Discord',
        'skype'       => 'Skype',
        'signal'      => 'Signal',
        'wechat'      => 'WeChat',
        'line'        => 'LINE',
        'viber'       => 'Viber',
        'snapchat'    => 'Snapchat',
        'slack'       => 'Slack',
        'teams'       => 'Teams',
        'gmail'       => 'Gmail',
        'outlook'     => 'Outlook',
        'google'      => 'Google Search',
        'youtube'     => 'YouTube',
        'yt'          => 'YouTube',
        'reddit'      => 'Reddit',
        'quora'       => 'Quora',
        'twitter'     => 'X',
        'x'           => 'X',
        'tiktok'      => 'TikTok',
        'pinterest'   => 'Pinterest',
        'linkedin'    => 'LinkedIn',
        'email'       => 'Email',
        'display'     => 'Display',
        'banner'      => 'Display',
        'push'        => 'Push',
        'native'      => 'Native',
        'sms'         => 'SMS',
        'organic'     => 'Organic',
        'referral'    => 'Referral',
    ];

    /**
     * Detect the traffic source from click data.
     *
     * @param string $source          Click source field (already overridden if applicable)
     * @param string $referer         HTTP Referer from the click
     * @param string $ua              User-Agent from the click
     * @param int    $overrideApplied Whether source override was applied (1/0)
     * @param array  $extraParams     Optional: sub params that may contain UTM data, headers, etc.
     * @return array{source: string, type: string, color: string, detected_by: string, utm_source: ?string, utm_medium: ?string, utm_campaign: ?string, utm_content: ?string, utm_term: ?string}
     */
    public static function detect(
        string $source,
        string $referer,
        string $ua,
        int    $overrideApplied = 0,
        array  $extraParams = []
    ): array {
        // Extract UTM params from referer URL query string
        $utmSource   = null;
        $utmMedium   = null;
        $utmCampaign = null;
        $utmContent  = null;
        $utmTerm     = null;

        // Parse UTM from referer
        if ($referer !== '') {
            $qry = parse_url($referer, PHP_URL_QUERY);
            if ($qry) {
                parse_str($qry, $qp);
                $utmSource   = self::trimOrNull($qp['utm_source']   ?? null);
                $utmMedium   = self::trimOrNull($qp['utm_medium']   ?? null);
                $utmCampaign = self::trimOrNull($qp['utm_campaign'] ?? null);
                $utmContent  = self::trimOrNull($qp['utm_content']  ?? null);
                $utmTerm     = self::trimOrNull($qp['utm_term']     ?? null);
            }
        }

        // Override UTMs from extra params if present
        if (!empty($extraParams['utm_source']))   $utmSource   = substr($extraParams['utm_source'], 0, 255);
        if (!empty($extraParams['utm_medium']))   $utmMedium   = substr($extraParams['utm_medium'], 0, 255);
        if (!empty($extraParams['utm_campaign'])) $utmCampaign = substr($extraParams['utm_campaign'], 0, 255);
        if (!empty($extraParams['utm_content']))  $utmContent  = substr($extraParams['utm_content'], 0, 255);
        if (!empty($extraParams['utm_term']))     $utmTerm     = substr($extraParams['utm_term'], 0, 255);

        $detected = null;
        $detectedBy = '';
        $srcLower = strtolower(trim($source));

        // ── Priority 1: Traffic Source Override applied → use the overridden source key
        if ($overrideApplied && $srcLower !== '') {
            $detected = self::resolveFromOverrideKey($srcLower);
            if ($detected) $detectedBy = 'Traffic Source Override';
        }

        // ── Priority 2: Explicit source param
        if (!$detected && $srcLower !== '') {
            $detected = self::resolveFromSourceKeyword($srcLower);
            if ($detected) $detectedBy = 'Click Parameter (source)';
        }

        // ── Priority 3: UTM Source match
        if (!$detected && $utmSource) {
            $detected = self::resolveFromSourceKeyword(strtolower($utmSource));
            if ($detected) $detectedBy = 'UTM Source Parameter';
        }

        // Check for paid medium (upgrades source)
        $isPaid = false;
        if ($utmMedium) {
            $medLower = strtolower($utmMedium);
            $isPaid = in_array($medLower, ['cpc', 'cpm', 'ppc', 'paid', 'paidsocial', 'paid_social', 'retargeting']);
        }

        // Check for Google Ads via gclid in referer/params
        if (!$detected && (($referer !== '' && str_contains($referer, 'gclid=')) || !empty($extraParams['gclid']))) {
            $detected = 'Google Ads';
            $detectedBy = 'Deep Link (gclid)';
        }

        // ── Priority 4: HTTP_X_REQUESTED_WITH (In-App Browser Package Name)
        if (!$detected && !empty($extraParams['x_requested_with'])) {
            $pkg = strtolower(trim($extraParams['x_requested_with']));
            if (isset(self::$appPackageMap[$pkg])) {
                $detected = self::$appPackageMap[$pkg];
                $detectedBy = 'App Package (X-Requested-With)';
            }
        }

        // ── Priority 5: Referer domain matching
        if (!$detected && $referer !== '') {
            $det = self::resolveFromReferer($referer, $ua);
            if ($det) {
                $detected = $det;
                $detectedBy = 'HTTP Referrer';
            }
        }

        // ── Priority 6: User-Agent matching (Excellent for Chat Apps hiding referers)
        if (!$detected && $ua !== '') {
            $det = self::resolveFromUA($ua);
            if ($det) {
                $detected = $det;
                $detectedBy = 'User-Agent Header';
            }
        }

        // ── Priority 7: Medium-based fallback detection
        if (!$detected && $utmMedium) {
            $medLower = strtolower($utmMedium);
            $det = match (true) {
                $medLower === 'email'                                     => 'Email',
                in_array($medLower, ['display', 'banner', 'cpm'])        => 'Display',
                $medLower === 'push'                                      => 'Push',
                $medLower === 'native'                                    => 'Native',
                $medLower === 'sms'                                       => 'SMS',
                in_array($medLower, ['social', 'paidsocial'])            => 'Referral',
                in_array($medLower, ['organic', 'seo'])                  => 'Organic',
                in_array($medLower, ['cpc', 'ppc', 'paid', 'retargeting'])=> 'Display',
                $medLower === 'referral'                                  => 'Referral',
                default                                                   => null,
            };
            if ($det) {
                $detected = $det;
                $detectedBy = 'UTM Medium Parameter';
            }
        }

        // ── Priority 8: Final Fallback (Direct vs Unknown)
        if (!$detected) {
            if ($referer === '' || $referer === null) {
                $detected = 'Direct';
                $detectedBy = 'Empty Referrer (Fallback)';
            } else {
                $detected = 'Unknown';
                $detectedBy = 'Unknown Referrer: ' . parse_url($referer, PHP_URL_HOST);
            }
        }

        // Upgrade to Ads variant if paid medium detected
        if ($isPaid) {
            if ($detected === 'Facebook') {
                $detected = 'Facebook Ads';
                $detectedBy .= ' + Paid Medium';
            }
            if ($detected === 'Google Search') {
                $detected = 'Google Ads';
                $detectedBy .= ' + Paid Medium';
            }
        }

        return [
            'source'       => $detected,
            'type'         => self::SOURCE_TYPES[$detected] ?? 'Unknown',
            'color'        => self::SOURCE_COLORS[$detected] ?? '#94A3B8',
            'detected_by'  => $detectedBy,
            'utm_source'   => $utmSource   ? substr($utmSource, 0, 255)   : null,
            'utm_medium'   => $utmMedium   ? substr($utmMedium, 0, 255)   : null,
            'utm_campaign' => $utmCampaign ? substr($utmCampaign, 0, 255) : null,
            'utm_content'  => $utmContent  ? substr($utmContent, 0, 255)  : null,
            'utm_term'     => $utmTerm     ? substr($utmTerm, 0, 255)     : null,
        ];
    }

    /**
     * Get the badge color for a source name.
     */
    public static function color(string $source): string
    {
        return self::SOURCE_COLORS[$source] ?? '#94A3B8';
    }

    /**
     * Get all available source names (for filter dropdowns).
     */
    public static function allSources(): array
    {
        return array_keys(self::SOURCE_COLORS);
    }

    // ── Private helpers ──────────────────────────────────────────────────

    private static function resolveFromOverrideKey(string $key): ?string
    {
        $map = [
            'organic'    => 'Organic',
            'paid_ads'   => 'Display',
            'display'    => 'Display',
            'email'      => 'Email',
            'social'     => 'Referral',
            'native_ads' => 'Native',
            'push'       => 'Push',
            'other'      => 'Referral',
        ];
        return $map[$key] ?? self::resolveFromSourceKeyword($key);
    }

    private static function resolveFromSourceKeyword(string $keyword): ?string
    {
        if (isset(self::$sourceKeywordMap[$keyword])) {
            return self::$sourceKeywordMap[$keyword];
        }
        foreach (self::$sourceKeywordMap as $k => $v) {
            if (str_contains($keyword, $k)) {
                return $v;
            }
        }
        return null;
    }

    private static function resolveFromReferer(string $referer, string $ua): ?string
    {
        $host = strtolower(parse_url($referer, PHP_URL_HOST) ?? '');
        if ($host === '') return null;
        $host = preg_replace('/^www\./', '', $host);

        if (isset(self::$refererMap[$host])) {
            $source = self::$refererMap[$host];
            if ($source === 'Facebook' && $ua !== '') {
                $uaLower = strtolower($ua);
                if (str_contains($uaLower, 'messenger') || str_contains($uaLower, 'fban/messenger')) {
                    return 'Messenger';
                }
            }
            if ($source === 'Instagram' && $ua !== '') {
                $uaLower = strtolower($ua);
                if (str_contains($uaLower, 'direct')) {
                    return 'Instagram DM';
                }
            }
            return $source;
        }

        $parts = explode('.', $host);
        if (count($parts) > 2) {
            $baseDomain = implode('.', array_slice($parts, -2));
            if (isset(self::$refererMap[$baseDomain])) {
                return self::$refererMap[$baseDomain];
            }
        }

        if (preg_match('/^google\./i', $host)) {
            return 'Google Search';
        }

        return null;
    }

    private static function resolveFromUA(string $ua): ?string
    {
        $uaLower = strtolower($ua);
        foreach (self::$uaMap as $keyword => $source) {
            if (str_contains($uaLower, strtolower($keyword))) {
                return $source;
            }
        }
        return null;
    }

    private static function trimOrNull($val): ?string
    {
        if ($val === null || $val === '') return null;
        $v = trim((string)$val);
        return $v !== '' ? $v : null;
    }
}
