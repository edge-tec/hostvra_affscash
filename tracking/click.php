<?php
/**
 * Click Tracking Endpoint
 *
 * Standard format (canonical):
 *   /click/{offer_id}?aff_id={affiliate_code}&click_id={tracker_click_id}
 *
 * Optional sub parameters for segmentation:
 *   &sub_id_1=...&sub_id_2=...&sub_id_3=...&sub_id_4=...&sub_id_5=...
 *
 * Parameter rules:
 *   aff_id    — affiliate code (also accepts ?aff= for backwards compatibility)
 *   click_id  — affiliate's tracker click ID, returned as {click_id} in postback
 *               (also accepts ?sub1= for backwards compatibility)
 *   sub_id_1..5 — optional segmentation values (also accept ?sub1..5= legacy names)
 *
 * Flow: Advertiser offer link must include {click_id} and {aff_id} macros so this
 * tracker can record the click against the correct affiliate.
 */

// Release session lock immediately — tracking endpoints don't need the session
// and holding it blocks concurrent requests from the same browser session.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$offerId = (int)($_GET['offer_id'] ?? 0);

// ── Affiliate identification ───────────────────────────────────────────────
// Primary: aff_id=  |  Legacy aliases: aff=, affiliate_id=, ref=
$affCode = Helpers::get('aff_id') ?: Helpers::get('aff') ?: Helpers::get('affiliate_id') ?: Helpers::get('ref');

// ── Affiliate tracker click ID (CRITICAL for postback matching) ───────────
// Only click_id= and its direct alias sub1= map here.
// sub_id_1= is intentionally NOT an alias for click_id — it has its own slot (sub2).
$sub1 = Helpers::get('click_id')
     ?: Helpers::get('sub1');

// ── Optional segmentation sub-parameters ──────────────────────────────────
// Definitive parameter → DB column mapping (no two params share a column):
//   sub_id_1= / affid=    → sub2
//   sub_id_2= / aff_sub1= → sub3
//   sub_id_3= / aff_sub2= → sub4
//   sub_id_4= / aff_sub3= → sub5
//   sub_id_5= / aff_sub4= → sub6  (new column, auto-created below)

$sub2 = Helpers::get('sub_id_1')
     ?: Helpers::get('affid')
     ?: Helpers::get('sub2');

$sub3 = Helpers::get('sub_id_2')
     ?: Helpers::get('aff_sub1')
     ?: Helpers::get('sub3');

$sub4 = Helpers::get('sub_id_3')
     ?: Helpers::get('aff_sub2')
     ?: Helpers::get('sub4');

$sub5 = Helpers::get('sub_id_4')
     ?: Helpers::get('aff_sub3')
     ?: Helpers::get('sub5');

$sub6 = Helpers::get('sub_id_5')
     ?: Helpers::get('aff_sub4')
     ?: Helpers::get('sub6');
$source  = Helpers::get('source') ?: Helpers::get('utm_source') ?: Helpers::get('traffic_source');   // traffic source / affiliate source name
$lpForce = Helpers::get('lp');

// Hard-drop blocked / flagged / rejected traffic. By policy this NEVER
// forwards to a configured Traffic Back URL or any offer link — admin-
// rejected traffic must terminate immediately at the validation layer.
// Any Location/Refresh headers queued earlier in the request are cleared
// so no downstream proxy or buffer can leak a redirect to the visitor.
function trafficBack(string $message, int $code = 403, bool $disableTb = false): never {
    $tbUrl = '';
    if (!$disableTb) {
        try { $tbUrl = trim((string)(Config::get('config', 'app.traffic_back_url') ?? '')); } catch (\Throwable $_) {}
    }

    $clickId = (string)($_GET['click_id'] ?? $_GET['sub1'] ?? '');
    $affId   = (string)($_GET['aff_id']   ?? $_GET['affiliate_id'] ?? $_GET['aff'] ?? '');
    $offerId = (string)($_GET['offer_id'] ?? '');
    // /click/{offer_id} routes drop offer_id into $_GET via the router;
    // fall back to scanning the path so direct /click/123 URLs still work.
    if ($offerId === '' && preg_match('#/click/(\d+)#', $_SERVER['REQUEST_URI'] ?? '', $m)) {
        $offerId = $m[1];
    }
    $affSub1 = $clickId;
    $platformClickId = Helpers::uuid();

    $finalUrl = '';
    if ($tbUrl !== '' && preg_match('#^https?://#i', $tbUrl)) {
        $tokens = [
            '{click_id}' => rawurlencode($platformClickId),
            '{sub1}'     => rawurlencode($affSub1),
            '{aff_id}'   => rawurlencode($affId),
            '{offer_id}' => rawurlencode($offerId),
            '{reason}'   => rawurlencode($message),
        ];
        $hasPlaceholders = (bool)preg_match('/\{(click_id|aff_id|offer_id|reason)\}/', $tbUrl);

        if ($hasPlaceholders) {
            $finalUrl = strtr($tbUrl, $tokens);
        } else {
            $sep = (strpos($tbUrl, '?') === false) ? '?' : '&';
            $finalUrl = $tbUrl . $sep . http_build_query([
                'click_id' => $clickId,
                'aff_id'   => $affId,
                'offer_id' => $offerId,
                'reason'   => $message,
            ]);
        }
    }

    // Log the traffic back event
    try {
        global $ip, $geo;
        
        $logAffId = null;
        if ($affId !== '') {
            $affRecord = Database::fetchOne("SELECT id FROM affiliates WHERE affiliate_code=?", [$affId]);
            if ($affRecord) $logAffId = (int)$affRecord['id'];
        }
        
        $logOffId = (is_numeric($offerId) && $offerId > 0) ? (int)$offerId : null;
        $logIp = substr($ip ?? Helpers::getIp(), 0, 45);
        $logCountry = substr((isset($geo) && is_array($geo) && isset($geo['country_code'])) ? $geo['country_code'] : '', 0, 2);
        Database::query(
            "INSERT INTO traffic_back_logs (click_id, affiliate_id, offer_id, reason, redirect_url, ip_address, country) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [substr($platformClickId, 0, 255), $logAffId, $logOffId, substr($message, 0, 255), $finalUrl, $logIp, $logCountry]
        );
    } catch (\Throwable $e) {
        @file_put_contents(BASE_PATH . '/storage/logs/traffic_back_err.txt', date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }

    if ($finalUrl !== '') {
        if (!headers_sent()) {
            @header_remove('Location');
            @header_remove('Refresh');
            header('Cache-Control: no-store');
            header('Location: ' . $finalUrl, true, 302);
        }
        exit;
    }

    // No Traffic Back URL configured → fall back to plain-text response.
    if (!headers_sent()) {
        @header_remove('Location');
        @header_remove('Refresh');
        http_response_code($code);
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo $message;
    exit;
}

if (!$offerId || !$affCode) {
    trafficBack('Invalid tracking link.', 400, true);
}

// Validate offer
$offer = Database::fetchOne(
    "SELECT o.*, COALESCE(a.id, 0) as advertiser_uid FROM `offers` o LEFT JOIN `advertisers` a ON a.id=o.advertiser_id WHERE o.id=? AND o.status='active'",
    [$offerId]
);
if (!$offer) {
    trafficBack('Offer not found or inactive.', 404, true);
}

// Validate affiliate
$affiliate = Database::fetchOne(
    "SELECT af.*, u.status FROM `affiliates` af JOIN `users` u ON u.id=af.user_id WHERE (af.affiliate_code=? OR CAST(af.id AS CHAR)=? OR CAST(af.user_id AS CHAR)=?) AND u.status='active'",
    [$affCode, $affCode, $affCode]
);
if (!$affiliate) {
    trafficBack('Invalid affiliate.', 403, true);
}

// ── Fraud Blocklist: affiliate-level block ────────────────────────────────
// If the admin has blocklisted this affiliate (by numeric id or affiliate_code),
// reject every click immediately — no offer access, no tracking, no payout,
// and never forward to Traffic Back URL or offer redirect.
$_blAffEntry = Blocklist::match([
    'affiliate_id'   => (int)$affiliate['id'],
    'affiliate_code' => $affiliate['affiliate_code'] ?? null,
], 'clicks');
if (Blocklist::isBlocking($_blAffEntry)) {
    Blocklist::recordHit((int)$_blAffEntry['id']);
    Blocklist::deny('Access denied.');
}

// Check if affiliate has access to this offer
$access = Database::fetchOne(
    "SELECT * FROM `affiliate_offers` WHERE affiliate_id=? AND offer_id=? AND status='approved'",
    [$affiliate['id'], $offerId]
);

$ip = Helpers::getIp();

// ── IP Conversion Protection System (One per IP) ──────────────────────────
try {
    $conversionConfig = Config::get('config', 'conversion') ?? [];
    if (!empty($conversionConfig['one_per_ip_enabled']) && !empty($ip) && $ip !== '0.0.0.0') {
        $whitelist = array_filter(array_map('trim', explode("\n", $conversionConfig['one_per_ip_whitelist'] ?? '')));
        if (!in_array($ip, $whitelist)) {
            $durationMode = $conversionConfig['one_per_ip_duration_mode'] ?? 'permanent';
            $query = "SELECT 1 FROM `offer_conversion_history` WHERE affiliate_id=? AND offer_id=? AND visitor_ip=? AND conversion_status='approved'";
            $params = [(int)$affiliate['id'], $offerId, $ip];
            
            if ($durationMode === 'custom') {
                $days = max(1, (int)($conversionConfig['one_per_ip_duration_days'] ?? 30));
                $query .= " AND conversion_time >= DATE_SUB(NOW(), INTERVAL ? DAY)";
                $params[] = $days;
            }
            $query .= " LIMIT 1";
            
            $historyCheck = Database::fetchOne($query, $params);
            if ($historyCheck) {
                // IP already successfully converted this offer for this affiliate
                $redirectMode = $conversionConfig['one_per_ip_redirect_mode'] ?? 'traffic_back';
                if ($redirectMode === 'next_available') {
                    // Try to find the next available active offer for this affiliate
                    $otherOffers = Database::fetchAll(
                        "SELECT o.* FROM `offers` o WHERE o.status='active' AND o.id != ? ORDER BY o.id DESC",
                        [$offerId]
                    );
                    $nextOfferFound = false;
                    foreach ($otherOffers as $otherOffer) {
                        $otherOfferId = (int)$otherOffer['id'];
                        
                        // 1. Check if IP has already converted this other offer
                        $otherQuery = "SELECT 1 FROM `offer_conversion_history` WHERE affiliate_id=? AND offer_id=? AND visitor_ip=? AND conversion_status='approved'";
                        $otherParams = [(int)$affiliate['id'], $otherOfferId, $ip];
                        if ($durationMode === 'custom') {
                            $days = max(1, (int)($conversionConfig['one_per_ip_duration_days'] ?? 30));
                            $otherQuery .= " AND conversion_time >= DATE_SUB(NOW(), INTERVAL ? DAY)";
                            $otherParams[] = $days;
                        }
                        $otherQuery .= " LIMIT 1";
                        if (Database::fetchOne($otherQuery, $otherParams)) {
                            continue; // Already converted this one, skip
                        }
                        
                        // 2. Check offer approval / private access
                        $otherAccess = null;
                        if ($otherOffer['require_approval']) {
                            $otherAccess = Database::fetchOne(
                                "SELECT * FROM `affiliate_offers` WHERE affiliate_id=? AND offer_id=? AND status='approved'",
                                [$affiliate['id'], $otherOfferId]
                            );
                            if (!$otherAccess) {
                                continue; // Not approved, skip
                            }
                        }
                        
                        // Check if blocked or rejected for this offer
                        $otherBlockedRecord = Database::fetchOne(
                            "SELECT id FROM `affiliate_offers` WHERE affiliate_id=? AND offer_id=? AND status IN ('blocked','rejected','removed')",
                            [$affiliate['id'], $otherOfferId]
                        );
                        if ($otherBlockedRecord) {
                            continue;
                        }
                        
                        // Check private offer visibility
                        if (PrivateOffer::isPrivate($otherOffer)) {
                            if (!PrivateOffer::hasAccess($otherOfferId, $affiliate['id'])) {
                                continue; // No private access
                            }
                        }
                        
                        // 3. Check caps
                        // Daily conversion cap
                        if ($otherOffer['daily_cap'] > 0) {
                            $otherTodayConvs = Database::fetchOne(
                                "SELECT COUNT(*) as cnt FROM `conversions` WHERE offer_id=? AND DATE(converted_at)=CURDATE() AND status IN ('pending','approved')",
                                [$otherOfferId]
                            );
                            if (($otherTodayConvs['cnt'] ?? 0) >= $otherOffer['daily_cap']) {
                                continue;
                            }
                        }
                        // Daily click cap
                        if ($otherOffer['daily_click_cap'] > 0) {
                            $otherTodayClicks = Database::fetchOne(
                                "SELECT COUNT(*) as cnt FROM `clicks` WHERE offer_id=? AND DATE(clicked_at)=CURDATE() AND status='valid'",
                                [$otherOfferId]
                            );
                            if (($otherTodayClicks['cnt'] ?? 0) >= $otherOffer['daily_click_cap']) {
                                continue;
                            }
                        }
                        // Affiliate daily cap for this offer
                        $otherAffCapOffer = Database::fetchOne(
                            "SELECT daily_cap FROM aff_daily_caps WHERE affiliate_id=? AND offer_id=? LIMIT 1",
                            [$affiliate['id'], $otherOfferId]
                        );
                        if ($otherAffCapOffer && (int)$otherAffCapOffer['daily_cap'] > 0) {
                            $otherTodayAffConvsOffer = Database::fetchOne(
                                "SELECT COUNT(*) as cnt FROM `conversions` WHERE affiliate_id=? AND offer_id=? AND DATE(converted_at)=CURDATE() AND status IN ('pending','approved')",
                                [$affiliate['id'], $otherOfferId]
                            );
                            if ((int)($otherTodayAffConvsOffer['cnt'] ?? 0) >= (int)$otherAffCapOffer['daily_cap']) {
                                continue;
                            }
                        }
                        
                        // If we reached here, the other offer is active and available!
                        $offerId = $otherOfferId;
                        $offer = $otherOffer;
                        $access = $otherAccess ?: null;
                        $nextOfferFound = true;
                        break;
                    }
                    
                    if (!$nextOfferFound) {
                        // No other offer is available -> redirect to Traffic Back
                        trafficBack('You have already completed this offer.');
                    }
                } else {
                    // Redirect to Traffic Back
                    trafficBack('You have already completed this offer.');
                }
            }
        }
    }
} catch (\Throwable $e) {
    // Fail open if there is a DB issue
}


// ── Enforce blocked / rejected / removed offer access instantly ───────────
// If the admin has blocked, rejected, or removed this affiliate's access to
// the offer, deny the click regardless of whether the offer requires approval
// or not. This invalidates every previously-issued tracking link the moment
// the admin changes the row's status — no cache, no restart needed.
//   - blocked  : admin hard-block,    affiliate cannot re-apply
//   - rejected : admin denied request, affiliate cannot re-apply
//   - removed  : admin revoked grant,  affiliate may submit a new request
$blockedRecord = Database::fetchOne(
    "SELECT id, status FROM `affiliate_offers`
      WHERE affiliate_id=? AND offer_id=? AND status IN ('blocked','rejected','removed')",
    [$affiliate['id'], $offerId]
);
if ($blockedRecord) {
    trafficBack('Access denied to this offer.', 403, true);
}

// ── Private offer enforcement ─────────────────────────────────────────────
// When offers.visibility='private', only affiliates in private_offer_access
// for this offer can generate tracking. Every attempt — allowed or denied —
// is logged. The check runs on every request, so admin grant/revoke takes
// effect on the very next click with no cache or restart.
if (empty($GLOBALS['_sl_id']) && !PrivateOffer::checkClickAccess($offer, (int)$affiliate['id'])) {
    trafficBack('Access denied to this offer.', 403, true);
}

// Resolve actual payout — priority: most specific rule wins
// Priority chain:
//   T1: Aff + Offer + Country + Device  (payout_device_rules with aff+offer+country+device)
//   T2: Aff + Offer + Country           (payout_country_rules with aff+offer+country)
//   T3: Aff + Offer                     (aff_custom_payouts OR affiliate_offers.custom_payout)
//   T4: Aff + Country + Device          (aff_country_device_payouts, global device rules for aff)
//   T5: Aff + Country                   (aff_country_payouts, global country rules for aff)
//   T6: affiliate_offers JSON device/country payouts
//   T7: Offer-level device/country payouts (JSON on offers row)
//   T8: Global device rules (no affiliate)
//   T9: Global country rules (no affiliate)
//   T10: Link-specific payout rate
//   T11: Default offer payout_amount (fallback)
function resolvePayout(array $offer, ?array $access, string $country, string $device, float $linkPayout = 0.0, ?int $affiliateId = null): float {
    // Use explicitly passed affiliateId first, then fall back to access record
    $affId   = $affiliateId ?? ($access['affiliate_id'] ?? null);
    $offerId = (int)$offer['id'];

    // ── T1: Affiliate + Offer + Country + Device (most specific) ──────────
    if ($affId) {
        try {
            $r = Database::fetchOne(
                "SELECT payout FROM payout_device_rules WHERE affiliate_id=? AND offer_id=? AND country=? AND device=? LIMIT 1",
                [$affId, $offerId, $country, $device]
            );
            if ($r) return (float)$r['payout'];
            // Any country, specific device
            $r = Database::fetchOne(
                "SELECT payout FROM payout_device_rules WHERE affiliate_id=? AND offer_id=? AND country='' AND device=? LIMIT 1",
                [$affId, $offerId, $device]
            );
            if ($r) return (float)$r['payout'];
        } catch (\Throwable $e) {}
    }

    // ── T2: Affiliate + Offer + Country ───────────────────────────────────
    if ($affId) {
        try {
            $r = Database::fetchOne(
                "SELECT payout FROM payout_country_rules WHERE affiliate_id=? AND offer_id=? AND country=? LIMIT 1",
                [$affId, $offerId, $country]
            );
            if ($r) return (float)$r['payout'];
        } catch (\Throwable $e) {}
    }

    // ── T3: Affiliate + Offer (flat custom payout) ─────────────────────────
    if ($affId) {
        try {
            $r = Database::fetchOne(
                "SELECT payout FROM aff_custom_payouts WHERE affiliate_id=? AND offer_id=? LIMIT 1",
                [$affId, $offerId]
            );
            if ($r) return (float)$r['payout'];
        } catch (\Throwable $e) {}
        // Also check affiliate_offers.custom_payout (set via affiliate management panel)
        if ($access && $access['custom_payout'] !== null) return (float)$access['custom_payout'];
    }

    // ── T4: Affiliate Global + Country + Device (no offer filter) ─────────
    if ($affId) {
        try {
            $r = Database::fetchOne(
                "SELECT payout FROM aff_country_device_payouts WHERE affiliate_id=? AND country=? AND device=? LIMIT 1",
                [$affId, $country, $device]
            );
            if ($r) return (float)$r['payout'];
        } catch (\Throwable $e) {}
        try {
            $r = Database::fetchOne(
                "SELECT payout FROM payout_device_rules WHERE affiliate_id=? AND offer_id IS NULL AND country=? AND device=? LIMIT 1",
                [$affId, $country, $device]
            );
            if ($r) return (float)$r['payout'];
            $r = Database::fetchOne(
                "SELECT payout FROM payout_device_rules WHERE affiliate_id=? AND offer_id IS NULL AND country='' AND device=? LIMIT 1",
                [$affId, $device]
            );
            if ($r) return (float)$r['payout'];
        } catch (\Throwable $e) {}
    }

    // ── T5: Affiliate Global + Country (no offer filter) ──────────────────
    if ($affId) {
        try {
            $r = Database::fetchOne(
                "SELECT payout FROM aff_country_payouts WHERE affiliate_id=? AND country=? LIMIT 1",
                [$affId, $country]
            );
            if ($r) return (float)$r['payout'];
        } catch (\Throwable $e) {}
        try {
            $r = Database::fetchOne(
                "SELECT payout FROM payout_country_rules WHERE affiliate_id=? AND offer_id IS NULL AND country=? LIMIT 1",
                [$affId, $country]
            );
            if ($r) return (float)$r['payout'];
        } catch (\Throwable $e) {}
    }

    // ── T6: affiliate_offers JSON device/country payouts ──────────────────
    if ($access && !empty($access['device_payouts'])) {
        $dp = json_decode($access['device_payouts'], true);
        if (is_array($dp) && isset($dp[$device])) return (float)$dp[$device];
    }
    if ($access && !empty($access['country_payouts'])) {
        $cp = json_decode($access['country_payouts'], true);
        if (is_array($cp)) foreach ($cp as $rule) {
            if (($rule['country'] ?? '') === $country) return (float)$rule['payout'];
        }
    }

    // ── T7: Offer-level device/country payouts (JSON on offers row) ────────
    if (!empty($offer['device_payouts'])) {
        $dp = json_decode($offer['device_payouts'], true);
        if (is_array($dp) && isset($dp[$device])) return (float)$dp[$device];
    }
    if (!empty($offer['country_payouts'])) {
        $cp = json_decode($offer['country_payouts'], true);
        if (is_array($cp)) foreach ($cp as $rule) {
            if (($rule['country'] ?? '') === $country) return (float)$rule['payout'];
        }
    }

    // ── T8: Global device rules (no affiliate, no offer) ──────────────────
    try {
        $r = Database::fetchOne(
            "SELECT payout FROM payout_device_rules WHERE affiliate_id IS NULL AND offer_id IS NULL AND country=? AND device=? LIMIT 1",
            [$country, $device]
        );
        if ($r) return (float)$r['payout'];
        $r = Database::fetchOne(
            "SELECT payout FROM payout_device_rules WHERE affiliate_id IS NULL AND offer_id IS NULL AND country='' AND device=? LIMIT 1",
            [$device]
        );
        if ($r) return (float)$r['payout'];
    } catch (\Throwable $e) {}

    // ── T9: Global country rules (no affiliate) ────────────────────────────
    try {
        // Offer-specific (no affiliate)
        $r = Database::fetchOne(
            "SELECT payout FROM payout_country_rules WHERE affiliate_id IS NULL AND offer_id=? AND country=? LIMIT 1",
            [$offerId, $country]
        );
        if ($r) return (float)$r['payout'];
        // Fully global
        $r = Database::fetchOne(
            "SELECT payout FROM payout_country_rules WHERE affiliate_id IS NULL AND offer_id IS NULL AND country=? LIMIT 1",
            [$country]
        );
        if ($r) return (float)$r['payout'];
    } catch (\Throwable $e) {}

    // ── T10: Link-specific payout rate ────────────────────────────────────
    if ($linkPayout > 0) return $linkPayout;

    // ── T11: Default offer payout (fallback) ──────────────────────────────
    return (float)$offer['payout_amount'];
}
// If offer requires approval, check access; public offers allow all.
// Smartlink-routed traffic is exempt: the smartlink has its own access control
// (smartlink_requests) so enforcing offer-level approval here would block all
// affiliates who are only approved for the smartlink but not the individual offer.
if ($offer['require_approval'] && !$access && empty($GLOBALS['_sl_id'])) {
    trafficBack('Access denied to this offer.', 403, true);
}

// Check daily conversion cap (offer-level)
if ($offer['daily_cap'] > 0) {
    $todayConvs = Database::fetchOne(
        "SELECT COUNT(*) as cnt FROM `conversions` WHERE offer_id=? AND DATE(converted_at)=CURDATE() AND status IN ('pending','approved')",
        [$offerId]
    );
    if (($todayConvs['cnt'] ?? 0) >= $offer['daily_cap']) {
        trafficBack('Offer capacity reached for today.');
    }
}

// Check daily click cap (offer-level)
if ($offer['daily_click_cap'] > 0) {
    $todayClicks = Database::fetchOne(
        "SELECT COUNT(*) as cnt FROM `clicks` WHERE offer_id=? AND DATE(clicked_at)=CURDATE() AND status='valid'",
        [$offerId]
    );
    if (($todayClicks['cnt'] ?? 0) >= $offer['daily_click_cap']) {
        trafficBack('Click cap reached for today.');
    }
}

// ── Advanced Payout Management: affiliate daily conversion cap ────────────
// Check aff_daily_caps — offer-specific cap first, then affiliate-wide cap
try {
    if ($affiliate) {
        // Offer-specific cap
        $affCapOffer = Database::fetchOne(
            "SELECT daily_cap FROM aff_daily_caps WHERE affiliate_id=? AND offer_id=? LIMIT 1",
            [$affiliate['id'], $offerId]
        );
        if ($affCapOffer && (int)$affCapOffer['daily_cap'] > 0) {
            $todayAffConvsOffer = Database::fetchOne(
                "SELECT COUNT(*) as cnt FROM `conversions` WHERE affiliate_id=? AND offer_id=? AND DATE(converted_at)=CURDATE() AND status IN ('pending','approved')",
                [$affiliate['id'], $offerId]
            );
            if ((int)($todayAffConvsOffer['cnt'] ?? 0) >= (int)$affCapOffer['daily_cap']) {
                trafficBack('Affiliate daily cap reached for this offer.');
            }
        }
        // Affiliate-wide cap (offer_id IS NULL)
        $affCapGlobal = Database::fetchOne(
            "SELECT daily_cap FROM aff_daily_caps WHERE affiliate_id=? AND offer_id IS NULL LIMIT 1",
            [$affiliate['id']]
        );
        if ($affCapGlobal && (int)$affCapGlobal['daily_cap'] > 0) {
            $todayAffConvsAll = Database::fetchOne(
                "SELECT COUNT(*) as cnt FROM `conversions` WHERE affiliate_id=? AND DATE(converted_at)=CURDATE() AND status IN ('pending','approved')",
                [$affiliate['id']]
            );
            if ((int)($todayAffConvsAll['cnt'] ?? 0) >= (int)$affCapGlobal['daily_cap']) {
                trafficBack('Affiliate daily conversion cap reached.');
            }
        }
    }
} catch (\Throwable $e) {} // graceful — table may not exist yet

$ip        = Helpers::getIp();
$ua        = Helpers::getUserAgent();
$referer   = Helpers::getReferer();
$clickId   = Helpers::uuid();
$deviceInfo= Helpers::parseDeviceDetailed($ua);
$geo       = Helpers::getGeoInfo($ip);

// (IP Conversion Protection System relocated early in the script)

// Idempotent schema migration — extended UA columns for Advertiser Reporting.
// Each ALTER is wrapped individually so partial migrations stay correct.
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `browser_version` VARCHAR(64) DEFAULT '' AFTER `browser`"); } catch (\Throwable $_) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `os_version`      VARCHAR(64) DEFAULT '' AFTER `os`"); }      catch (\Throwable $_) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `device_brand`    VARCHAR(64) DEFAULT '' AFTER `device_type`"); } catch (\Throwable $_) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `device_model`    VARCHAR(128) DEFAULT '' AFTER `device_brand`"); } catch (\Throwable $_) {}

// ── Fraud Blocklist Enforcement ─────────────────────────────────────────────
// Centralised check via Blocklist::enforceClick(). Covers IP, CIDR, UA, ASN,
// affiliate (by id or code), and device fingerprint. Hard-block entries
// terminate the request with HTTP 403 and NO redirect — blocked traffic must
// never reach the Traffic Back URL or any offer redirect. Flag/throttle
// entries record the hit and let the click proceed so the fraud scoring
// pipeline can pick them up.
Blocklist::enforceClick([
    'ip'             => $ip,
    'ua'             => $ua,
    'asn'            => $geo['asn'] ?? ($geo['as'] ?? ''),
    'affiliate_id'   => (int)$affiliate['id'],
    'affiliate_code' => $affiliate['affiliate_code'] ?? null,
]);
// ── End Blocklist Enforcement ───────────────────────────────────────────────


// Priority: (1) exact device + exact GEO, (2) exact device + any GEO,
//           (3) all-devices + exact GEO, (4) all-devices + any GEO fallback.
// RAND() rotates among equal-priority rows for even traffic distribution.
$matchedLink = null;
try {
    $visitorDevice  = $deviceInfo['device'];
    $visitorCountry = $geo['country'] ?? '';
    $row = Database::fetchOne(
        "SELECT * FROM offer_links
         WHERE offer_id=? AND status='active'
           AND (device_type=? OR device_type='all')
           AND (geo_country=? OR geo_country='')
         ORDER BY
           CASE
             WHEN device_type=? AND geo_country=? THEN 1
             WHEN device_type=? AND geo_country='' THEN 2
             WHEN device_type='all' AND geo_country=? THEN 3
             ELSE 4
           END,
           RAND()
         LIMIT 1",
        [$offerId, $visitorDevice, $visitorCountry,
         $visitorDevice, $visitorCountry,
         $visitorDevice, $visitorCountry]
    );
    if ($row) $matchedLink = $row;
} catch (Exception $e) {
    // Table not yet created — graceful fallback to default routing
}

// Resolve payout: affiliate overrides → link rate → offer default
$linkPayout = $matchedLink && $matchedLink['payout_rate'] > 0 ? (float)$matchedLink['payout_rate'] : 0.0;
$payout     = resolvePayout($offer, $access ?: null, $geo['country'], $deviceInfo['device'], $linkPayout, (int)$affiliate['id']);
$revenue    = (float)$offer['revenue_amount'];

// Smartlink payout override (set by smartlink.php when routing via a smartlink)
if (!empty($GLOBALS['_sl_payout_override'])) {
    $slOvr = $GLOBALS['_sl_payout_override'];
    if ($slOvr['type'] === 'fixed') {
        $payout = max(0.0, (float)$slOvr['value']);
    } elseif ($slOvr['type'] === 'percent' && (float)$slOvr['value'] > 0) {
        $payout = round($payout * ((float)$slOvr['value'] / 100), 4);
    } elseif ($slOvr['type'] === 'skip') {
        $payout = 0.0;
    }
}

// Affiliate-specific smartlink payout (aff_smartlink_payouts) — highest priority for smartlink traffic.
// Overrides both the base payout and any smartlink offer-row override above.
if (!empty($GLOBALS['_sl_id']) && !empty($affiliate['id'])) {
    try {
        $slAffPayout = Database::fetchOne(
            "SELECT payout, revenue FROM aff_smartlink_payouts WHERE affiliate_id=? AND smartlink_id=? LIMIT 1",
            [(int)$affiliate['id'], (int)$GLOBALS['_sl_id']]
        );
        if ($slAffPayout) {
            $payout  = (float)$slAffPayout['payout'];
            $revenue = (float)$slAffPayout['revenue'];
        }
    } catch (\Throwable $e) {}
}

// Duplicate click check (same IP + affiliate + offer in last 24h)
$isDuplicate = Database::fetchOne(
    "SELECT id FROM `clicks` WHERE ip_address=? AND offer_id=? AND affiliate_id=? AND clicked_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 1",
    [$ip, $offerId, $affiliate['id']]
);
$isUnique = $isDuplicate ? 0 : 1;

// Fraud check (IPQualityScore)
// Score-Only mode: skip click-time check — scoring fires strictly after conversion instead.
$fraudResult = ['score' => 0, 'action' => 'allow'];
$_fraudCfg = Config::get('fraud') ?? [];
$_fraudMode = $_fraudCfg['mode'] ?? 'score_only';
if (!$isDuplicate && $_fraudMode !== 'score_only') {
    if (($_fraudCfg['ipqs_enabled'] ?? false) && !empty($_fraudCfg['ipqs_api_key'])) {
        $fraudResult = FraudIQ::checkIPQS($ip, $clickId);
    }
}

// ── In-House Real-Time Risk Engine ──────────────────────────────────────
$riskEngineResult = RiskEngine::evaluateClick([
    'ip' => $ip,
    'ua' => $ua,
    'affiliate_id' => $affiliate['id'] ?? 0,
    'offer_id' => $offerId,
    'click_id' => $clickId
]);

$fraudReasons = [];
if (!empty($riskEngineResult['reasons'])) {
    $fraudReasons = $riskEngineResult['reasons'];
}

$clickStatus = 'valid';
if ($riskEngineResult['action'] === 'block') {
    $clickStatus = 'blocked';
    $fraudResult['score'] = max($fraudResult['score'] ?? 0, 100);
} elseif ($fraudResult['action'] === 'block') {
    $clickStatus = 'blocked';
} elseif ($isDuplicate) {
    $clickStatus = 'duplicate';
}

// ── Country geo-targeting enforcement ────────────────────────────────
// If the offer has geo_targeting set, only allow traffic from listed countries.
// Skip this check for SmartLink traffic — the smartlink's per-offer geo/device
// scoring already selected the best matching offer for this visitor, so the
// offer's own geo restriction must not fire a second time and block the user.
$isGeoBlocked = false;
if (empty($GLOBALS['_sl_id']) && !empty($offer['geo_targeting'])) {
    $targetGeos = json_decode($offer['geo_targeting'], true);
    if (is_array($targetGeos) && !empty($targetGeos) && $geo['country'] !== '' && !in_array($geo['country'], $targetGeos, true)) {
        $isGeoBlocked = true;
        $clickStatus  = 'blocked';
    }
}

// ── VPN / proxy detection ─────────────────────────────────────────────
// Controlled by admin setting: vpn_detection.enabled
// When enabled: block VPN/proxy traffic and log the attempt.
// When disabled: allow all traffic through (existing behaviour).
$isVpnBlocked    = false;
$vpnDetectionType = '';
$vpnDetectionEnabled = (Config::get('config', 'vpn_detection.enabled') ?? '0') === '1';

// Per-affiliate bypass: admins can add specific affiliate IDs to a skip list
// (managed at /admin/vpn-proxy-skip). Their traffic still gets detection signals
// for fraud-score visibility but the block + vpn_blocked_log path is suppressed.
$_vpnSkipForThisAffiliate = !empty($affiliate['id'])
    && VpnSkipList::isSkipped((int)$affiliate['id']);

$isVpnSignal = $geo['proxy'] || $geo['hosting'] || ($fraudResult['is_vpn'] ?? 0);
if ($isVpnSignal) {
    // Determine detection type label
    if ($geo['proxy'])               $vpnDetectionType = 'Proxy';
    elseif ($geo['hosting'])         $vpnDetectionType = 'VPN/Hosting';
    elseif ($fraudResult['is_vpn'] ?? 0) $vpnDetectionType = 'VPN';

    if ($vpnDetectionEnabled && !$_vpnSkipForThisAffiliate) {
        $isVpnBlocked = true;
        $clickStatus  = 'blocked';
        // Log the blocked attempt to vpn_blocked_log table
        try {
            Database::query("CREATE TABLE IF NOT EXISTS `vpn_blocked_log` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `affiliate_id`   INT UNSIGNED NULL,
                `offer_id`       INT UNSIGNED NULL,
                `offer_name`     VARCHAR(255) NULL,
                `smartlink_id`   INT UNSIGNED NULL,
                `smartlink_name` VARCHAR(255) NULL,
                `ip_address`     VARCHAR(45) NOT NULL,
                `detection_type` VARCHAR(50) NOT NULL DEFAULT 'VPN',
                `user_agent`     VARCHAR(1000) NULL,
                `country`        VARCHAR(4) NOT NULL DEFAULT '',
                `blocked_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_blocked_at` (`blocked_at`),
                INDEX `idx_aff` (`affiliate_id`),
                INDEX `idx_smartlink` (`smartlink_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            try { Database::query("ALTER TABLE `vpn_blocked_log` ADD COLUMN `smartlink_id` INT UNSIGNED NULL AFTER `offer_name`"); } catch (\Throwable $_e) {}
            try { Database::query("ALTER TABLE `vpn_blocked_log` ADD COLUMN `smartlink_name` VARCHAR(255) NULL AFTER `smartlink_id`"); } catch (\Throwable $_e) {}

            $slId = $GLOBALS['_sl_id'] ?? (int)($_GET['sl'] ?? $_GET['smartlink_id'] ?? 0) ?: null;
            $slName = $GLOBALS['_sl_name'] ?? null;
            if ($slId && !$slName) {
                try {
                    $slRow = Database::fetchOne("SELECT name FROM smartlinks WHERE id=?", [$slId]);
                    if ($slRow) $slName = $slRow['name'];
                } catch (\Throwable $e) {}
            }

            Database::insert('vpn_blocked_log', [
                'affiliate_id'   => $affiliate['id'] ?? null,
                'offer_id'       => $offerId ?: null,
                'offer_name'     => $offer['name'] ?? ($offerId ? ('Offer #' . $offerId) : null),
                'smartlink_id'   => $slId,
                'smartlink_name' => $slName,
                'ip_address'     => $ip,
                'detection_type' => $vpnDetectionType,
                'user_agent'     => substr($ua ?? '', 0, 1000),
                'country'        => $geo['country'] ?? '',
            ]);
        } catch (\Throwable $e) { /* table may not exist yet — silently skip */ }
    }
    // When disabled: $isVpnBlocked stays false, traffic passes through
}

// Build offer URL — device link takes priority; else conversion-optimized or random rotation
$landingPages = !empty($offer['landing_pages']) ? json_decode($offer['landing_pages'], true) : null;
$lpIdx = null;

if ($matchedLink) {
    // Use the device-specific (or 'all') offer link URL directly
    $offerUrl = $matchedLink['offer_url'];
    // $lpIdx stays null — no LP rotation for device-routed links
} elseif (!empty($landingPages) && count($landingPages) > 1) {
    // Affiliate-forced landing page selection
    if ($lpForce !== null && $lpForce !== '' && isset($landingPages[(int)$lpForce])) {
        $lpIdx = (int)$lpForce;
        $offerUrl = $landingPages[$lpIdx];
    } else {
        if (!empty($offer['conversion_optimize'])) {
            // CR-based weighted rotation
            $lpStats = Database::fetchAll(
                "SELECT c.landing_page_idx, COUNT(*) as clicks
                 FROM clicks c WHERE c.offer_id=? AND c.landing_page_idx IS NOT NULL AND c.status='valid'
                 GROUP BY c.landing_page_idx",
                [$offerId]
            );
            $convStats = Database::fetchAll(
                "SELECT c.landing_page_idx, COUNT(*) as convs
                 FROM conversions cv JOIN clicks c ON c.click_id=cv.click_id
                 WHERE cv.offer_id=? AND c.landing_page_idx IS NOT NULL
                 GROUP BY c.landing_page_idx",
                [$offerId]
            );
            $convByIdx = [];
            foreach ($convStats as $cs) {
                $convByIdx[(int)$cs['landing_page_idx']] = (int)$cs['convs'];
            }
            $clicksByIdx = [];
            foreach ($lpStats as $s) {
                $clicksByIdx[(int)$s['landing_page_idx']] = (int)$s['clicks'];
            }
            // Build weights (use 1.0 for untested pages, CR for pages with >= 10 clicks)
            $weights = [];
            foreach (array_keys($landingPages) as $i) {
                $c = $clicksByIdx[$i] ?? 0;
                $v = $convByIdx[$i] ?? 0;
                $weights[$i] = ($c >= 10) ? max(0.01, $v / $c) : 1.0;
            }
            $total = array_sum($weights);
            $rand = (mt_rand() / mt_getrandmax()) * $total;
            $cum = 0; $lpIdx = 0;
            foreach ($weights as $i => $w) {
                $cum += $w;
                if ($rand <= $cum) { $lpIdx = $i; break; }
            }
        } else {
            $lpIdx = array_rand($landingPages);
        }
        $offerUrl = $landingPages[$lpIdx];
    }
} else {
    $offerUrl = $offer['offer_url'];
}

// Smartlink direct URL override: replaces the resolved offer URL with the
// URL configured on the specific smartlink offer row.
if (!empty($GLOBALS['_sl_direct_url'])) {
    $offerUrl = $GLOBALS['_sl_direct_url'];
    $lpIdx    = null;
}

// ── Traffic Source Detection ──────────────────────────────────────────────
// Extra params for detection (extracting from GET)
$extraDetParams = [
    'utm_source'   => Helpers::get('utm_source'),
    'utm_medium'   => Helpers::get('utm_medium'),
    'utm_campaign' => Helpers::get('utm_campaign'),
    'utm_content'  => Helpers::get('utm_content'),
    'utm_term'     => Helpers::get('utm_term'),
    'gclid'        => Helpers::get('gclid'),
    'x_requested_with' => $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null,
];
// Get TRUE original source
$tsDet = TrafficSourceDetector::detect($source, $referer, $ua, false, $extraDetParams);
$trueOriginalSource = $tsDet['source'];

// ── Advanced Traffic Source Override ──────────────────────────────────────
require_once BASE_PATH . '/core/AdvancedTrafficSourceOverride.php';

$advOverrideSource = null;
$advOverrideRuleId = null;

try {
    $overrideMatch = AdvancedTrafficSourceOverride::evaluate($trueOriginalSource, [
        'affiliate_id'  => (int)$affiliate['id'],
        'offer_id'      => (int)$offerId,
        'advertiser_id' => (int)($offer['advertiser_uid'] ?? $offer['advertiser_id'] ?? 0),
        'country'       => $geo['country'] ?? '',
        'device_type'   => $deviceInfo['device'] ?? '',
        'browser'       => $deviceInfo['browser'] ?? '',
        'os'            => $deviceInfo['os'] ?? '',
        'smartlink_id'  => $GLOBALS['_sl_id'] ?? null,
        'landing_page_idx' => $lpIdx
    ]);

    if ($overrideMatch) {
        $advOverrideSource = $overrideMatch['override_source'];
        $advOverrideRuleId = $overrideMatch['rule_id'];
    }
} catch (\Throwable $e) {}

// Use the override source for the advertiser URL, or fallback to true source
$finalSourceForAdvertiser = $advOverrideSource ?? $trueOriginalSource;

$offerUrl = str_replace(
    ['{click_id}', '{aff_id}', '{aff_sub1}', '{aff_sub2}', '{aff_sub3}', '{aff_sub4}', '{sub1}', '{sub2}', '{sub3}', '{sub4}', '{sub5}', '{sub6}', '{offer_id}', '{country}', '{source}'],
    [urlencode($clickId), urlencode($affCode), urlencode($sub3), urlencode($sub4), urlencode($sub5), urlencode($sub6), urlencode($sub1), urlencode($sub2), urlencode($sub3), urlencode($sub4), urlencode($sub5), urlencode($sub6), $offerId, urlencode($geo['country']), urlencode($finalSourceForAdvertiser ?? '')],
    $offerUrl
);

// Ensure all late-added columns exist (auto-migration — each ALTER is a no-op if the column is already present)
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `sub6`          VARCHAR(500)  DEFAULT NULL          AFTER `sub5`");        } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `smartlink_id`  INT UNSIGNED  DEFAULT NULL          AFTER `affiliate_id`"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `fraud_reasons` TEXT          DEFAULT NULL");                               } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `source`        VARCHAR(255)  DEFAULT ''");                                 } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `original_source` VARCHAR(255) DEFAULT NULL");                              } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `source_override_applied` TINYINT(1) DEFAULT 0");                           } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `traffic_source`      VARCHAR(50)  DEFAULT 'Unknown'"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `traffic_source_type` VARCHAR(50)  DEFAULT 'Unknown'"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `detected_by`         VARCHAR(100) DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `utm_source`          VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `utm_medium`          VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `utm_campaign`        VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `utm_content`         VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `utm_term`            VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $_e) {}

// ── Auto-Block: fraud score threshold check ───────────────────────────────
// Controlled by admin setting: fraud.auto_block.enabled / fraud.auto_block.threshold
if ($clickStatus === 'valid') {
    $_abCfg = Config::get('config', 'fraud') ?? [];
    if (!empty($_abCfg['auto_block']['enabled'])) {
        $_abThreshold = (int)($_abCfg['auto_block']['threshold'] ?? 70);
        if ($fraudResult['score'] >= $_abThreshold) {
            $clickStatus = 'blocked';
        }
    }
}

// Record click
Database::insert('clicks', [
    'click_id'         => $clickId,
    'offer_id'         => $offerId,
    'affiliate_id'     => $affiliate['id'],
    'smartlink_id'     => ($GLOBALS['_sl_id'] ?? (int)($_GET['sl'] ?? $_GET['smartlink_id'] ?? 0) ?: (Database::fetchOne("SELECT smartlink_id FROM smartlink_offers WHERE offer_id=? LIMIT 1", [$offerId])['smartlink_id'] ?? null)),
    'source'           => substr($source, 0, 255), // original raw param
    'original_source'          => null, // Deprecated
    'source_override_applied'  => 0, // Deprecated
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
    'sub1'             => $sub1,
    'sub2'             => $sub2,
    'sub3'             => $sub3,
    'sub4'             => $sub4,
    'sub5'             => $sub5,
    'sub6'             => $sub6,
    'landing_page_idx' => $lpIdx,
    'ip_address'       => $ip,
    'user_agent'       => substr($ua, 0, 1000),
    'referer'          => substr($referer, 0, 2000),
    'country'          => $geo['country'],
    'region'           => $geo['region'],
    'city'             => $geo['city'],
    'isp'              => $geo['isp'],
    'device_type'      => $deviceInfo['device'],
    'device_brand'     => $deviceInfo['device_brand']    ?? '',
    'device_model'     => $deviceInfo['device_model']    ?? '',
    'os'               => $deviceInfo['os'],
    'os_version'       => $deviceInfo['os_version']      ?? '',
    'browser'          => $deviceInfo['browser'],
    'browser_version'  => $deviceInfo['browser_version'] ?? '',
    'is_unique'        => $isUnique,
    'is_fraud'         => ($fraudResult['action'] !== 'allow' || $riskEngineResult['action'] !== 'allow') ? 1 : 0,
    'fraud_score'      => $fraudResult['score'],
    'fraud_reasons'    => !empty($fraudReasons) ? json_encode($fraudReasons) : null,
    'payout'           => $payout,
    'revenue'          => $revenue,
    'status'           => $clickStatus,
]);

// Update daily stats (clicks only)
if ($clickStatus === 'valid' || $clickStatus === 'duplicate') {
    Database::upsertStats(date('Y-m-d'), $affiliate['id'], $offerId, [
        'clicks'        => 1,
        'unique_clicks' => $isUnique,  // 0 for duplicates, 1 for first click
    ]);
} elseif ($fraudResult['action'] !== 'allow' || $riskEngineResult['action'] !== 'allow') {
    Database::upsertStats(date('Y-m-d'), $affiliate['id'], $offerId, [
        'fraud_clicks' => 1,
    ]);
}

// Block geo-targeted or VPN traffic (recorded for analytics, then redirected)
if ($isGeoBlocked) {
    trafficBack('Traffic from your country is not allowed for this offer.');
}
if ($isVpnBlocked) {
    // Hard block — never redirect to traffic_back_url for VPN/proxy.
    // Any redirect (including traffic_back_url) could be an offer link,
    // so we terminate with 403 and no Location header.
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

// Block fraudulent clicks
if ($clickStatus === 'blocked') {
    trafficBack('Access denied.');
}

// 302 redirect to offer
header("Location: $offerUrl", true, 302);
exit;
