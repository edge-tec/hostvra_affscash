<?php
/**
 * Super Admin - Plan Management Controller
 */
Auth::check();
if (Auth::role() !== 'super_admin') {
    Helpers::redirect('/');
}

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !Auth::verifyCsrf($_POST['csrf_token'])) {
        $error = 'CSRF token verification failed.';
    } else {
        $name           = trim($_POST['name'] ?? '');
        $price          = (float)($_POST['price'] ?? 0);
        $duration       = (int)($_POST['duration'] ?? 30);
        $maxUsers       = (int)($_POST['max_users'] ?? 0);
        $maxOffers      = (int)($_POST['max_offers'] ?? 0);
        $maxDomains     = (int)($_POST['max_domains'] ?? 0);
        $maxClicks      = (int)($_POST['max_click_limits'] ?? 0);
        $maxStorage     = (int)($_POST['max_storage'] ?? 0);
        $status         = $_POST['status'] ?? 'active';

        if (!$name || $price < 0 || $duration <= 0) {
            $error = 'Name, price, and duration are required and must be valid.';
        } else {
            if ($action === 'create') {
                Database::insert('subscription_plans', [
                    'name'             => $name,
                    'price'            => $price,
                    'currency'         => 'USD',
                    'duration'         => $duration,
                    'max_users'        => $maxUsers,
                    'max_offers'       => $maxOffers,
                    'max_domains'      => $maxDomains,
                    'max_click_limits' => $maxClicks,
                    'max_storage'      => $maxStorage,
                    'status'           => $status,
                    'created_at'       => date('Y-m-d H:i:s')
                ]);
                $success = 'Plan created successfully!';
                Helpers::redirect('/super_admin/plans?success=' . urlencode($success));
            } elseif ($action === 'edit') {
                $id = (int)($_GET['id'] ?? 0);
                Database::update('subscription_plans', [
                    'name'             => $name,
                    'price'            => $price,
                    'duration'         => $duration,
                    'max_users'        => $maxUsers,
                    'max_offers'       => $maxOffers,
                    'max_domains'      => $maxDomains,
                    'max_click_limits' => $maxClicks,
                    'max_storage'      => $maxStorage,
                    'status'           => $status
                ], 'id = ?', [$id]);
                $success = 'Plan updated successfully!';
                Helpers::redirect('/super_admin/plans?success=' . urlencode($success));
            }
        }
    }
}

$plans = Database::fetchAll("SELECT * FROM `subscription_plans` ORDER BY id DESC");

require BASE_PATH . '/views/super_admin/plans.php';
