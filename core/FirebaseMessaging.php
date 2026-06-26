<?php

/**
 * FirebaseMessaging
 *
 * Sends push notifications via FCM v1 HTTP API with:
 * - OAuth2 access token caching (55-min TTL)
 * - High-priority Android delivery
 * - Data-only payloads (works when app is killed)
 * - Deep link routing data
 * - Stale token cleanup on FCM 404/NOT_FOUND
 * - Notification channel routing for Android O+
 * - Batch sending with rate limiting
 */
class FirebaseMessaging {
    private static $credentialsPath = __DIR__ . '/firebase_credentials.json';
    private static $tokenCachePath  = __DIR__ . '/../storage/fcm_token_cache.json';
    private static $tokenUri = 'https://oauth2.googleapis.com/token';

    // FCM notification channel IDs matching Android app channels
    private static $channelMap = [
        'conversion'   => 'affscash_conversions',
        'withdrawal'   => 'affscash_withdrawals',
        'support'      => 'affscash_support',
        'chat'         => 'affscash_support',
        'ticket'       => 'affscash_support',
        'announcement' => 'affscash_announcements',
        'news'         => 'affscash_announcements',
        'offer'        => 'affscash_general',
        'account'      => 'affscash_general',
        'billing'      => 'affscash_withdrawals',
        'payout'       => 'affscash_withdrawals',
        'fraud'        => 'affscash_general',
        'notification' => 'affscash_general',
        'info'         => 'affscash_general',
        'danger'       => 'affscash_general',
    ];

    /**
     * Sends a push notification to a specific device token.
     *
     * @param string $deviceToken  FCM device registration token
     * @param string $title        Notification title
     * @param string $body         Notification body
     * @param array  $data         Extra data payload (must be string key-value pairs)
     * @return array|false         FCM response or false on failure
     */
    public static function send($deviceToken, $title, $body, $data = []) {
        if (!$deviceToken || strlen($deviceToken) < 10) {
            return false;
        }

        if (!file_exists(self::$credentialsPath)) {
            error_log("[FCM] Firebase credentials missing.");
            return false;
        }

        $credentials = json_decode(file_get_contents(self::$credentialsPath), true);
        if (!$credentials || empty($credentials['project_id'])) {
            error_log("[FCM] Invalid Firebase credentials JSON.");
            return false;
        }

        $accessToken = self::getAccessToken($credentials);
        if (!$accessToken) {
            return false;
        }

        $projectId = $credentials['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        // Determine notification channel based on type
        $type = $data['type'] ?? 'info';
        $channelId = self::$channelMap[$type] ?? 'affscash_general';

        // Ensure all data values are strings (FCM requirement)
        $stringData = [];
        foreach ($data as $k => $v) {
            $stringData[(string)$k] = (string)$v;
        }
        // Always include title and body in data so the app can handle
        // data-only messages when in background/killed
        $stringData['title'] = (string)$title;
        $stringData['body']  = (string)$body;
        $stringData['from_notification'] = "true";

        $message = [
            'message' => [
                'token' => $deviceToken,
                // Notification payload ensures delivery to the system tray by Google Play Services
                // even if the app is force-killed or restricted by OEM battery optimizers.
                'notification' => [
                    'title' => (string)$title,
                    'body'  => (string)$body,
                ],
                // Data payload — always delivered to the app natively
                'data' => (object)$stringData,
                // Android-specific configuration
                'android' => [
                    'priority' => 'HIGH',          // Bypass Doze mode (HIGH in v1 API)
                    'ttl'      => '86400s',        // 24h TTL
                    'direct_boot_ok' => true,      // Deliver even if device is locked
                    'notification' => [
                        'channel_id' => $channelId, // Explicitly route to correct channel
                        'click_action' => 'android.intent.action.MAIN' // Open app on click
                    ]
                ],
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Handle stale/invalid tokens
        if ($httpCode === 404 || $httpCode === 400) {
            $responseData = json_decode($response, true);
            $errorCode = $responseData['error']['details'][0]['errorCode'] ?? '';
            $errorStatus = $responseData['error']['status'] ?? '';
            if ($errorCode === 'UNREGISTERED' || $errorStatus === 'NOT_FOUND' || $httpCode === 404) {
                self::removeStaleToken($deviceToken);
                return false;
            }
        }

        if ($httpCode !== 200) {
            error_log("[FCM] Send Error (HTTP $httpCode): " . substr($response, 0, 500));
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Sends a push notification to multiple users by user IDs.
     * Includes rate limiting: max 500 sends per batch with 100ms delay between batches.
     */
    public static function sendToUsers($userIds, $title, $body, $data = []) {
        require_once __DIR__ . '/Database.php';
        require_once __DIR__ . '/BadgeSyncHelper.php';

        if (empty($userIds)) return 0;

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $rows = Database::fetchAll(
            "SELECT ud.device_token, ud.user_id, u.role FROM user_devices ud JOIN users u ON u.id = ud.user_id WHERE ud.user_id IN ($placeholders)",
            $userIds
        );

        if (empty($rows)) return 0;

        $sent = 0;
        $batchCount = 0;
        foreach ($rows as $row) {
            // Calculate exact counts for this specific user
            $counts = BadgeSyncHelper::getCountsForUser($row['user_id'], $row['role']);
            $userData = array_merge($data, [
                'unread_notifs' => (string)$counts['unread_notifs'],
                'unread_chats' => (string)$counts['unread_chats'],
                'unread_alerts' => (string)$counts['unread_alerts'],
                'pending_approvals' => (string)$counts['pending_approvals'],
            ]);

            $result = self::send($row['device_token'], $title, $body, $userData);
            if ($result !== false) $sent++;
            $batchCount++;

            // Rate limit: pause every 500 sends
            if ($batchCount >= 500) {
                usleep(100000); // 100ms
                $batchCount = 0;
            }
        }

        return $sent;
    }

    /**
     * Sends a push notification to all users of a specific role, or everyone.
     */
    public static function sendToRole($role, $title, $body, $data = []) {
        require_once __DIR__ . '/Database.php';

        if ($role === 'all') {
            $users = Database::fetchAll("SELECT id FROM users WHERE status='active'");
        } else {
            $users = Database::fetchAll("SELECT id FROM users WHERE role=? AND status='active'", [$role]);
        }

        if (empty($users)) return 0;

        $userIds = array_column($users, 'id');
        return self::sendToUsers($userIds, $title, $body, $data);
    }

    /**
     * Sends a push notification to a specific user.
     */
    public static function sendToUser($userId, $title, $body, $data = []) {
        return self::sendToUsers([$userId], $title, $body, $data);
    }

    /**
     * Remove a stale/invalid device token from the database.
     */
    private static function removeStaleToken($deviceToken) {
        try {
            require_once __DIR__ . '/Database.php';
            Database::query("DELETE FROM user_devices WHERE device_token = ?", [$deviceToken]);
        } catch (\Throwable $e) {
            error_log("[FCM] Failed to remove stale token: " . $e->getMessage());
        }
    }

    /**
     * Generates an OAuth2 access token from the Service Account JSON.
     * Caches the token for 55 minutes (tokens are valid for 60 min).
     */
    private static function getAccessToken($credentials) {
        // Check file-based cache first
        $cacheDir = dirname(self::$tokenCachePath);
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        if (file_exists(self::$tokenCachePath)) {
            $cached = json_decode(file_get_contents(self::$tokenCachePath), true);
            if ($cached && isset($cached['access_token'], $cached['expires_at'])) {
                if (time() < $cached['expires_at']) {
                    return $cached['access_token'];
                }
            }
        }

        // Generate new JWT
        $jwtHeader = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $jwtClaim = json_encode([
            'iss'   => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => self::$tokenUri,
            'exp'   => $now + 3600,
            'iat'   => $now
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($jwtHeader));
        $base64UrlClaim  = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($jwtClaim));

        $signatureInput = $base64UrlHeader . '.' . $base64UrlClaim;

        $signature = '';
        if (!openssl_sign($signatureInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            error_log("[FCM] JWT Signature Failed.");
            return null;
        }

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt = $signatureInput . '.' . $base64UrlSignature;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$tokenUri);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("[FCM] Token request failed (HTTP $httpCode): " . substr($response, 0, 500));
            return null;
        }

        $data = json_decode($response, true);
        if (!isset($data['access_token'])) {
            error_log("[FCM] No access_token in response.");
            return null;
        }

        // Cache the token for 55 minutes (5 min buffer before expiry)
        $cacheData = [
            'access_token' => $data['access_token'],
            'expires_at'   => time() + 3300, // 55 minutes
        ];
        @file_put_contents(self::$tokenCachePath, json_encode($cacheData), LOCK_EX);

        return $data['access_token'];
    }
}
