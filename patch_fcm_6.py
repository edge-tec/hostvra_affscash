import re

with open("core/FirebaseMessaging.php", "r") as f:
    content = f.read()

replacement = """        $message = [
            'message' => [
                'token' => $deviceToken,
                // Data payload — always delivered to the app natively
                'data' => (object)$stringData,
                // Android-specific configuration
                'android' => [
                    'priority' => 'high',          // Bypass Doze mode (must be lowercase 'high' in v1 API)
                    'ttl'      => '86400s',        // 24h TTL
                    'direct_boot_ok' => true,      // Deliver even if device is locked
                ],
            ]
        ];

        // Only include the System notification payload if there is a visible title or body.
        // If it's a silent background sync (like badge_sync), omit the notification block.
        if (!empty($title) || !empty($body)) {
            $message['message']['notification'] = [
                'title' => (string)$title,
                'body'  => (string)$body,
            ];
            $message['message']['android']['notification'] = [
                'channel_id' => $channelId, // Explicitly route to correct channel
                'click_action' => 'android.intent.action.MAIN' // Open app on click
            ];
        }"""

content = re.sub(r"        \$message = \[\n            'message' => \[\n                'token' => \$deviceToken,\n                // System notification payload — handles delivery when app is killed/swiped away on strict OEMs\n                'notification' => \[\n                    'title' => \(string\)\$title,\n                    'body'  => \(string\)\$body,\n                \],\n                // Data payload — always delivered to the app natively\n                'data' => \(object\)\$stringData,\n                // Android-specific configuration\n                'android' => \[\n                    'priority' => 'high',          // Bypass Doze mode \(must be lowercase 'high' in v1 API\)\n                    'ttl'      => '86400s',        // 24h TTL\n                    'direct_boot_ok' => true,      // Deliver even if device is locked\n                    'notification' => \[\n                        'channel_id' => \$channelId, // Explicitly route to correct channel\n                        'click_action' => 'android\.intent\.action\.MAIN' // Open app on click\n                    \]\n                \],\n            \]\n        \];", replacement, content)

with open("core/FirebaseMessaging.php", "w") as f:
    f.write(content)
