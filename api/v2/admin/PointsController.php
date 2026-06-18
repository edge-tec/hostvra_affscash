<?php
header('Content-Type: application/json');
Auth::check('admin');

try {
    $action = Helpers::get('action') ?: 'list';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $input['action'] ?? $action;

        if ($action === 'save_config') {
            $enabled  = isset($input['enabled']) && ($input['enabled'] === true || $input['enabled'] === '1' || $input['enabled'] === 1);
            $usdPerPt = (int)($input['usd_per_point'] ?? 1);
            if ($usdPerPt < 1) $usdPerPt = 1;
            if ($usdPerPt > 100) $usdPerPt = 100;
            PointsService::setConfig($enabled, $usdPerPt);
            echo json_encode(['success' => true, 'message' => 'Points configuration saved.']);
            exit;
        }

        if ($action === 'adjust') {
            $affId  = (int)($input['affiliate_id'] ?? 0);
            $delta  = (int)($input['delta'] ?? 0);
            $reason = trim((string)($input['reason'] ?? ''));
            if ($affId > 0 && $delta !== 0) {
                $r = PointsService::adjust($affId, $delta, $reason ?: 'Admin adjustment');
                if ($r === -1) {
                    throw new Exception('Adjustment rejected: would create negative balance.');
                } else {
                    echo json_encode(['success' => true, 'message' => sprintf('Balance for affiliate #%d is now %d points.', $affId, $r)]);
                    exit;
                }
            } else {
                throw new Exception('Invalid affiliate ID or delta.');
            }
        }

        if ($action === 'sync_all') {
            $dryRun = isset($input['dry_run']) && ($input['dry_run'] === true || $input['dry_run'] === '1' || $input['dry_run'] === 1);
            $since  = trim((string)($input['since'] ?? ''));
            $result = PointsService::syncAllApproved($dryRun, $since ?: null);
            
            $msg = sprintf(
                'Sync %s: %d processed, %d credited, %d skipped, %d errors.',
                $dryRun ? '(dry run)' : 'complete',
                $result['processed'],
                $result['credited'],
                $result['skipped'],
                $result['errors']
            );
            
            echo json_encode(['success' => true, 'message' => $msg, 'log' => $result['log'] ?? []]);
            exit;
        }

        throw new Exception("Invalid action");
    }

    if ($action === 'list') {
        $cfg = PointsService::config();
        
        $rows = Database::fetchAll(
            "SELECT af.id AS affiliate_id,
                    CONCAT(u.first_name,' ',u.last_name) AS name,
                    u.email,
                    COALESCE(ap.balance, 0)         AS balance,
                    COALESCE(ap.lifetime_earned, 0) AS lifetime_earned,
                    COALESCE(ap.lifetime_spent, 0)  AS lifetime_spent,
                    ap.updated_at
             FROM affiliates af
             JOIN users u ON u.id = af.user_id
             LEFT JOIN affiliate_points ap ON ap.affiliate_id = af.id
             WHERE u.status = 'active'
             ORDER BY balance DESC, lifetime_earned DESC
             LIMIT 500"
        ) ?: [];

        $recent = Database::fetchAll(
            "SELECT pt.*, CONCAT(u.first_name,' ',u.last_name) AS aff_name, u.email
             FROM points_transactions pt
             LEFT JOIN affiliates af ON af.id = pt.affiliate_id
             LEFT JOIN users u ON u.id = af.user_id
             ORDER BY pt.id DESC LIMIT 50"
        ) ?: [];

        echo json_encode([
            'success' => true,
            'config' => [
                'enabled' => (bool)$cfg['enabled'],
                'usd_per_point' => (int)$cfg['usd_per_point']
            ],
            'balances' => $rows,
            'recent' => $recent
        ]);
        exit;
    }

    throw new Exception("Invalid action");

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
