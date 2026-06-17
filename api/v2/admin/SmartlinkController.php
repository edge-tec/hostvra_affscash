<?php
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';
require_once BASE_PATH . '/core/Mailer.php';

Auth::requireRole('admin');

$action = Helpers::get('action') ?: 'list';

if ($action === 'list') {
    $smartlinks = Database::fetchAll(
        "SELECT sl.*, COUNT(DISTINCT so.id) as offer_count,
                COALESCE(MAX(cl_stats.total_clicks), 0)  as total_clicks,
                COALESCE(MAX(cv_stats.total_convs),  0)  as total_convs
         FROM smartlinks sl
         LEFT JOIN smartlink_offers so ON so.smartlink_id = sl.id
         LEFT JOIN (
             SELECT smartlink_id, COUNT(*) as total_clicks
             FROM clicks WHERE smartlink_id IS NOT NULL GROUP BY smartlink_id
         ) cl_stats ON cl_stats.smartlink_id = sl.id
         LEFT JOIN (
             SELECT cl.smartlink_id, COUNT(*) as total_convs
             FROM conversions cv
             JOIN clicks cl ON cl.click_id = cv.click_id
             WHERE cl.smartlink_id IS NOT NULL
             GROUP BY cl.smartlink_id
         ) cv_stats ON cv_stats.smartlink_id = sl.id
         GROUP BY sl.id ORDER BY sl.created_at DESC"
    ) ?: [];

    $pendingCount = Database::fetchOne("SELECT COUNT(*) as cnt FROM smartlink_requests WHERE status='pending'")['cnt'] ?? 0;

    Helpers::jsonResponse([
        'status' => 'success',
        'data' => [
            'smartlinks' => $smartlinks,
            'pending_requests_count' => (int)$pendingCount
        ]
    ]);
}

if ($action === 'requests') {
    $requests = Database::fetchAll(
        "SELECT sr.*, sl.name as smartlink_name, sl.slug,
                CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code, u.email as aff_email
         FROM smartlink_requests sr
         JOIN smartlinks sl ON sl.id=sr.smartlink_id
         JOIN affiliates af ON af.id=sr.affiliate_id
         JOIN users u ON u.id=af.user_id
         ORDER BY sr.created_at DESC"
    ) ?: [];

    Helpers::jsonResponse([
        'status' => 'success',
        'data' => [
            'requests' => $requests
        ]
    ]);
}

if ($action === 'detail') {
    $id = (int)Helpers::get('id');
    $sl = Database::fetchOne("SELECT * FROM smartlinks WHERE id=?", [$id]);
    if (!$sl) {
        Helpers::jsonResponse(['status' => 'error', 'message' => 'Smartlink not found'], 404);
    }

    $offers = Database::fetchAll(
        "SELECT so.*, o.name as offer_name 
         FROM smartlink_offers so 
         LEFT JOIN offers o ON o.id=so.offer_id 
         WHERE so.smartlink_id=?", 
        [$id]
    ) ?: [];

    // Parse geo and device rules
    foreach ($offers as &$offer) {
        $offer['geo_rules'] = $offer['geo_rules'] ? json_decode($offer['geo_rules'], true) : [];
        $offer['device_rules'] = $offer['device_rules'] ? json_decode($offer['device_rules'], true) : [];
    }

    $activeOffers = Database::fetchAll("SELECT id, name, payout_amount as payout FROM offers WHERE status='active' ORDER BY name") ?: [];

    Helpers::jsonResponse([
        'status' => 'success',
        'data' => [
            'smartlink' => $sl,
            'offers' => $offers,
            'available_offers' => $activeOffers
        ]
    ]);
}

if ($action === 'action') {
    $data = Helpers::getJsonPayload();
    $subAction = $data['action'] ?? '';

    if ($subAction === 'toggle_status') {
        $id = (int)($data['id'] ?? 0);
        $cur = Database::fetchOne("SELECT status FROM smartlinks WHERE id=?", [$id]);
        if ($cur) {
            $newStatus = $cur['status'] === 'active' ? 'paused' : 'active';
            Database::update('smartlinks', ['status' => $newStatus], 'id=?', [$id]);
            Helpers::jsonResponse(['status' => 'success', 'message' => 'Status updated']);
        }
        Helpers::jsonResponse(['status' => 'error', 'message' => 'Not found'], 404);
    }

    if ($subAction === 'delete') {
        $id = (int)($data['id'] ?? 0);
        Database::query("DELETE FROM smartlink_offers WHERE smartlink_id=?", [$id]);
        Database::query("DELETE FROM smartlink_requests WHERE smartlink_id=?", [$id]);
        Database::query("DELETE FROM smartlinks WHERE id=?", [$id]);
        Helpers::jsonResponse(['status' => 'success', 'message' => 'Deleted successfully']);
    }

    if ($subAction === 'review_request') {
        $reqId = (int)($data['request_id'] ?? 0);
        $decision = $data['decision'] ?? '';
        $note = $data['admin_note'] ?? '';

        if (!in_array($decision, ['approved', 'rejected'])) {
            Helpers::jsonResponse(['status' => 'error', 'message' => 'Invalid decision']);
        }

        $req = Database::fetchOne(
            "SELECT sr.*, sl.name as sl_name,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name, u.email as aff_email, u.id as user_id_val
             FROM smartlink_requests sr
             JOIN smartlinks sl ON sl.id=sr.smartlink_id
             JOIN affiliates af ON af.id=sr.affiliate_id
             JOIN users u ON u.id=af.user_id
             WHERE sr.id=?", [$reqId]
        );

        if ($req) {
            Database::update('smartlink_requests', [
                'status'      => $decision,
                'admin_note'  => $note,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => date('Y-m-d H:i:s'),
            ], 'id=?', [$reqId]);

            Database::insert('notifications', [
                'user_id' => $req['user_id_val'],
                'type'    => $decision === 'approved' ? 'success' : 'warning',
                'title'   => 'Smartlink Request ' . ucfirst($decision),
                'message' => 'Your request to use smartlink "' . $req['sl_name'] . '" has been ' . $decision . '.',
                'link'    => '/affiliate/smartlinks',
            ]);

            Helpers::jsonResponse(['status' => 'success', 'message' => "Request $decision"]);
        } else {
            Helpers::jsonResponse(['status' => 'error', 'message' => 'Not found'], 404);
        }
    }

    Helpers::jsonResponse(['status' => 'error', 'message' => 'Invalid action'], 400);
}

if ($action === 'save') {
    $data = Helpers::getJsonPayload();
    $id = (int)($data['id'] ?? 0);
    $name = $data['name'] ?? '';
    $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($data['slug'] ?? ''));
    $rotation = $data['rotation_type'] ?? 'geo';
    $description = $data['description'] ?? '';
    $status = $data['status'] ?? 'active';
    $requireApproval = !empty($data['require_approval']) ? 1 : 0;
    $offers = $data['offers'] ?? [];

    if (!$name || !$slug) {
        Helpers::jsonResponse(['status' => 'error', 'message' => 'Name and slug are required'], 400);
    }

    if (empty($offers)) {
        Helpers::jsonResponse(['status' => 'error', 'message' => 'At least one offer is required'], 400);
    }

    if ($id > 0) {
        $existing = Database::fetchOne("SELECT id FROM smartlinks WHERE slug=? AND id!=?", [$slug, $id]);
        if ($existing) Helpers::jsonResponse(['status' => 'error', 'message' => 'Slug already in use'], 400);
    } else {
        $existing = Database::fetchOne("SELECT id FROM smartlinks WHERE slug=?", [$slug]);
        if ($existing) Helpers::jsonResponse(['status' => 'error', 'message' => 'Slug already in use'], 400);
    }

    Database::begin();
    try {
        if ($id > 0) {
            Database::update('smartlinks', [
                'name' => $name,
                'slug' => $slug,
                'rotation_type' => $rotation,
                'description' => $description,
                'status' => $status,
                'require_approval' => $requireApproval
            ], 'id=?', [$id]);
            Database::query("DELETE FROM smartlink_offers WHERE smartlink_id=?", [$id]);
            $slId = $id;
        } else {
            $slId = Database::insert('smartlinks', [
                'name' => $name,
                'slug' => $slug,
                'rotation_type' => $rotation,
                'description' => $description,
                'status' => $status,
                'require_approval' => $requireApproval,
                'created_by' => Auth::id()
            ]);
        }

        foreach ($offers as $o) {
            $oid = (int)($o['offer_id'] ?? 0);
            $directUrl = trim($o['direct_url'] ?? '') ?: null;
            if (!$oid && !$directUrl) continue;

            $geoArr = $o['geo_rules'] ?? [];
            $devArr = $o['device_rules'] ?? [];
            
            Database::insert('smartlink_offers', [
                'smartlink_id' => $slId,
                'offer_id'     => $oid ?: null,
                'weight'       => max(1, (int)($o['weight'] ?? 10)),
                'geo_rules'    => !empty($geoArr) ? json_encode($geoArr) : null,
                'device_rules' => !empty($devArr) ? json_encode($devArr) : null,
                'payout_type'  => $o['payout_type'] ?? 'default',
                'payout_value' => isset($o['payout_value']) ? max(0.0, (float)$o['payout_value']) : null,
                'direct_url'   => $directUrl
            ]);
        }
        Database::commit();
        Helpers::jsonResponse(['status' => 'success', 'message' => 'Saved successfully']);
    } catch (Exception $e) {
        Database::rollback();
        Helpers::jsonResponse(['status' => 'error', 'message' => 'Failed to save: ' . $e->getMessage()], 500);
    }
}

Helpers::jsonResponse(['status' => 'error', 'message' => 'Invalid action'], 400);
