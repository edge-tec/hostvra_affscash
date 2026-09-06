<?php
/**
 * Smartlink redirect handler
 * Routes visitor to best matching offer based on device + geo targeting,
 * then delegates to click.php for full tracking.
 */
$slug = Helpers::get('slug');
if (!$slug) { http_response_code(404); exit('Not found.'); }

$sl = Database::fetchOne("SELECT * FROM `smartlinks` WHERE slug=? AND status='active'", [$slug]);
if (!$sl) { http_response_code(404); exit('Smartlink not found.'); }

$offers = Database::fetchAll(
    "SELECT so.*,
            o.id        AS offer_id,
            o.offer_url AS offer_url,
            o.status    AS offer_status
     FROM `smartlink_offers` so
     LEFT JOIN `offers` o ON o.id = so.offer_id
     WHERE so.smartlink_id = ?
       AND (
           (so.offer_id IS NOT NULL AND o.status = 'active')
           OR
           (so.offer_id IS NULL AND so.direct_url IS NOT NULL AND so.direct_url != '')
       )",
    [$sl['id']]
);
if (empty($offers)) { http_response_code(503); exit('No active offers.'); }

// ── Detect visitor device + country ──────────────────────────────────────
$ip          = Helpers::getIp();
$ua          = Helpers::getUserAgent();
$deviceInfo  = Helpers::parseDevice($ua);
$geo         = Helpers::getGeoInfo($ip);
$visitorDev  = $deviceInfo['device'];       // 'desktop'|'mobile'|'tablet'
$visitorGeo  = $geo['country'] ?? '';       // 'US', 'GB', '' if unknown

// ── Fraud Blocklist (scope=clicks) ────────────────────────────────────────
// Hard-block before any redirect — including custom URL and direct offer URL
// paths that bypass click.php entirely. The global scope=all guard in index.php
// already covered scope=all entries; this catches scope=clicks entries plus
// matches against the affiliate code supplied on the smartlink URL.
$_slAffCode = Helpers::get('aff') ?: Helpers::get('aff_id');
Blocklist::enforceClick([
    'ip'             => $ip,
    'ua'             => $ua,
    'asn'            => $geo['asn'] ?? ($geo['as'] ?? ''),
    'affiliate_code' => $_slAffCode,
]);

// ── VPN / Proxy hard block ────────────────────────────────────────────────
// Must run before any redirect (including custom URL entries and direct offer
// URL paths that bypass click.php entirely), so VPN/proxy users cannot reach
// any link in the system. Uses the same geo proxy/hosting signals as click.php.
//
// Per-affiliate bypass: if the smartlink URL carries an `aff` (or `aff_id`)
// query param identifying an affiliate that's on the VPN/Proxy skip list, the
// hard block is suppressed for that visitor. The skip list is managed by admins
// at /admin/vpn-proxy-skip.
$_skAffCode    = Helpers::get('aff') ?: Helpers::get('aff_id') ?: Helpers::get('affiliate_id') ?: Helpers::get('affiliate') ?: Helpers::get('ref');
$_slAffId      = null;
$_slAffRow     = null;
if ($_skAffCode) {
    try {
        $_slAffRow = Database::fetchOne(
            "SELECT af.id, af.affiliate_code FROM affiliates af JOIN users u ON u.id = af.user_id WHERE af.affiliate_code=? OR CAST(af.id AS CHAR)=? OR CAST(af.user_id AS CHAR)=? LIMIT 1",
            [$_skAffCode, $_skAffCode, $_skAffCode]
        );
        if ($_slAffRow) {
            $_slAffId = (int)$_slAffRow['id'];
        }
    } catch (\Throwable $e) {}
}

$_vpnSkipForSL = $_slAffId ? VpnSkipList::isSkipped($_slAffId) : ($_skAffCode ? VpnSkipList::isSkippedByCode($_skAffCode) : false);

if ((Config::get('config', 'vpn_detection.enabled') ?? '0') === '1' && !$_vpnSkipForSL) {
    if (($geo['proxy'] ?? false) || ($geo['hosting'] ?? false)) {
        // Log the blocked attempt using the same table as click.php
        try {


            Database::insert('vpn_blocked_log', [
                'affiliate_id'   => $_slAffId,
                'offer_id'       => null,
                'offer_name'     => null,
                'smartlink_id'   => (int)$sl['id'],
                'smartlink_name' => $sl['name'] ?? ('Smartlink #' . $sl['id']),
                'ip_address'     => $ip,
                'detection_type' => ($geo['proxy'] ? 'Proxy' : 'VPN/Hosting'),
                'user_agent'     => substr($ua ?? '', 0, 1000),
                'country'        => $geo['country'] ?? '',
            ]);
        } catch (\Throwable $e) {}
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
}

// ── Score each offer (0–2) ────────────────────────────────────────────────
// Scoring rules:
//   +1 if device_rules is set  AND visitor device is in the list
//   +1 if geo_rules    is set  AND visitor country is in the list
//   +0 per dimension if no rule set (dimension is unconstrained = always qualifies)
//   Offer is excluded if any set rule does NOT match (hard filter)
//
// Result: score 2 = both targeted and matched (highest)
//         score 1 = one rule matched, other unconstrained
//         score 0 = no rules at all (generic offer, lowest priority but always eligible)
//         excluded = at least one rule set but doesn't match visitor
$eligible = [];
foreach ($offers as $o) {
    $geoRules = !empty($o['geo_rules'])    ? json_decode($o['geo_rules'],    true) : null;
    $devRules = !empty($o['device_rules']) ? json_decode($o['device_rules'], true) : null;

    // Hard filter: if a rule is set and visitor doesn't match → skip
    if ($geoRules && ($visitorGeo === '' || !in_array($visitorGeo, $geoRules, true))) continue;
    if ($devRules && !in_array($visitorDev, $devRules, true)) continue;

    // Targeting score: how specifically this offer was configured for this visitor
    $o['_score'] = ($geoRules ? 1 : 0) + ($devRules ? 1 : 0);
    $eligible[] = $o;
}

// Fall back to all offers if none passed geo+device filter
// (e.g. visitor from unrecognised country, but some offers have geo rules)
if (empty($eligible)) {
    foreach ($offers as $o) {
        $o['_score'] = 0;
        $eligible[] = $o;
    }
}

// Highest-scoring candidates only
$maxScore   = max(array_column($eligible, '_score'));
$candidates = array_values(array_filter($eligible, fn($o) => $o['_score'] === $maxScore));

// ── Weighted random selection from candidates ─────────────────────────────
$total      = array_sum(array_column($candidates, 'weight'));
$rand       = mt_rand(1, max(1, (int)$total));
$cumulative = 0;
$selected   = null;
foreach ($candidates as $o) {
    $cumulative += max(1, (int)$o['weight']);
    if ($rand <= $cumulative) { $selected = $o; break; }
}
if (!$selected) $selected = $candidates[0];

// ── Determine if this is a custom URL entry (no linked offer) ─────────────
$isCustomEntry = empty($selected['offer_id']);
$directUrl     = trim($selected['direct_url'] ?? '');

// ── Pass smartlink payout override to click.php (regular offers only) ─────
$payType  = $selected['payout_type']  ?? 'default';
$payValue = $selected['payout_value'] ?? null;
if (!$isCustomEntry && $payType !== 'default') {
    $GLOBALS['_sl_payout_override'] = [
        'type'  => $payType,
        'value' => (float)($payValue ?? 0),
    ];
}

// ── Pass direct URL override to click.php (regular offer with URL override) ──
if (!$isCustomEntry && $directUrl !== '') {
    $GLOBALS['_sl_direct_url'] = $directUrl;
}

// ── Route to affiliate click tracking or direct offer URL ─────────────────
$affCode   = Helpers::get('aff') ?: Helpers::get('aff_id') ?: Helpers::get('affiliate_id') ?: Helpers::get('affiliate') ?: Helpers::get('ref');
$affiliate = $affCode
    ? Database::fetchOne("SELECT af.*, u.status FROM `affiliates` af JOIN `users` u ON u.id=af.user_id WHERE (af.affiliate_code=? OR CAST(af.id AS CHAR)=? OR CAST(af.user_id AS CHAR)=?) AND u.status='active'", [$affCode, $affCode, $affCode])
    : null;

// ── Custom URL entry: track click if affiliate present, then redirect ──────
if ($isCustomEntry) {
    $redirectUrl = $directUrl ?: '/';

    if ($affiliate) {


        $clickId = Helpers::uuid();
        $sub1    = Helpers::get('sub1') ?? '';
        $sub2    = Helpers::get('sub2') ?? '';
        $sub3    = Helpers::get('sub3') ?? '';
        $sub4    = Helpers::get('sub4') ?? '';
        $sub5    = Helpers::get('sub5') ?? '';
        $sub6    = Helpers::get('sub6') ?? '';
        $source  = Helpers::get('source') ?: Helpers::get('utm_source') ?: Helpers::get('traffic_source') ?: '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        // ── Traffic Source Detection ────────────
        $extraDetParams = [
            'utm_source'   => Helpers::get('utm_source'),
            'utm_medium'   => Helpers::get('utm_medium'),
            'utm_campaign' => Helpers::get('utm_campaign'),
            'utm_content'  => Helpers::get('utm_content'),
            'utm_term'     => Helpers::get('utm_term'),
            'gclid'        => Helpers::get('gclid'),
            'x_requested_with' => $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null,
        ];
        $tsDet = TrafficSourceDetector::detect($source, $referer, $ua ?? '', false, $extraDetParams);
        $trueOriginalSource = $tsDet['source'];

        // ── Advanced Traffic Source Override ──────────────────────────────────────
        require_once BASE_PATH . '/core/AdvancedTrafficSourceOverride.php';

        $advOverrideSource = null;
        $advOverrideRuleId = null;
        
        try {
            $deviceInfo = Helpers::parseDeviceDetailed($ua ?? '');
            $ip = Helpers::getIp();
            $geo = Helpers::getGeoInfo($ip);
            
            $overrideMatch = AdvancedTrafficSourceOverride::evaluate($trueOriginalSource, [
                'affiliate_id'  => (int)$affiliate['id'],
                'offer_id'      => null, // custom smartlink redirect doesn't have an offer_id yet
                'advertiser_id' => null,
                'country'       => $geo['country'] ?? '',
                'device_type'   => $deviceInfo['device'] ?? '',
                'browser'       => $deviceInfo['browser'] ?? '',
                'os'            => $deviceInfo['os'] ?? '',
                'smartlink_id'  => (int)$sl['id'],
                'landing_page_idx' => null
            ]);

            if ($overrideMatch) {
                $advOverrideSource = $overrideMatch['override_source'];
                $advOverrideRuleId = $overrideMatch['rule_id'];
            }
        } catch (\Throwable $e) {}



        try {
            Database::insert('clicks', [
                'click_id'     => $clickId,
                'offer_id'     => null,
                'affiliate_id' => (int)$affiliate['id'],
                'smartlink_id' => (int)$sl['id'],
                'source'       => substr($source, 0, 255), // original raw param
                'original_source'         => null,
                'source_override_applied' => 0,
                'traffic_source'       => $tsDet['source'],
                'traffic_source_type'  => $tsDet['type'],
                'override_source'      => $advOverrideSource,
                'override_rule_id'     => $advOverrideRuleId,
                'detected_by'          => $tsDet['detected_by'],
                'utm_source'           => $tsDet['utm_source'],
                'utm_medium'           => $tsDet['utm_medium'],
                'utm_campaign'         => $tsDet['utm_campaign'],
                'utm_content'          => $tsDet['utm_content'],
                'utm_term'             => $tsDet['utm_term'],
                'sub1'         => substr($sub1, 0, 500),
                'sub2'         => substr($sub2, 0, 500),
                'sub3'         => substr($sub3, 0, 500),
                'sub4'         => substr($sub4, 0, 500),
                'sub5'         => substr($sub5, 0, 500),
                'sub6'         => substr($sub6, 0, 500),
                'ip_address'   => $ip,
                'user_agent'   => substr($ua ?? '', 0, 1000),
                'referer'      => substr($referer, 0, 2000),
                'country'      => $geo['country'] ?? '',
                'region'       => $geo['region']  ?? '',
                'city'         => $geo['city']    ?? '',
                'isp'          => $geo['isp']     ?? '',
                'device_type'  => $deviceInfo['device']  ?? '',
                'os'           => $deviceInfo['os']      ?? '',
                'browser'      => $deviceInfo['browser'] ?? '',
                'is_unique'    => 1,
                'is_fraud'     => 0,
                'fraud_score'  => 0,
                'payout'       => 0,
                'revenue'      => 0,
                'status'       => 'valid',
            ]);
        } catch (\Throwable $_e) {}

        // Update daily stats so affiliate day/offer report tabs reflect this click
        try {
            Database::upsertStats(date('Y-m-d'), (int)$affiliate['id'], 0, [
                'clicks'        => 1,
                'unique_clicks' => 1,
            ]);
        } catch (\Throwable $_e) {}

        // Replace {click_id} macro and common subs in destination URL
        $redirectUrl = str_replace(
            ['{click_id}', '{aff_id}', '{sub1}', '{sub2}', '{sub3}', '{sub4}', '{sub5}', '{sub6}', '{country}', '{source}'],
            [urlencode($clickId), urlencode($affCode), urlencode($sub1), urlencode($sub2), urlencode($sub3), urlencode($sub4), urlencode($sub5), urlencode($sub6), urlencode($geo['country'] ?? ''), urlencode($source)],
            $redirectUrl
        );
    }

    header('Location: ' . $redirectUrl, true, 302);
    exit;
}

if (!$affiliate) {
    // No affiliate: redirect directly to offer URL (no click recorded)
    $offerUrl = $directUrl ?: ($selected['offer_url'] ?? '/');
    header('Location: ' . $offerUrl, true, 302);
    exit;
}

// Delegate to click.php for full tracking (geo, fraud, cap checks, stats)
$_GET['offer_id']    = $selected['offer_id'];
$_GET['aff']         = $affiliate['affiliate_code'] ?? $affCode;
$GLOBALS['_sl_id']   = (int)$sl['id'];   // pass smartlink ID so click.php can record it
$GLOBALS['_sl_name'] = $sl['name'] ?? null;
require __DIR__ . '/click.php';
