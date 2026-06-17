<?php
require_once BASE_PATH . '/api/v2/ApiController.php';

class AffiliateManagerController extends ApiController {
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->requireAdmin();
    }

    public function handle() {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            $this->listManagers();
        } elseif ($action === 'delete') {
            $this->deleteManager();
        } else {
            $this->sendError('Invalid action');
        }
    }

    private function listManagers() {
        try {
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

            $this->sendSuccess(['managers' => $result], 'Managers fetched successfully');
        } catch (\Exception $e) {
            $this->sendError('Failed to fetch managers: ' . $e->getMessage());
        }
    }

    private function deleteManager() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('Method not allowed', 405);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $mgrId = isset($input['mgr_id']) ? (int)$input['mgr_id'] : 0;

        if ($mgrId <= 0) {
            $this->sendError('Invalid manager ID');
            return;
        }

        try {
            $manager = Database::fetchOne(
                "SELECT u.id as user_id, u.first_name, u.last_name
                 FROM users u
                 JOIN affiliate_managers am ON am.user_id = u.id
                 WHERE am.id = ?",
                [$mgrId]
            );

            if (!$manager) {
                $this->sendError('Manager not found');
                return;
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
            $this->sendSuccess(null, 'Affiliate Manager account deleted successfully.');
        } catch (\Exception $e) {
            Database::rollback();
            $this->sendError('Failed to delete manager: ' . $e->getMessage());
        }
    }
}

$controller = new AffiliateManagerController();
$controller->handle();
