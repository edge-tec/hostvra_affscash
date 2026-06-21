<?php
/**
 * NotificationHelper
 *
 * Centralizes the creation of in-app notifications and Firebase Cloud Messaging (FCM) push notifications.
 * Supports:
 * - notification_type for categorization and deep linking
 * - deep_link_route for Android in-app navigation
 * - Deduplication (prevents identical notifications within 60 seconds)
 * - Returns the notification ID for callers to reference
 */
class NotificationHelper {

    /**
     * Notify a specific user.
     * Inserts into `notifications` table and triggers FCM.
     *
     * @param int         $userId           Target user ID
     * @param string      $title            Notification title
     * @param string      $message          Notification body
     * @param string      $type             Category type (info, conversion, withdrawal, etc.)
     * @param string|null $link             Web link (for web panel)
     * @param array       $fcmData          Extra data for FCM push
     * @param string|null $notificationType Specific event type (conversion_new, withdrawal_approved, etc.)
     * @param string|null $deepLinkRoute    Android deep link route (e.g., conversion_details/123)
     * @return int|false                    The notification ID, or false on failure
     */
    public static function notifyUser($userId, $title, $message, $type = 'info', $link = null, $fcmData = [], $notificationType = null, $deepLinkRoute = null) {
        if (!$userId) return false;

        require_once __DIR__ . '/Database.php';
        require_once __DIR__ . '/FirebaseMessaging.php';

        try {
            // Deduplication: skip if identical notification was sent in last 60 seconds
            $existing = Database::fetchAll(
                "SELECT id FROM notifications WHERE user_id=? AND title=? AND type=? AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND) LIMIT 1",
                [$userId, $title, $type]
            );
            if (!empty($existing)) {
                return (int)$existing[0]['id']; // Already exists, return its ID
            }

            Database::query(
                "INSERT INTO notifications (user_id, title, message, type, link, notification_type, deep_link_route) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$userId, $title, $message, $type, $link, $notificationType, $deepLinkRoute]
            );

            $notifId = Database::lastInsertId();

            // Prepare FCM data payload
            $fcmData['link'] = $link ?? '';
            $fcmData['type'] = $type;
            $fcmData['notification_type'] = $notificationType ?? $type;
            $fcmData['deep_link_route'] = $deepLinkRoute ?? '';
            $fcmData['notification_id'] = (string)$notifId;

            FirebaseMessaging::sendToUser($userId, $title, $message, $fcmData);

            return (int)$notifId;
        } catch (\Throwable $e) {
            error_log("[NotificationHelper::notifyUser] " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify an entire role (e.g., 'admin', 'affiliate_manager', 'affiliate', 'all').
     * Inserts a broadcast notification into `notifications` table with `target_role` and triggers FCM.
     *
     * @param string      $role             Target role ('admin', 'affiliate', 'affiliate_manager', 'all')
     * @param string      $title            Notification title
     * @param string      $message          Notification body
     * @param string      $type             Category type
     * @param string|null $link             Web link
     * @param array       $fcmData          Extra data for FCM push
     * @param string|null $notificationType Specific event type
     * @param string|null $deepLinkRoute    Android deep link route
     * @return int|false                    The notification ID, or false on failure
     */
    public static function notifyRole($role, $title, $message, $type = 'info', $link = null, $fcmData = [], $notificationType = null, $deepLinkRoute = null) {
        if (!$role) return false;

        require_once __DIR__ . '/Database.php';
        require_once __DIR__ . '/FirebaseMessaging.php';

        try {
            // Deduplication: skip if identical broadcast was sent in last 60 seconds
            $existing = Database::fetchAll(
                "SELECT id FROM notifications WHERE target_role=? AND title=? AND type=? AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND) LIMIT 1",
                [$role, $title, $type]
            );
            if (!empty($existing)) {
                return (int)$existing[0]['id'];
            }

            Database::query(
                "INSERT INTO notifications (target_role, title, message, type, link, notification_type, deep_link_route) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$role, $title, $message, $type, $link, $notificationType, $deepLinkRoute]
            );

            $notifId = Database::lastInsertId();

            // Prepare FCM data payload
            $fcmData['link'] = $link ?? '';
            $fcmData['type'] = $type;
            $fcmData['notification_type'] = $notificationType ?? $type;
            $fcmData['deep_link_route'] = $deepLinkRoute ?? '';
            $fcmData['notification_id'] = (string)$notifId;

            FirebaseMessaging::sendToRole($role, $title, $message, $fcmData);

            return (int)$notifId;
        } catch (\Throwable $e) {
            error_log("[NotificationHelper::notifyRole] " . $e->getMessage());
            return false;
        }
    }

}
