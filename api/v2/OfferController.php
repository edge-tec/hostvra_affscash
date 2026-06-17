<?php
header('Content-Type: application/json');

try {
    Auth::check('affiliate');

    $action = $_GET['action'] ?? 'list';
    $affId = Auth::affiliateId();

    PrivateOffer::ensureTables();

    if ($action === 'list') {
        $qFilter         = $_GET['q'] ?? '';
        $catFilter       = $_GET['category'] ?? '';
        $typeFilter      = $_GET['payout_type'] ?? '';
        $offerTypeFilter = $_GET['offer_type'] ?? '';
        $countryFilter   = $_GET['country'] ?? '';
        $deviceFilter    = $_GET['device'] ?? '';
        $accessFilter    = $_GET['access_filter'] ?? '';
        $inHouse         = filter_var($_GET['in_house'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $offerWhere = [
            "o.status='active'",
            "(COALESCE(o.visibility,'public') != 'private' OR poa.id IS NOT NULL)"
        ];
        
        if ($inHouse) {
            $offerWhere[] = "o.is_inhouse = 1";
        } else {
            $offerWhere[] = "(o.is_inhouse IS NULL OR o.is_inhouse = 0)";
        }
        $offerParams = [];

        if ($qFilter)         { $offerWhere[] = "o.name LIKE ?";        $offerParams[] = '%'.$qFilter.'%'; }
        if ($catFilter)       { $offerWhere[] = "o.category = ?";       $offerParams[] = $catFilter; }
        if ($typeFilter)      { $offerWhere[] = "o.payout_type = ?";    $offerParams[] = $typeFilter; }
        if ($offerTypeFilter) { $offerWhere[] = "o.offer_type = ?";     $offerParams[] = $offerTypeFilter; }
        if ($countryFilter)   { $offerWhere[] = "JSON_CONTAINS(o.geo_targeting, JSON_QUOTE(?))"; $offerParams[] = $countryFilter; }
        if ($deviceFilter)    { $offerWhere[] = "JSON_CONTAINS(o.device_targeting, JSON_QUOTE(?))"; $offerParams[] = $deviceFilter; }
        
        switch ($accessFilter) {
            case 'request':    $offerWhere[] = "o.require_approval = 1"; break;
            case 'all_access': $offerWhere[] = "o.require_approval = 0"; break;
        }
        $offerWhereStr = implode(' AND ', $offerWhere);

        try {
            $offers = Database::fetchAll(
                "SELECT o.id, o.name, o.description, o.category, o.offer_type, o.require_approval, 
                 o.geo_targeting as countries, o.device_targeting as devices,
                 o.payout_type, o.payout_amount as payout, o.preview_url, 
                 ao.status as access_status, ao.custom_payout
                 FROM offers o
                 LEFT JOIN affiliate_offers ao ON ao.offer_id = o.id AND ao.affiliate_id = ?
                 LEFT JOIN private_offer_access poa ON poa.offer_id = o.id AND poa.affiliate_id = ?
                 WHERE $offerWhereStr
                   AND (ao.status IS NULL OR ao.status NOT IN ('blocked','rejected'))
                 ORDER BY o.created_at DESC",
                array_merge([$affId, $affId], $offerParams)
            );
        } catch (PDOException $e) {
            $offers = [];
        }

        $offersList = [];
        foreach ($offers as $offer) {
            $payout = $offer['custom_payout'] !== null ? $offer['custom_payout'] : $offer['payout'];
            $offersList[] = [
                'id' => $offer['id'],
                'name' => $offer['name'],
                'description' => $offer['description'],
                'category' => $offer['category'],
                'offer_type' => $offer['offer_type'],
                'require_approval' => (int)$offer['require_approval'],
                'countries' => $offer['countries'],
                'devices' => $offer['devices'],
                'payout_type' => $offer['payout_type'],
                'payout' => (float)$payout,
                'preview_url' => $offer['preview_url'],
                'access_status' => $offer['access_status']
            ];
        }

        echo json_encode([
            'success' => true,
            'offers' => $offersList
        ]);

    } elseif ($action === 'details') {
        $offerId = $_GET['id'] ?? 0;
        
        // Check access
        try {
            $offer = Database::fetchOne(
                "SELECT o.*, ao.custom_payout, ao.status as approval_status 
                 FROM offers o
                 JOIN affiliate_offers ao ON ao.offer_id = o.id
                 LEFT JOIN private_offer_access poa ON poa.offer_id = o.id AND poa.affiliate_id = ?
                 WHERE o.id=? AND ao.affiliate_id=? AND o.status='active'
                   AND (COALESCE(o.visibility,'public') != 'private' OR poa.id IS NOT NULL)",
                [$affId, $offerId, $affId]
            );
        } catch (PDOException $e) {
            $offer = Database::fetchOne(
                "SELECT o.*, ao.custom_payout, ao.status as approval_status 
                 FROM offers o
                 JOIN affiliate_offers ao ON ao.offer_id = o.id
                 WHERE o.id=? AND ao.affiliate_id=? AND o.status='active'",
                [$offerId, $affId]
            );
        }

        if (!$offer) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Offer not found or no access']);
            exit;
        }

        $payout = $offer['custom_payout'] !== null ? $offer['custom_payout'] : $offer['payout'];
        
        $affRow = Database::fetchOne("SELECT affiliate_code FROM affiliates WHERE id=?", [$affId]);
        $affiliateCode = $affRow ? ($affRow['affiliate_code'] ?? '') : '';
        
        $appUrl = Config::get('config', 'app.url');
        $trackingLink = rtrim($appUrl, '/') . '/offer/' . $offerId . '?aff_id=' . $affiliateCode;

        echo json_encode([
            'success' => true,
            'offer' => [
                'id' => $offer['id'],
                'name' => $offer['name'],
                'description' => $offer['description'],
                'payout_type' => $offer['payout_type'],
                'payout' => (float)$payout,
                'preview_url' => $offer['preview_url'],
                'countries' => $offer['geo_targeting'] ?? $offer['countries'] ?? null,
                'devices' => $offer['device_targeting'] ?? $offer['devices'] ?? null,
                'tracking_link' => $trackingLink
            ]
        ]);
    } elseif ($action === 'apply') {
        $json = json_decode(file_get_contents('php://input'), true);
        $offerId = (int)($json['offer_id'] ?? 0);
        $promoDesc = trim($json['promotion_description'] ?? '');

        $_isLocked = function (?array $existing): bool {
            $s = $existing['status'] ?? '';
            return $s === 'rejected' || $s === 'blocked';
        };
        $_canReapply = function (?array $existing): bool {
            return !$existing || ($existing['status'] ?? '') === 'removed';
        };

        $offer = Database::fetchOne("SELECT * FROM offers WHERE id=? AND status='active'", [$offerId]);
        if (!$offer) {
            echo json_encode(['success' => false, 'error' => 'Offer not found']);
            exit;
        }

        if (PrivateOffer::isPrivate($offer) && !PrivateOffer::hasAccess($offerId, $affId)) {
            echo json_encode(['success' => false, 'error' => 'No access to private offer']);
            exit;
        }

        $existing = Database::fetchOne("SELECT id, status FROM affiliate_offers WHERE affiliate_id=? AND offer_id=?", [$affId, $offerId]);
        
        if ($_isLocked($existing)) {
            echo json_encode(['success' => false, 'error' => 'Offer locked or rejected.']);
            exit;
        }

        if ($_canReapply($existing)) {
            $status = $offer['require_approval'] ? 'pending' : 'approved';
            if ($existing) {
                Database::update('affiliate_offers', [
                    'status'      => $status,
                    'notes'       => $promoDesc ?: null,
                    'approved_at' => $status === 'approved' ? date('Y-m-d H:i:s') : null,
                    'approved_by' => $status === 'approved' ? 0 : null,
                ], 'affiliate_id=? AND offer_id=?', [$affId, $offerId]);
            } else {
                Database::insert('affiliate_offers', [
                    'affiliate_id' => $affId,
                    'offer_id'     => $offerId,
                    'status'       => $status,
                    'notes'        => $promoDesc ?: null,
                    'approved_at'  => $status === 'approved' ? date('Y-m-d H:i:s') : null,
                    'approved_by'  => $status === 'approved' ? 0 : null,
                ]);
            }
            
            if ($status === 'pending') {
                $me = Auth::currentUser();
                Database::insert('notifications', ['user_id'=>null,'target_role'=>'admin','type'=>'info','title'=>'Offer Access Request','message'=>'An affiliate is requesting access to offer: '.$offer['name'],'link'=>'/admin/affiliates']);
                // Email logic omitted for simplicity in API
            }

            echo json_encode([
                'success' => true,
                'message' => $status === 'approved' ? 'Offer access granted!' : 'Access request submitted.',
                'new_status' => $status
            ]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Already applied or approved.']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Action not found']);
    }
} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
