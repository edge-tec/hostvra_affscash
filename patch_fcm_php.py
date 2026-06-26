import re

with open("core/FirebaseMessaging.php", "r") as f:
    content = f.read()

replacement = """        $stringData['title'] = (string)$title;
        $stringData['body']  = (string)$body;
        $stringData['from_notification'] = "true";

        $message = [
            'message' => [
                'token' => $deviceToken,
                // System notification payload — handles delivery when app is killed/swiped away on strict OEMs
                'notification' => [
                    'title' => (string)$title,
                    'body'  => (string)$body,
                ],
                // Data payload — always delivered to the app natively
                'data' => (object)$stringData,
                // Android-specific configuration
                'android' => [
                    'priority' => 'high',          // Bypass Doze mode (must be lowercase 'high' in v1 API)
                    'ttl'      => '86400s',        // 24h TTL
                    'direct_boot_ok' => true,      // Deliver even if device is locked
                    'notification' => [
                        'channel_id' => $channelId, // Explicitly route to correct channel
                        'click_action' => 'android.intent.action.MAIN' // Open app on click
                    ]
                ],
            ]
        ];"""

content = re.sub(r"        \$stringData\['title'\] = \(string\)\$title;\n        \$stringData\['body'\]  = \(string\)\$body;\n\n        \$message = \[\n            'message' => \[\n                'token' => \$deviceToken,\n                // Data payload — always delivered to the app natively without OS interference\n                'data' => \(object\)\$stringData,\n                // Android-specific configuration\n                'android' => \[\n                    'priority' => 'HIGH',          // Bypass Doze mode \(must be HIGH not high\)\n                    'ttl'      => '86400s',        // 24h TTL\n                    'direct_boot_ok' => true       // Deliver even if device is locked\n                \],\n            \]\n        \];", replacement, content)

with open("core/FirebaseMessaging.php", "w") as f:
    f.write(content)
