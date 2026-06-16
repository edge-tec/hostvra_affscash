<?php
header('Content-Type: application/json');

try {
    Auth::check('affiliate');

    $action = $_GET['action'] ?? 'list';
    $affId = Auth::affiliateId();

    PrivateOffer::ensureTables();

    if ($action === 'list') {
        // List all offers for this affiliate
        $query = "SELECT o.id, o.name, o.description, o.payout_type, o.payout, o.preview_url, ao.custom_payout
                  FROM offers o
                  JOIN affiliate_offers ao ON ao.offer_id  = o.id
                  LEFT JOIN private_offer_access poa ON poa.offer_id = o.id AND poa.affiliate_id = ?
                  WHERE ao.affiliate_id=? AND ao.status='approved' AND o.status='active'
                    AND (COALESCE(o.visibility,'public') != 'private' OR poa.id IS NOT NULL)";

        try {
            $offers = Database::fetchAll($query, [$affId, $affId]);
        } catch (PDOException $e) {
            $query = "SELECT o.id, o.name, o.description, o.payout_type, o.payout, o.preview_url, ao.custom_payout
                      FROM offers o
                      JOIN affiliate_offers ao ON ao.offer_id  = o.id
                      WHERE ao.affiliate_id=? AND ao.status='approved' AND o.status='active'";
            $offers = Database::fetchAll($query, [$affId]);
        }

        $offersList = [];
        foreach ($offers as $offer) {
            $payout = $offer['custom_payout'] !== null ? $offer['custom_payout'] : $offer['payout'];
            $offersList[] = [
                'id' => $offer['id'],
                'name' => $offer['name'],
                'description' => $offer['description'],
                'payout_type' => $offer['payout_type'],
                'payout' => (float)$payout,
                'preview_url' => $offer['preview_url']
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
        
        $affiliateCode = Database::fetchOne("SELECT affiliate_code FROM affiliates WHERE id=?", [$affId])['affiliate_code'] ?? '';
        
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
                'countries' => $offer['countries'],
                'devices' => $offer['devices'],
                'tracking_link' => $trackingLink
            ]
        ]);
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
