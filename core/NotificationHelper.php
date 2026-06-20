<?php
/**
 * NotificationHelper
 *
 * Centralizes the creation of in-app notifications and Firebase Cloud Messaging (FCM) push notifications.
 */
class NotificationHelper {

    /**
     * Notify a specific user.
     * Inserts into `notifications` table and triggers FCM.
     */
    public static function notifyUser($userId, $title, $message, $type = 'info', $link = null, $fcmData = []) {
        if (!$userId) return;

        require_once __DIR__ . '/Database.php';
        require_once __DIR__ . '/FirebaseMessaging.php';

        try {
            Database::query(
                "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)",
                [$userId, $title, $message, $type, $link]
            );

            // Add standard data
            $fcmData['link'] = $link ?? '';
            $fcmData['type'] = $type;

            FirebaseMessaging::sendToUser($userId, $title, $message, $fcmData);
        } catch (\Throwable $e) {
            error_log("[NotificationHelper::notifyUser] " . $e->getMessage());
        }
    }

    /**
     * Notify an entire role (e.g., 'admin', 'affiliate_manager').
     * Inserts into `notifications` table with `target_role` and triggers FCM for all active users in role.
     */
    public static function notifyRole($role, $title, $message, $type = 'info', $link = null, $fcmData = []) {
        if (!$role) return;

        require_once __DIR__ . '/Database.php';
        require_once __DIR__ . '/FirebaseMessaging.php';

        try {
            Database::query(
                "INSERT INTO notifications (target_role, title, message, type, link) VALUES (?, ?, ?, ?, ?)",
                [$role, $title, $message, $type, $link]
            );

            // Add standard data
            $fcmData['link'] = $link ?? '';
            $fcmData['type'] = $type;

            FirebaseMessaging::sendToRole($role, $title, $message, $fcmData);
        } catch (\Throwable $e) {
            error_log("[NotificationHelper::notifyRole] " . $e->getMessage());
        }
    }

}
