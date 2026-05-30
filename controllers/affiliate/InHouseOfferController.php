<?php
Auth::check('affiliate');
$pageTitle = 'In-House Offers';
$affId  = Auth::affiliateId();
$appUrl = rtrim(Config::get('config', 'app.url') ?? '', '/');

// Handle apply / access request
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $offerId = (int)Helpers::postRaw('offer_id');
    $offer   = Database::fetchOne(
        "SELECT * FROM offers WHERE id=? AND is_inhouse=1 AND status='active'",
        [$offerId]
    );

    // Private-offer guard: in-house private offers also require an explicit
    // grant. Block the apply submission and log the denial.
    if ($offer && PrivateOffer::isPrivate($offer) && !PrivateOffer::hasAccess($offerId, (int)$affId)) {
        PrivateOffer::log($offerId, (int)$affId, 'deny', null, 'apply', 'Apply attempt on private in-house offer without grant');
        Helpers::redirect('/affiliate/inhouse-offers');
    }

    if ($offer) {
        $existing = Database::fetchOne(
            "SELECT id, status FROM affiliate_offers WHERE affiliate_id=? AND offer_id=?",
            [$affId, $offerId]
        );
        // Permanent deny states — admin must manually override.
        if ($existing && in_array($existing['status'], ['blocked','rejected'], true)) {
            Helpers::redirect('/affiliate/inhouse-offers');
        }
        // Allow first-time request OR re-request after admin "removed" approval.
        if (!$existing || $existing['status'] === 'removed') {
            $status = $offer['require_approval'] ? 'pending' : 'approved';
            if ($existing) {
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

            if ($status === 'pending') {
                Database::insert('notifications', [
                    'user_id'     => null,
                    'target_role' => 'admin',
                    'type'        => 'info',
                    'title'       => 'In-House Offer Access Request',
                    'message'     => 'An affiliate is requesting access to in-house offer: ' . $offer['name'],
                    'link'        => '/admin/offer-approvals',
                ]);
                Helpers::flash('success', 'Access request submitted — awaiting admin approval.');
            } else {
                Helpers::flash('success', 'Access granted! Your tracking link is ready below.');
            }
        }
    }
    Helpers::redirect('/affiliate/inhouse-offers');
}

// Filters
$qFilter      = Helpers::get('q');
$idFilter     = (int)Helpers::get('offer_id');
$accessFilter = Helpers::get('access_filter') ?: '';

// Show ALL active in-house offers so affiliates can browse and apply
$where  = ["o.is_inhouse = 1", "o.status = 'active'"];
$whereParams = [];
if ($idFilter > 0) { $where[] = "o.id = ?";       $whereParams[] = $idFilter; }
if ($qFilter)      { $where[] = "o.name LIKE ?";   $whereParams[] = '%' . $qFilter . '%'; }
switch ($accessFilter) {
    case 'request':    $where[] = "o.require_approval = 1"; break;
    case 'all_access': $where[] = "o.require_approval = 0"; break;
}

PrivateOffer::ensureTables();
// Private in-house offers are hidden unless this affiliate is explicitly
// granted on private_offer_access.
$where[] = "(COALESCE(o.visibility,'public') != 'private' OR poa.id IS NOT NULL)";
$offers = Database::fetchAll(
    "SELECT o.*,
            ao.status as access_status,
            ao.id     as access_id,
            ao.custom_payout,
            acp.payout as adv_custom_payout,
            poa.id AS private_grant_id,
            (SELECT COUNT(*) FROM clicks c WHERE c.offer_id=o.id AND c.affiliate_id=? AND DATE(c.clicked_at)=CURDATE()) as today_clicks
     FROM offers o
     LEFT JOIN affiliate_offers ao    ON ao.offer_id  = o.id AND ao.affiliate_id  = ?
     LEFT JOIN aff_custom_payouts acp ON acp.offer_id = o.id AND acp.affiliate_id = ?
     LEFT JOIN private_offer_access poa ON poa.offer_id = o.id AND poa.affiliate_id = ?
     WHERE " . implode(' AND ', $where) . "
       AND (ao.status IS NULL OR ao.status NOT IN ('blocked','rejected'))
     ORDER BY o.created_at DESC",
    array_merge([$affId, $affId, $affId, $affId], $whereParams)
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

$aff = Database::fetchOne("SELECT affiliate_code FROM affiliates WHERE id=?", [$affId]);

// Load active tracking domains for the domain selector
$trackingDomains = Database::fetchAll(
    "SELECT * FROM tracking_domains WHERE is_active=1 ORDER BY is_default DESC, created_at ASC"
);
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

require BASE_PATH . '/views/affiliate/inhouse/index.php';
