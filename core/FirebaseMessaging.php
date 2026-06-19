<?php

class FirebaseMessaging {
    private static $credentialsPath = __DIR__ . '/firebase_credentials.json';
    private static $tokenUri = 'https://oauth2.googleapis.com/token';

    /**
     * Sends a push notification to a specific device token.
     */
    public static function send($deviceToken, $title, $body, $data = []) {
        if (!file_exists(self::$credentialsPath)) {
            error_log("Firebase credentials missing.");
            return false;
        }

        $credentials = json_decode(file_get_contents(self::$credentialsPath), true);
        if (!$credentials) {
            error_log("Invalid Firebase credentials JSON.");
            return false;
        }

        $accessToken = self::getAccessToken($credentials);
        if (!$accessToken) {
            return false;
        }

        $projectId = $credentials['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $message = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => (object)$data
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("FCM Send Error: " . $response);
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Sends a push notification to multiple users.
     */
    public static function sendToUsers($userIds, $title, $body, $data = []) {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance();
        $conn = $db->getConnection();

        if (empty($userIds)) return;

        // Send FCM pushes
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $rows = Database::fetchAll("SELECT device_token FROM user_devices WHERE user_id IN ($placeholders)", $userIds);

        foreach ($rows as $row) {
            self::send($row['device_token'], $title, $body, $data);
        }
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
        
        if (empty($users)) return;
        
        $userIds = array_column($users, 'id');
        
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $devices = Database::fetchAll("SELECT device_token FROM user_devices WHERE user_id IN ($placeholders)", $userIds);
        
        foreach ($devices as $row) {
            self::send($row['device_token'], $title, $body, $data);
        }
    }

    /**
     * Sends a push notification to a specific user.
     */
    public static function sendToUser($userId, $title, $body, $data = []) {
        self::sendToUsers([$userId], $title, $body, $data);
    }

    /**
     * Generates an OAuth2 access token from the Service Account JSON.
     */
    private static function getAccessToken($credentials) {
        $jwtHeader = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $jwtClaim = json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => self::$tokenUri,
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($jwtHeader));
        $base64UrlClaim = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($jwtClaim));

        $signatureInput = $base64UrlHeader . '.' . $base64UrlClaim;
        
        $signature = '';
        if (!openssl_sign($signatureInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            error_log("FCM JWT Signature Failed.");
            return null;
        }

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt = $signatureInput . '.' . $base64UrlSignature;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$tokenUri);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return isset($data['access_token']) ? $data['access_token'] : null;
    }
}
