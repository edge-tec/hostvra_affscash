<?php

/**
 * BadgeSyncHelper
 *
 * Centralizes the logic to calculate exactly how many unread notifications,
 * chats, alerts, and pending approvals a user has, based on their role.
 * Used to inject real-time exact counts into FCM payloads.
 */
class BadgeSyncHelper {

    public static function getCountsForUser($userId, $role) {
        require_once __DIR__ . '/Database.php';

        $counts = [
            'unread_notifs' => 0,
            'unread_chats' => 0,
            'unread_alerts' => 0,
            'pending_approvals' => 0,
        ];

        try {
            if ($role === 'admin') {
                $unreadNotifs = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0", [$userId])['c'] ?? 0);
                $unreadBroadcast = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM notifications n WHERE n.user_id IS NULL AND n.target_role IN ('admin', 'all') AND NOT EXISTS (SELECT 1 FROM notification_reads nr WHERE nr.notification_id=n.id AND nr.user_id=?)", [$userId])['c'] ?? 0);
                $unreadAlerts = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM fraud_alerts WHERE is_read=0 AND resolved_at IS NULL")['c'] ?? 0);
                try { Database::query("ALTER TABLE support_messages ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0"); } catch(\Throwable $e) {}
                $unreadChats = (int)(Database::fetchOne("SELECT COUNT(*) c FROM support_messages WHERE owner_type IN ('affiliate', 'advertiser') AND sender_role != 'admin' AND is_read=0 AND is_deleted=0")['c'] ?? 0);
                $pendingApprovals = (int)(Database::fetchOne("SELECT COUNT(*) c FROM affiliate_offers WHERE status='pending'")['c'] ?? 0);

                $counts['unread_notifs'] = $unreadNotifs + $unreadBroadcast;
                $counts['unread_alerts'] = $unreadAlerts;
                $counts['unread_chats'] = $unreadChats;
                $counts['pending_approvals'] = $pendingApprovals;
            } 
            elseif ($role === 'affiliate_manager') {
                $mgrRow = Database::fetchOne("SELECT id FROM affiliate_managers WHERE user_id=?", [$userId]);
                $mgrId = $mgrRow['id'] ?? 0;

                $managerAffIds = [];
                if ($mgrId) {
                    $affRows = Database::fetchAll("SELECT id FROM affiliates WHERE manager_id=?", [$mgrId]);
                    $managerAffIds = array_column($affRows, 'id');
                }

                $unreadNotifs = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0", [$userId])['c'] ?? 0);
                
                $unreadAdminChats = (int)(Database::fetchOne("SELECT COUNT(*) c FROM manager_messages WHERE manager_id=? AND sender_role='admin' AND read_by_manager=0", [$mgrId])['c'] ?? 0);
                $unreadAffiliateChats = 0;
                $pendingApprovals = 0;
                $unreadAlerts = 0;

                if (!empty($managerAffIds)) {
                    $inAff = implode(',', array_fill(0, count($managerAffIds), '?'));
                    $unreadAffiliateChats = (int)(Database::fetchOne(
                        "SELECT COUNT(*) c FROM support_messages sm JOIN support_conversations sc ON sc.id = sm.conversation_id WHERE sc.affiliate_id IN ($inAff) AND sm.sender_role='affiliate' AND sm.is_read=0",
                        $managerAffIds
                    )['c'] ?? 0);

                    $pendingApprovals = (int)(Database::fetchOne(
                        "SELECT COUNT(*) c FROM affiliate_offers WHERE affiliate_id IN ($inAff) AND status='pending'",
                        $managerAffIds
                    )['c'] ?? 0);

                    $unreadAlerts = (int)(Database::fetchOne(
                        "SELECT COUNT(*) c FROM fraud_alerts WHERE affiliate_id IN ($inAff) AND is_read=0 AND resolved_at IS NULL",
                        $managerAffIds
                    )['c'] ?? 0);
                }

                $counts['unread_notifs'] = $unreadNotifs;
                $counts['unread_chats'] = $unreadAdminChats + $unreadAffiliateChats;
                $counts['unread_alerts'] = $unreadAlerts;
                $counts['pending_approvals'] = $pendingApprovals;
            }
            elseif ($role === 'affiliate') {
                $affRow = Database::fetchOne("SELECT id FROM affiliates WHERE user_id=?", [$userId]);
                $affId = $affRow['id'] ?? 0;

                $unreadNotifs = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0", [$userId])['c'] ?? 0);
                $unreadAlerts = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM fraud_alerts WHERE affiliate_id=? AND is_read=0 AND resolved_at IS NULL", [$affId])['c'] ?? 0);
                $unreadChats = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM support_messages WHERE affiliate_id=? AND owner_type='affiliate' AND sender_role!='affiliate' AND is_read=0 AND is_deleted=0", [$affId])['c'] ?? 0);

                $counts['unread_notifs'] = $unreadNotifs;
                $counts['unread_alerts'] = $unreadAlerts;
                $counts['unread_chats'] = $unreadChats;
                $counts['pending_approvals'] = 0; // Affiliates don't approve offers
            }

        } catch (Throwable $e) {
            error_log("[BadgeSyncHelper] Failed to get counts: " . $e->getMessage());
        }

        return $counts;
    }

    /**
     * Emits a silent push notification via FCM to immediately sync read states
     * to the user's Android app without showing a system notification.
     */
    public static function emitReadSync($userId) {
        require_once __DIR__ . '/Database.php';
        require_once __DIR__ . '/FirebaseMessaging.php';

        $user = Database::fetchOne("SELECT role FROM users WHERE id=?", [$userId]);
        if (!$user) return false;

        // Data payload with no title or body acts as a silent data-only push in the Android App
        // The MyFirebaseMessagingService will intercept this, update counts, and skip showing anything.
        return FirebaseMessaging::sendToUser($userId, "", "", [
            'type' => 'silent_sync',
            'action' => 'update_badges'
        ]);
    }
}
