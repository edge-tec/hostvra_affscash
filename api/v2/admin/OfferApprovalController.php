<?php
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';
require_once BASE_PATH . '/core/Mailer.php';
require_once BASE_PATH . '/core/Config.php';

class AdminOfferApprovalController {

    public static function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
            self::listApprovals();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'review') {
            self::reviewApproval();
        } else {
            Helpers::json(['success' => false, 'message' => 'Invalid action.'], 400);
        }
    }

    private static function listApprovals() {
        Auth::check('admin');

        $filterStatus = $_GET['status'] ?? 'pending';
        $filterOffer  = $_GET['offer_id'] ?? null;
        $filterAff    = $_GET['aff'] ?? null;

        $where  = ["1=1"];
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
            $params   = array_merge($params, ['%'.$filterAff.'%','%'.$filterAff.'%','%'.$filterAff.'%','%'.$filterAff.'%']);
        }

        $whereStr = 'WHERE ' . implode(' AND ', $where);

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
                (SELECT COUNT(*) FROM conversions cv WHERE cv.affiliate_id=ao.affiliate_id AND cv.status='approved' AND cv.is_hidden=0) as total_conversions
             FROM affiliate_offers ao
             JOIN offers o ON o.id = ao.offer_id
             JOIN affiliates af ON af.id = ao.affiliate_id
             JOIN users u ON u.id = af.user_id
             $whereStr
             ORDER BY ao.approved_at DESC",
            $params
        );

        $pendingCount = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM affiliate_offers WHERE status='pending'"
        )['cnt'] ?? 0;

        $allOffers = Database::fetchAll(
            "SELECT DISTINCT o.id, o.name FROM offers o
             JOIN affiliate_offers ao ON ao.offer_id=o.id
             ORDER BY o.name"
        );

        Helpers::json([
            'success' => true,
            'requests' => $requests,
            'all_offers' => $allOffers,
            'pending_count' => $pendingCount
        ]);
    }

    private static function reviewApproval() {
        Auth::check('admin');

        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $affId = (int)($data['affiliate_id'] ?? 0);
        $offerId = (int)($data['offer_id'] ?? 0);
        $action = $data['review_action'] ?? '';

        if (in_array($action, ['approve', 'reject'], true) && $affId && $offerId) {

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
                    
                    self::sendNotification($affId, $ao['offer_name'], true);
                    
                    Helpers::json(['success' => true, 'message' => 'Access to "' . $ao['offer_name'] . '" approved.']);
                } else {
                    Database::update('affiliate_offers',
                        ['status' => 'rejected'],
                        'affiliate_id=? AND offer_id=?',
                        [$affId, $offerId]
                    );
                    
                    self::sendNotification($affId, $ao['offer_name'], false);

                    Helpers::json(['success' => true, 'message' => 'Request rejected.']);
                }
            } else {
                Helpers::json(['success' => false, 'message' => 'Request not found.'], 404);
            }
        } else {
            Helpers::json(['success' => false, 'message' => 'Invalid parameters.'], 400);
        }
    }
    
    private static function sendNotification($affId, $offerName, $isApproved) {
        require_once BASE_PATH . '/core/NotificationHelper.php';
        $affUser = Database::fetchOne(
            "SELECT u.id as user_id, u.email, u.first_name, u.last_name FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
            [$affId]
        );
        if ($affUser) {
            $title = $isApproved ? 'Offer Access Approved' : 'Offer Access Rejected';
            $message = $isApproved
                ? 'Your access to offer "' . $offerName . '" has been approved.'
                : 'Your access request for offer "' . $offerName . '" was not approved.';
            $notifType = $isApproved ? 'offer_approved' : 'offer_rejected';

            NotificationHelper::notifyUser(
                (int)$affUser['user_id'], $title, $message,
                'offer', '/affiliate/offers', [],
                $notifType, 'offers'
            );

            try {
                $emailEvent = $isApproved ? 'offer_approved' : 'offer_rejected';
                Mailer::sendEvent($affUser['email'], $affUser['first_name'].' '.$affUser['last_name'], $emailEvent, [
                    'name'       => $affUser['first_name'].' '.$affUser['last_name'],
                    'offer_name' => $offerName,
                    'app_url'    => rtrim(Config::get('config','app.url') ?? '', '/'),
                    'site_name'  => Config::get('config','app.name') ?? 'AffiliateTracker',
                ]);
            } catch (Exception $e) {}
        }
    }
}

AdminOfferApprovalController::handleRequest();
