<?php
Auth::check('admin');
require_once BASE_PATH . '/core/NotificationHelper.php';
$pageTitle = 'Offer Approval Requests';

// ── POST: approve or reject a request ────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $action  = Helpers::postRaw('action');
    $affId   = (int)Helpers::postRaw('affiliate_id');
    $offerId = (int)Helpers::postRaw('offer_id');

    if (in_array($action, ['approve','reject'], true) && $affId && $offerId) {
        $ao = Database::fetchOne(
            "SELECT ao.*, o.name as offer_name, o.payout_type, o.payout_amount, o.revenue_amount, u.email, u.first_name, u.last_name
             FROM affiliate_offers ao
             JOIN offers o ON o.id = ao.offer_id
             JOIN affiliates af ON af.id = ao.affiliate_id
             JOIN users u ON u.id = af.user_id
             WHERE ao.affiliate_id=? AND ao.offer_id=?",
            [$affId, $offerId]
        );

        if ($ao) {
            $revsharePct = Mailer::calculateRevSharePercentage($ao);
            $revshareStr = 'RevShare: ' . $revsharePct;

            if ($action === 'approve') {
                Database::update('affiliate_offers',
                    ['status' => 'approved', 'approved_at' => date('Y-m-d H:i:s'), 'approved_by' => Auth::id()],
                    'affiliate_id=? AND offer_id=?',
                    [$affId, $offerId]
                );
                // Notify affiliate via NotificationHelper (push + DB)
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
                        Mailer::sendEvent($affUser['email'], $affUser['first_name'] . ' ' . $affUser['last_name'], 'offer_approved', [
                            'name'            => $affUser['first_name'] . ' ' . $affUser['last_name'],
                            'offer_name'      => $ao['offer_name'],
                            'revshare'        => $revshareStr,
                            'revshare_percent'=> $revsharePct,
                            'app_url'         => rtrim(Config::get('config', 'app.url') ?? '', '/'),
                            'site_name'       => Config::get('config', 'app.name') ?? 'AffiliateTracker',
                        ]);
                    } catch (Exception $e) {}
                }
                Helpers::flash('success', 'Access to "' . $ao['offer_name'] . '" approved.');
            } else {
                Database::update('affiliate_offers',
                    ['status' => 'rejected'],
                    'affiliate_id=? AND offer_id=?',
                    [$affId, $offerId]
                );
                // Notify affiliate via NotificationHelper (push + DB)
                $affUser = Database::fetchOne(
                    "SELECT u.id as user_id, u.email, u.first_name, u.last_name FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
                    [$affId]
                );
                if ($affUser) {
                    NotificationHelper::notifyUser(
                        (int)$affUser['user_id'],
                        'Offer Access Rejected',
                        'Your access request for offer "' . $ao['offer_name'] . '" was not approved.',
                        'offer', '/affiliate/offers', [],
                        'offer_rejected', 'offers'
                    );
                    try {
                        Mailer::sendEvent($affUser['email'], $affUser['first_name'] . ' ' . $affUser['last_name'], 'offer_rejected', [
                            'name'            => $affUser['first_name'] . ' ' . $affUser['last_name'],
                            'offer_name'      => $ao['offer_name'],
                            'revshare'        => $revshareStr,
                            'revshare_percent'=> $revsharePct,
                            'app_url'         => rtrim(Config::get('config', 'app.url') ?? '', '/'),
                            'site_name'       => Config::get('config', 'app.name') ?? 'AffiliateTracker',
                        ]);
                    } catch (Exception $e) {}
                }
                Helpers::flash('success', 'Request rejected.');
            }
        }
    }

    Helpers::redirect('/admin/offer-approvals');
}

// ── Filters ───────────────────────────────────────────────────────────────
$filterStatus = Helpers::get('status', 'pending');
$filterOffer  = Helpers::get('offer_id');
$filterAff    = Helpers::get('aff');

$where  = [];
$params = [];

$validStatuses = ['pending', 'approved', 'rejected', 'all'];
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
    $params   = array_merge($params, ['%'.$filterAff.'%', '%'.$filterAff.'%', '%'.$filterAff.'%', '%'.$filterAff.'%']);
}

$whereStr = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

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
        u.created_at   as affiliate_joined,
        af.affiliate_code,
        u.country,
        af.traffic_sources,
        (SELECT COUNT(*) FROM clicks cl WHERE cl.affiliate_id=ao.affiliate_id AND cl.status='valid') as total_clicks,
        (SELECT COUNT(*) FROM conversions cv WHERE cv.affiliate_id=ao.affiliate_id AND cv.status='approved') as total_conversions
     FROM affiliate_offers ao
     JOIN offers o ON o.id = ao.offer_id
     JOIN affiliates af ON af.id = ao.affiliate_id
     JOIN users u ON u.id = af.user_id
     $whereStr
     ORDER BY ao.approved_at DESC",
    $params
);

// Pending count for badge
$pendingCount = Database::fetchOne(
    "SELECT COUNT(*) as cnt FROM affiliate_offers WHERE status='pending'",
    []
)['cnt'] ?? 0;

// Offer list for filter dropdown
$allOffers = Database::fetchAll(
    "SELECT DISTINCT o.id, o.name FROM offers o
     JOIN affiliate_offers ao ON ao.offer_id=o.id
     ORDER BY o.name"
);

require BASE_PATH . '/views/admin/offer_approvals/index.php';
