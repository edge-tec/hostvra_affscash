<?php
Auth::check('admin');
$pageTitle = 'Notifications';

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $action = Helpers::post('action');

    if ($action === 'send') {
        require_once BASE_PATH . '/core/NotificationHelper.php';
        $title      = Helpers::post('title');
        $message    = Helpers::postRaw('message');
        $targetRole = Helpers::post('target_role');
        $type       = Helpers::post('type');
        $userId     = Helpers::postRaw('user_id') ? (int)Helpers::postRaw('user_id') : null;

        if ($userId) {
            NotificationHelper::notifyUser(
                $userId, $title, $message,
                $type ?: 'info', '', [],
                'admin_notification', 'notifications'
            );
        } elseif ($targetRole) {
            NotificationHelper::notifyRole(
                $targetRole, $title, $message,
                $type ?: 'info', '', [],
                'admin_notification', 'notifications'
            );
        } else {
            // Broadcast to all roles
            foreach (['admin', 'affiliate_manager', 'affiliate', 'advertiser'] as $role) {
                NotificationHelper::notifyRole(
                    $role, $title, $message,
                    $type ?: 'info', '', [],
                    'admin_notification', 'notifications'
                );
            }
        }

        Helpers::flash('success', 'Notification sent.');
    }

    if ($action === 'delete') {
        Database::delete('notifications', 'id=?', [(int)Helpers::postRaw('id')]);
        Helpers::flash('success', 'Notification deleted.');
    }

    Helpers::redirect('/admin/notifications');
}

$notifications = Database::fetchAll(
    "SELECT n.*, CONCAT(u.first_name,' ',u.last_name) as user_name FROM notifications n LEFT JOIN users u ON u.id=n.user_id ORDER BY n.created_at DESC LIMIT 100"
);
$affiliates  = Database::fetchAll("SELECT u.id,CONCAT(u.first_name,' ',u.last_name,' <',u.email,'>') as label FROM users u WHERE u.role='affiliate' AND u.status='active'");
$advertisers = Database::fetchAll("SELECT u.id,CONCAT(u.first_name,' ',u.last_name,' <',u.email,'>') as label FROM users u WHERE u.role='advertiser' AND u.status='active'");

require BASE_PATH . '/views/admin/notifications/index.php';
