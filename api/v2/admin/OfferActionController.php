<?php
header('Content-Type: application/json');

Auth::check('admin');

try {
    $action = Helpers::get('action');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true) ?? $_POST;

    if ($action === 'create') {
        $name     = $data['name'] ?? '';
        $advId    = (int)($data['advertiser_id'] ?? 0);
        $payout   = (float)($data['payout'] ?? 0);
        $revenue  = (float)($data['revenue'] ?? 0);
        $type     = $data['payout_type'] ?? 'CPA';
        $status   = $data['status'] ?? 'active';
        $offerUrl = $data['offer_url'] ?? '';
        
        $geos     = $data['geo_targeting'] ?? [];
        $devices  = $data['device_targeting'] ?? [];
        $dailyCap = (int)($data['daily_cap'] ?? 0);
        $totalCap = (int)($data['total_cap'] ?? 0);
        $visibility = $data['visibility'] ?? 'public';
        $category = $data['category'] ?? '';
        $offerType = $data['offer_type'] ?? null;
        $description = $data['description'] ?? '';
        $requireApproval = !empty($data['require_approval']) ? 1 : 0;
        $isInHouse = !empty($data['is_inhouse']) ? 1 : 0;

        if (!$name) throw new Exception('Offer name is required.');
        if (!$isInHouse && !$advId) throw new Exception('Advertiser is required for external offers.');
        if (!$offerUrl) throw new Exception('Offer URL is required.');

        if (Database::fetchOne("SELECT id FROM offers WHERE name=? AND status != 'deleted'", [$name])) {
            throw new Exception('An offer with this name already exists.');
        }

        $id = Database::insert('offers', [
            'advertiser_id'      => $advId,
            'name'               => $name,
            'description'        => $description,
            'offer_url'          => $offerUrl,
            'category'           => $category,
            'geo_targeting'      => $geos ? json_encode($geos) : null,
            'device_targeting'   => $devices ? json_encode($devices) : null,
            'offer_type'         => $offerType,
            'payout_type'        => $type,
            'payout_amount'      => $payout,
            'revenue_amount'     => $revenue,
            'daily_cap'          => $dailyCap,
            'total_cap'          => $totalCap,
            'status'             => $status,
            'visibility'         => $visibility,
            'require_approval'   => $requireApproval,
            'is_inhouse'         => $isInHouse,
            'created_by'         => Auth::id()
        ]);

        echo json_encode(['success' => true, 'message' => 'Offer created successfully', 'id' => $id]);

    } elseif ($action === 'edit') {
        $id = (int)($data['id'] ?? 0);
        if (!$id) throw new Exception('Offer ID is required.');

        $name     = $data['name'] ?? '';
        $advId    = (int)($data['advertiser_id'] ?? 0);
        $payout   = (float)($data['payout'] ?? 0);
        $revenue  = (float)($data['revenue'] ?? 0);
        $type     = $data['payout_type'] ?? 'CPA';
        $status   = $data['status'] ?? 'active';
        $offerUrl = $data['offer_url'] ?? '';
        
        $geos     = $data['geo_targeting'] ?? [];
        $devices  = $data['device_targeting'] ?? [];
        $dailyCap = (int)($data['daily_cap'] ?? 0);
        $totalCap = (int)($data['total_cap'] ?? 0);
        $visibility = $data['visibility'] ?? 'public';
        $category = $data['category'] ?? '';
        $offerType = $data['offer_type'] ?? null;
        $description = $data['description'] ?? '';
        $requireApproval = !empty($data['require_approval']) ? 1 : 0;
        $isInHouse = !empty($data['is_inhouse']) ? 1 : 0;

        if (!$name) throw new Exception('Offer name is required.');
        if (!$isInHouse && !$advId) throw new Exception('Advertiser is required for external offers.');
        if (!$offerUrl) throw new Exception('Offer URL is required.');

        if (Database::fetchOne("SELECT id FROM offers WHERE name=? AND id != ? AND status != 'deleted'", [$name, $id])) {
            throw new Exception('An offer with this name already exists.');
        }

        Database::update('offers', [
            'advertiser_id'      => $advId,
            'name'               => $name,
            'description'        => $description,
            'offer_url'          => $offerUrl,
            'category'           => $category,
            'geo_targeting'      => $geos ? json_encode($geos) : null,
            'device_targeting'   => $devices ? json_encode($devices) : null,
            'offer_type'         => $offerType,
            'payout_type'        => $type,
            'payout_amount'      => $payout,
            'revenue_amount'     => $revenue,
            'daily_cap'          => $dailyCap,
            'total_cap'          => $totalCap,
            'status'             => $status,
            'visibility'         => $visibility,
            'require_approval'   => $requireApproval,
            'is_inhouse'         => $isInHouse
        ], 'id=?', [$id]);

        echo json_encode(['success' => true, 'message' => 'Offer updated successfully']);

    } elseif ($action === 'update_status') {
        $id = (int)($data['id'] ?? 0);
        $status = $data['status'] ?? '';

        if (!$id || !in_array($status, ['active', 'paused', 'expired', 'pending'])) {
            throw new Exception('Invalid status or ID.');
        }

        Database::update('offers', ['status' => $status], 'id=?', [$id]);
        echo json_encode(['success' => true, 'message' => 'Status updated']);

    } elseif ($action === 'delete') {
        $id = (int)($data['id'] ?? 0);
        if (!$id) throw new Exception('Offer ID required.');

        Database::update('offers', ['status' => 'deleted'], 'id=?', [$id]);
        Database::query("DELETE FROM affiliate_offers WHERE offer_id=?", [$id]);
        Database::query("DELETE FROM smartlink_offers WHERE offer_id=?", [$id]);
        Database::query("DELETE FROM offer_links WHERE offer_id=?", [$id]);
        echo json_encode(['success' => true, 'message' => 'Offer deleted']);

    } else {
        throw new Exception('Unknown action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
