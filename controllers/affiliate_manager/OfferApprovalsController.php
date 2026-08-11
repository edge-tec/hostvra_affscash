<?php
Auth::check('affiliate_manager');
require_once BASE_PATH . '/core/NotificationHelper.php';

if (!Auth::hasPermission('view_affiliates')) {
    Helpers::flash('error', 'You do not have permission to view offer approvals.');
    Helpers::redirect('/affiliate_manager/dashboard');
}

$pageTitle = 'Offer Approval Requests';
$affIds    = Auth::managerAffiliateIds();

// No managed affiliates — nothing to show
if (empty($affIds)) {
    $requests     = [];
    $pendingCount = 0;
    $allOffers    = [];
    $filterStatus = 'pending';
    $filterOffer  = '';
    $filterAff    = '';
    require BASE_PATH . '/views/affiliate_manager/offer_approvals.php';
    exit;
}

$inSql    = implode(',', array_fill(0, count($affIds), '?'));

// ── POST: approve or reject ───────────────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $action  = Helpers::postRaw('action');
    $affId   = (int)Helpers::postRaw('affiliate_id');
    $offerId = (int)Helpers::postRaw('offer_id');

    // Security: manager can only act on their own affiliates
    if (in_array($action, ['approve','reject'], true) && $affId && $offerId
        && in_array($affId, array_map('intval', $affIds), true)) {

        if ($action === 'reject_duplicates') {
            $aff = Database::fetchOne(
                "SELECT u.first_name, u.last_name, u.email FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
                [$affId]
            );
            if ($aff) {
                $fullName = trim($aff['first_name'] . ' ' . $aff['last_name']);
                $dupApps = Database::fetchAll(
                    "SELECT ao.id FROM affiliate_offers ao
                     JOIN affiliates af ON af.id = ao.affiliate_id
                     JOIN users u ON u.id = af.user_id
                     WHERE ao.offer_id = ? AND ao.status = 'pending' AND ao.affiliate_id != ?
                       AND (LOWER(CONCAT(u.first_name,' ',u.last_name)) = LOWER(?) OR u.email = ?)",
                    [$offerId, $affId, $fullName, $aff['email']]
                );
                foreach ($dupApps as $da) {
                    Database::update('affiliate_offers', ['status' => 'rejected'], 'id=?', [$da['id']]);
                }
                Helpers::flash('success', 'Rejected ' . count($dupApps) . ' duplicate application(s) for this offer.');
            }
            Helpers::redirect('/affiliate_manager/offer-approvals');
        }

        $ao = Database::fetchOne(
            "SELECT ao.*, o.name as offer_name
             FROM affiliate_offers ao
             JOIN offers o ON o.id = ao.offer_id
             JOIN affiliates af ON af.id = ao.affiliate_id
             JOIN users u ON u.id = af.user_id
             WHERE ao.affiliate_id=? AND ao.offer_id=?",
            [$affId, $offerId]
        );

        if ($ao) {
            if ($action === 'approve') {
                Database::update('affiliate_offers',
                    ['status' => 'approved', 'approved_at' => date('Y-m-d H:i:s'), 'approved_by' => Auth::id()],
                    'affiliate_id=? AND offer_id=?',
                    [$affId, $offerId]
                );
                $affUser = Database::fetchOne(
                    "SELECT u.id as user_id, u.email, u.first_name, u.last_name FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
                    [$affId]
                );
                if ($affUser) {
                    NotificationHelper::notifyUser(
                        (int)$affUser['user_id'],
                        'Offer Access Approved',
                        'Your access to offer "' . $ao['offer_name'] . '" has been approved.',
                        'offer', '/affiliate/offers', [],
                        'offer_approved', 'offers'
                    );
                    try {
                        Mailer::sendEvent($affUser['email'], $affUser['first_name'].' '.$affUser['last_name'], 'offer_approved', [
                            'name'       => $affUser['first_name'].' '.$affUser['last_name'],
                            'offer_name' => $ao['offer_name'],
                            'app_url'    => rtrim(Config::get('config','app.url') ?? '', '/'),
                            'site_name'  => Config::get('config','app.name') ?? 'AffiliateTracker',
                        ]);
                    } catch (Exception $e) {}
                }
                Helpers::flash('success', 'Access to "'.$ao['offer_name'].'" approved.');
            } else {
                Database::update('affiliate_offers',
                    ['status' => 'rejected'],
                    'affiliate_id=? AND offer_id=?',
                    [$affId, $offerId]
                );
                $affUser = Database::fetchOne(
                    "SELECT u.id as user_id, u.email, u.first_name, u.last_name FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
                    [$affId]
                );
                if ($affUser) {
                    NotificationHelper::notifyUser(
                        (int)$affUser['user_id'],
                        'Offer Access Rejected',
                        'Your access request for offer "'.$ao['offer_name'].'" was not approved.',
                        'offer', '/affiliate/offers', [],
                        'offer_rejected', 'offers'
                    );
                    try {
                        Mailer::sendEvent($affUser['email'], $affUser['first_name'].' '.$affUser['last_name'], 'offer_rejected', [
                            'name'       => $affUser['first_name'].' '.$affUser['last_name'],
                            'offer_name' => $ao['offer_name'],
                            'app_url'    => rtrim(Config::get('config','app.url') ?? '', '/'),
                            'site_name'  => Config::get('config','app.name') ?? 'AffiliateTracker',
                        ]);
                    } catch (Exception $e) {}
                }
                Helpers::flash('success', 'Request rejected.');
            }
        }
    }

    Helpers::redirect('/affiliate_manager/offer-approvals');
}

// ── Filters ───────────────────────────────────────────────────────────────
$filterStatus = Helpers::get('status', 'pending');
$filterOffer  = Helpers::get('offer_id');
$filterAff    = Helpers::get('aff');

$where  = ["ao.affiliate_id IN ($inSql)"];  // always scope to managed affiliates
$params = $affIds;

$validStatuses = ['pending','approved','rejected','all'];
if ($filterStatus && $filterStatus !== 'all' && in_array($filterStatus, $validStatuses)) {
    $where[]  = "ao.status = ?";
    $params[] = $filterStatus;
}
if ($filterOffer) {
    $where[]  = "ao.offer_id = ?";
    $params[] = (int)$filterOffer;
}
if ($filterAff) {
    $where[]  = "(u.first_name LIKE ? OR u.last_name LIKE ? OR af.affiliate_code LIKE ? OR u.email LIKE ?)";
    $params   = array_merge($params, ['%'.$filterAff.'%','%'.$filterAff.'%','%'.$filterAff.'%','%'.$filterAff.'%']);
}

$whereStr = 'WHERE ' . implode(' AND ', $where);

// ── Fetch requests ────────────────────────────────────────────────────────
$requests = Database::fetchAll(
    "SELECT
        ao.id          as ao_id,
        ao.affiliate_id,
        ao.offer_id,
        ao.status,
        ao.notes       as promotion_description,
        ao.approved_at as requested_at,
        ao.approved_at,
        o.name         as offer_name,
        o.payout_amount,
        o.payout_type,
        o.category     as offer_category,
        CONCAT(u.first_name,' ',u.last_name) as affiliate_name,
        u.email        as affiliate_email,
        u.phone        as affiliate_phone,
        u.created_at   as affiliate_joined,
        af.affiliate_code,
        u.country,
        af.traffic_sources,
        (SELECT COUNT(*) FROM clicks cl WHERE cl.affiliate_id=ao.affiliate_id AND cl.status='valid') as total_clicks,
        (SELECT COUNT(*) FROM conversions cv WHERE cv.affiliate_id=ao.affiliate_id AND cv.status='approved' AND cv.is_hidden=0 AND (cv.hide_reason IS NULL OR cv.hide_reason NOT LIKE '%traffic_back%') AND NOT EXISTS (SELECT 1 FROM clicks _ck_tb WHERE _ck_tb.click_id = cv.click_id AND _ck_tb.source = 'traffic_back') AND NOT EXISTS (SELECT 1 FROM traffic_back_logs _tbl_tb WHERE _tbl_tb.click_id = cv.click_id)) as total_conversions
     FROM affiliate_offers ao
     JOIN offers o ON o.id = ao.offer_id
     JOIN affiliates af ON af.id = ao.affiliate_id
     JOIN users u ON u.id = af.user_id
     $whereStr
     ORDER BY ao.approved_at DESC, ao.id DESC",
    $params
) ?: [];

foreach ($requests as &$req) {
    $dupApps = Database::fetchAll(
        "SELECT u.email, af.affiliate_code, ao.status
         FROM affiliate_offers ao
         JOIN affiliates af ON af.id = ao.affiliate_id
         JOIN users u ON u.id = af.user_id
         WHERE ao.offer_id = ? AND ao.id != ?
           AND (
               LOWER(CONCAT(u.first_name,' ',u.last_name)) = LOWER(?)
               OR u.email = ?
               OR (u.phone IS NOT NULL AND u.phone != '' AND u.phone = ?)
           )",
        [$req['offer_id'], $req['ao_id'], $req['affiliate_name'], $req['affiliate_email'], $req['affiliate_phone'] ?? '']
    ) ?: [];

    $req['duplicate_count'] = count($dupApps);
    $req['duplicate_info']  = implode(', ', array_map(function($d) {
        return $d['email'] . ' (' . $d['affiliate_code'] . ' - ' . strtoupper($d['status']) . ')';
    }, $dupApps));
}
unset($req);

// Pending count — scoped to this manager's affiliates only
$pendingCount = Database::fetchOne(
    "SELECT COUNT(*) as cnt FROM affiliate_offers
     WHERE status='pending' AND affiliate_id IN ($inSql)",
    $affIds
)['cnt'] ?? 0;

// Offers used by managed affiliates (for filter dropdown)
$allOffers = Database::fetchAll(
    "SELECT DISTINCT o.id, o.name FROM offers o
     JOIN affiliate_offers ao ON ao.offer_id=o.id
     WHERE ao.affiliate_id IN ($inSql)
     ORDER BY o.name",
    $affIds
);

require BASE_PATH . '/views/affiliate_manager/offer_approvals.php';
