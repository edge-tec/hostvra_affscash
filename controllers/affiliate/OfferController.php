<?php
Auth::check('affiliate');
$pageTitle = 'Available Offers';
$affId  = Auth::affiliateId();
$appUrl = Config::get('config','app.url') ?? '';

// Schema migrations
try { Database::query("ALTER TABLE affiliate_offers ADD COLUMN notes TEXT DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN is_inhouse TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers MODIFY COLUMN advertiser_id INT UNSIGNED NULL DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN offer_type VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN offer_image VARCHAR(512) DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN require_approval TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN visibility VARCHAR(20) NOT NULL DEFAULT 'public'"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN device_targeting JSON DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN landing_pages JSON DEFAULT NULL"); } catch (Exception $e) {}
// Ensure tracking_domains table exists
try {
    Database::query("CREATE TABLE IF NOT EXISTS `tracking_domains` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `domain`     VARCHAR(255) NOT NULL UNIQUE,
        `label`      VARCHAR(100) DEFAULT '',
        `is_default` TINYINT(1) DEFAULT 0,
        `is_active`  TINYINT(1) DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_default` (`is_default`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {}

// Approval-state policy for affiliate-initiated requests:
//   no row                → create 'pending' (or 'approved' for public offers)
//   status = 'removed'    → re-request allowed: reset row to 'pending'/'approved'
//   status = 'rejected'   → re-request DENIED (admin must override manually)
//   status = 'blocked'    → re-request DENIED (admin must override manually)
//   status = 'pending'    → silently no-op (already requested)
//   status = 'approved'   → silently no-op (already has access)
$_canReapply = function (?array $existing): bool {
    return !$existing || ($existing['status'] ?? '') === 'removed';
};
$_isLocked = function (?array $existing): bool {
    $s = $existing['status'] ?? '';
    return $s === 'rejected' || $s === 'blocked';
};

// Handle apply_offer_id (require_approval flow with promotion description)
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token')) && Helpers::postRaw('apply_offer_id')) {
    $applyId = (int)Helpers::postRaw('apply_offer_id');
    $promoDesc = trim(Helpers::postRaw('promotion_description') ?? '');
    $offer = Database::fetchOne("SELECT id, name, require_approval, visibility FROM offers WHERE id=? AND status='active'", [$applyId]);
    // Private-offer guard: even if a crafted POST reaches us, an affiliate who
    // is not on the private access list cannot interact with the offer.
    if ($offer && PrivateOffer::isPrivate($offer) && !PrivateOffer::hasAccess($applyId, (int)$affId)) {
        PrivateOffer::log($applyId, (int)$affId, 'deny', null, 'apply', 'Apply attempt on private offer without grant');
        Helpers::redirect('/affiliate/offers');
    }
    if ($offer) {
        $existing = Database::fetchOne("SELECT id, status FROM affiliate_offers WHERE affiliate_id=? AND offer_id=?", [$affId, $applyId]);
        if ($_isLocked($existing)) {
            Helpers::flash('error', 'This offer is not available for new approval requests. Please contact your manager.');
            Helpers::redirect('/affiliate/offers');
        }
        if ($_canReapply($existing)) {
            if ($existing) {
                // Re-request after admin removed approval — reset the existing row.
                Database::update('affiliate_offers',
                    ['status' => 'pending', 'notes' => $promoDesc ?: null, 'approved_at' => null, 'approved_by' => null],
                    'affiliate_id=? AND offer_id=?',
                    [$affId, $applyId]
                );
            } else {
                Database::insert('affiliate_offers', [
                    'affiliate_id' => $affId,
                    'offer_id'     => $applyId,
                    'status'       => 'pending',
                    'notes'        => $promoDesc ?: null,
                ]);
            }
            $me = Auth::currentUser();
            Database::insert('notifications', ['user_id'=>null,'target_role'=>'admin','type'=>'info','title'=>'Offer Access Request','message'=>'An affiliate is requesting access to offer: '.$offer['name'],'link'=>'/admin/affiliates']);
            $adminUsers = Database::fetchAll("SELECT email, first_name, last_name FROM users WHERE role='admin' AND status='active' LIMIT 3");
            foreach ($adminUsers as $admin) {
                Mailer::sendEvent($admin['email'], $admin['first_name'].' '.$admin['last_name'], 'offer_approval_requested', [
                    'name'         => $me['first_name'].' '.$me['last_name'],
                    'email'        => $me['email'],
                    'site_name'    => Config::get('config','app.name') ?? 'AffiliateTracker',
                    'app_url'      => rtrim(Config::get('config','app.url') ?? '', '/'),
                    'offer_name'   => $offer['name'],
                    'affiliate_id' => $affId,
                ]);
            }
            Helpers::flash('success', 'Application submitted. Awaiting approval.');
        }
    }
    Helpers::redirect('/affiliate/offers');
}

// Handle standard apply for offer (no require_approval)
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $offerId = (int)Helpers::postRaw('offer_id');
    // Private-offer guard — see comment above.
    $_privCheckOffer = Database::fetchOne("SELECT visibility FROM offers WHERE id=?", [$offerId]);
    if ($_privCheckOffer && PrivateOffer::isPrivate($_privCheckOffer) && !PrivateOffer::hasAccess($offerId, (int)$affId)) {
        PrivateOffer::log($offerId, (int)$affId, 'deny', null, 'apply', 'Apply attempt on private offer without grant');
        Helpers::redirect('/affiliate/offers');
    }
    $existing = Database::fetchOne("SELECT id, status FROM affiliate_offers WHERE affiliate_id=? AND offer_id=?", [$affId, $offerId]);
    // Permanently locked states (rejected/blocked) — affiliate cannot re-apply
    // unless admin manually overrides from /admin/affiliates/{id}.
    if ($_isLocked($existing)) {
        Helpers::redirect('/affiliate/offers');
    }
    if ($_canReapply($existing)) {
        $offer = Database::fetchOne("SELECT * FROM offers WHERE id=? AND status='active'", [$offerId]);
        if ($offer) {
            $status = $offer['require_approval'] ? 'pending' : 'approved';
            if ($existing) {
                // Re-request after admin removed approval — reset existing row.
                Database::update('affiliate_offers', [
                    'status'      => $status,
                    'approved_at' => $status === 'approved' ? date('Y-m-d H:i:s') : null,
                    'approved_by' => $status === 'approved' ? 0 : null,
                ], 'affiliate_id=? AND offer_id=?', [$affId, $offerId]);
            } else {
                Database::insert('affiliate_offers', [
                    'affiliate_id' => $affId,
                    'offer_id'     => $offerId,
                    'status'       => $status,
                    'approved_at'  => $status === 'approved' ? date('Y-m-d H:i:s') : null,
                    'approved_by'  => $status === 'approved' ? 0 : null,
                ]);
            }
            $me = Auth::currentUser();
            if ($status === 'pending') {
                Database::insert('notifications', ['user_id'=>null,'target_role'=>'admin','type'=>'info','title'=>'Offer Access Request','message'=>'An affiliate is requesting access to offer: '.$offer['name'],'link'=>'/admin/affiliates']);
                // Email admin about request
                $adminUsers = Database::fetchAll("SELECT email, first_name, last_name FROM users WHERE role='admin' AND status='active' LIMIT 3");
                foreach ($adminUsers as $admin) {
                    Mailer::sendEvent($admin['email'], $admin['first_name'].' '.$admin['last_name'], 'offer_approval_requested', [
                        'name'         => $me['first_name'].' '.$me['last_name'],
                        'email'        => $me['email'],
                        'site_name'    => Config::get('config','app.name') ?? 'AffiliateTracker',
                        'app_url'      => rtrim(Config::get('config','app.url') ?? '', '/'),
                        'offer_name'   => $offer['name'],
                        'affiliate_id' => $affId,
                    ]);
                }
            }
            Helpers::flash('success', $status === 'approved' ? 'Offer access granted! Your tracking link is ready.' : 'Access request submitted for admin review.');
        }
    }
    Helpers::redirect('/affiliate/offers');
}

// Offer filters
$qFilter         = Helpers::get('q');
$catFilter       = Helpers::get('category');
$typeFilter      = Helpers::get('payout_type');
$offerTypeFilter = Helpers::get('offer_type');
$countryFilter   = Helpers::get('country');
$deviceFilter    = Helpers::get('device');
$idFilter        = (int)Helpers::get('offer_id');
$accessFilter    = Helpers::get('access_filter') ?: '';

// Private offers are normally hidden — but if this affiliate is in the
// private_offer_access list for a given offer, that offer becomes visible.
PrivateOffer::ensureTables();
$offerWhere = [
    "o.status='active'",
    "(COALESCE(o.visibility,'public') != 'private' OR poa.id IS NOT NULL)",
    "(o.is_inhouse IS NULL OR o.is_inhouse = 0)",
];
$offerParams = [];
if ($idFilter > 0)    { $offerWhere[] = "o.id = ?";             $offerParams[] = $idFilter; }
if ($qFilter)         { $offerWhere[] = "o.name LIKE ?";        $offerParams[] = '%'.$qFilter.'%'; }
if ($catFilter)       { $offerWhere[] = "o.category = ?";       $offerParams[] = $catFilter; }
if ($typeFilter)      { $offerWhere[] = "o.payout_type = ?";    $offerParams[] = $typeFilter; }
if ($offerTypeFilter) { $offerWhere[] = "o.offer_type = ?";     $offerParams[] = $offerTypeFilter; }
if ($countryFilter)   { $offerWhere[] = "JSON_CONTAINS(o.geo_targeting, JSON_QUOTE(?))"; $offerParams[] = $countryFilter; }
if ($deviceFilter)    { $offerWhere[] = "JSON_CONTAINS(o.device_targeting, JSON_QUOTE(?))"; $offerParams[] = $deviceFilter; }
switch ($accessFilter) {
    case 'request':    $offerWhere[] = "o.require_approval = 1"; break;
    case 'all_access': $offerWhere[] = "o.require_approval = 0"; break;
    // 'active' and 'inactive' have no extra effect since base WHERE already requires active status
}
$offerWhereStr = implode(' AND ', $offerWhere);

// Get all public/approved offers with affiliate access status.
// Also join aff_custom_payouts for display.
// Hidden from the affiliate entirely:
//   ao.status='blocked'  — admin hard-block (permanent)
//   ao.status='rejected' — admin denied the access request (permanent)
// 'removed' rows ARE shown — the affiliate can re-request approval.
$offers = Database::fetchAll(
    "SELECT o.*, ao.status as access_status, ao.custom_payout, ao.device_payouts as aff_device_payouts, ao.country_payouts as aff_country_payouts,
     acp.payout as adv_custom_payout,
     poa.id AS private_grant_id,
     (SELECT COUNT(*) FROM clicks WHERE offer_id=o.id AND affiliate_id=? AND DATE(clicked_at)=CURDATE()) as today_clicks
     FROM offers o
     LEFT JOIN affiliate_offers ao    ON ao.offer_id  = o.id AND ao.affiliate_id  = ?
     LEFT JOIN aff_custom_payouts acp ON acp.offer_id = o.id AND acp.affiliate_id = ?
     LEFT JOIN private_offer_access poa ON poa.offer_id = o.id AND poa.affiliate_id = ?
     WHERE $offerWhereStr
       AND (ao.status IS NULL OR ao.status NOT IN ('blocked','rejected'))
     ORDER BY o.created_at DESC",
    array_merge([$affId, $affId, $affId, $affId], $offerParams)
);

// Load affiliate-specific payout rules for all offers in one batch
$affPayoutRules = [];
$affGlobalCap   = 0;
if (!empty($offers)) {
    $offerIds = array_column($offers, 'id');
    $ph = implode(',', array_fill(0, count($offerIds), '?'));
    // Offer-specific caps
    try {
        $caps = Database::fetchAll(
            "SELECT offer_id, daily_cap FROM aff_daily_caps WHERE affiliate_id=? AND offer_id IN ($ph)",
            array_merge([$affId], $offerIds)
        );
        foreach ($caps as $c) $affPayoutRules[(int)$c['offer_id']]['cap'] = (int)$c['daily_cap'];
    } catch (Exception $e) {}
    // Global affiliate cap (no offer filter)
    try {
        $gc = Database::fetchOne("SELECT daily_cap FROM aff_daily_caps WHERE affiliate_id=? AND offer_id IS NULL LIMIT 1", [$affId]);
        if ($gc) $affGlobalCap = (int)$gc['daily_cap'];
    } catch (Exception $e) {}
    // Country payout rules for this affiliate + each offer
    try {
        $crs = Database::fetchAll(
            "SELECT offer_id, country, payout FROM payout_country_rules WHERE affiliate_id=? AND offer_id IN ($ph) ORDER BY country",
            array_merge([$affId], $offerIds)
        );
        foreach ($crs as $r) $affPayoutRules[(int)$r['offer_id']]['countries'][] = $r;
    } catch (Exception $e) {}
    // Device payout rules for this affiliate + each offer
    try {
        $drs = Database::fetchAll(
            "SELECT offer_id, country, device, payout FROM payout_device_rules WHERE affiliate_id=? AND offer_id IN ($ph) ORDER BY device, country",
            array_merge([$affId], $offerIds)
        );
        foreach ($drs as $r) $affPayoutRules[(int)$r['offer_id']]['devices'][] = $r;
    } catch (Exception $e) {}
}

$filterCategories = Database::fetchAll("SELECT DISTINCT category FROM offers WHERE status='active' AND category IS NOT NULL AND category != '' ORDER BY category");
$filterOfferTypes = Database::fetchAll("SELECT DISTINCT offer_type FROM offers WHERE status='active' AND offer_type IS NOT NULL AND offer_type != '' ORDER BY offer_type");

$aff = Database::fetchOne("SELECT affiliate_code FROM affiliates WHERE id=?", [$affId]);

// Load active tracking domains for the domain selector
$tenantId = Tenant::getTenantId();
if ($tenantId) {
    $trackingDomains = Database::fetchAll(
        "SELECT * FROM tracking_domains WHERE is_active=1 AND tenant_id=? ORDER BY is_default DESC, created_at ASC",
        [$tenantId]
    );
} else {
    $trackingDomains = Database::fetchAll(
        "SELECT * FROM tracking_domains WHERE is_active=1 ORDER BY is_default DESC, created_at ASC"
    );
}
// Fallback: if no domains configured, use app URL as the default domain
if (empty($trackingDomains)) {
    $parsedAppUrl = parse_url($appUrl);
    $trackingDomains = [[
        'id'         => 0,
        'domain'     => $parsedAppUrl['host'] ?? preg_replace('#^https?://#','', $appUrl),
        'label'      => 'Default',
        'is_default' => 1,
        'is_active'  => 1,
    ]];
}

// Load device-specific offer links for display in the affiliate panel
$offerLinksMap = [];
if (!empty($offers)) {
    $offerIds = array_column($offers, 'id');
    $ph = implode(',', array_fill(0, count($offerIds), '?'));
    try {
        $allLinks = Database::fetchAll(
            "SELECT * FROM offer_links WHERE offer_id IN ($ph) AND status='active' ORDER BY offer_id, sort_order, id",
            $offerIds
        );
        foreach ($allLinks as $al) {
            $offerLinksMap[$al['offer_id']][] = $al;
        }
    } catch (Exception $e) {}
}

require BASE_PATH . '/views/affiliate/offers/index.php';
