<?php
/**
 * Admin App API — Affiliate Managers
 */
Auth::check('admin');

try {
    // Read JSON input if any
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? ($input['action'] ?? 'list');

    if ($action === 'list') {
        $managers = Database::fetchAll(
            "SELECT u.id as user_id, u.email, u.first_name, u.last_name, u.status, u.created_at,
                    am.id as mgr_id, am.permissions, am.balance, am.commission_rate,
                    (SELECT COUNT(*) FROM affiliates af WHERE af.manager_id = am.id) as aff_count
             FROM users u
             JOIN affiliate_managers am ON am.user_id = u.id
             WHERE u.role = 'affiliate_manager' AND u.status != 'deleted'
             ORDER BY u.created_at DESC"
        );

        $result = [];
        if ($managers) {
            foreach ($managers as $manager) {
                // Try parsing permissions JSON
                $perms = [];
                if (!empty($manager['permissions'])) {
                    $parsed = json_decode($manager['permissions'], true);
                    if (is_array($parsed)) {
                        $perms = $parsed;
                    }
                }

                $result[] = [
                    'user_id' => (int)$manager['user_id'],
                    'mgr_id' => (int)$manager['mgr_id'],
                    'name' => trim($manager['first_name'] . ' ' . $manager['last_name']),
                    'email' => $manager['email'],
                    'status' => $manager['status'],
                    'created_at' => $manager['created_at'],
                    'permissions' => $perms,
                    'balance' => number_format((float)$manager['balance'], 4, '.', ''),
                    'commission_rate' => number_format((float)$manager['commission_rate'], 2, '.', ''),
                    'aff_count' => (int)$manager['aff_count']
                ];
            }
        }

        Helpers::json(['status' => 'success', 'message' => null, 'data' => ['managers' => $result]]);
        exit;
    }

    if ($action === 'delete') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helpers::json(['status' => 'error', 'message' => 'Method not allowed'], 405);
        }

        $mgrId = isset($input['mgr_id']) ? (int)$input['mgr_id'] : 0;

        if ($mgrId <= 0) {
            Helpers::json(['status' => 'error', 'message' => 'Invalid manager ID']);
            exit;
        }

        $manager = Database::fetchOne(
            "SELECT u.id as user_id, u.first_name, u.last_name
             FROM users u
             JOIN affiliate_managers am ON am.user_id = u.id
             WHERE am.id = ?",
            [$mgrId]
        );

        if (!$manager) {
            Helpers::json(['status' => 'error', 'message' => 'Manager not found']);
            exit;
        }

        Database::begin();
        
        // Soft delete user
        Database::update('users', ['status' => 'deleted'], 'id=?', [$manager['user_id']]);
        
        // Unassign all affiliates from this manager
        Database::query(
            "UPDATE affiliates SET manager_id = NULL, manager_assigned_at = NULL WHERE manager_id = ?",
            [$mgrId]
        );
        
        // Kill active sessions
        Database::query("DELETE FROM user_active_sessions WHERE user_id = ?", [$manager['user_id']]);

        Database::commit();
        
        Helpers::json(['status' => 'success', 'message' => 'Affiliate Manager account deleted successfully.']);
        exit;
    }

    if ($action === 'impersonate') {
        $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
        if ($userId <= 0) {
            Helpers::json(['status' => 'error', 'message' => 'Invalid user ID']);
            exit;
        }

        if (Auth::impersonate($userId)) {
            echo json_encode([
                'success' => true,
                'role' => 'affiliate_manager',
                'user' => Auth::currentUser()
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to impersonate']);
        }
        exit;
    }

    Helpers::json(['status' => 'error', 'message' => 'Invalid action'], 400);


} catch (\Exception $e) {
    error_log("Admin AffiliateManager API Error: " . $e->getMessage());
    Helpers::json(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()], 500);
}
