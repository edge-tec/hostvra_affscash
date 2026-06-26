import re

with open("app/src/main/java/net/affscash/android/MainActivity.kt", "r") as f:
    content = f.read()

replacement = """    private fun handleNotificationIntent(intent: Intent?) {
        val fromNotificationStr = intent?.getStringExtra("from_notification")
        val notificationTypeStr = intent?.getStringExtra("notification_type")
        val fromNotification = intent?.getBooleanExtra("from_notification", false) == true || 
                               fromNotificationStr == "true" ||
                               !notificationTypeStr.isNullOrEmpty()

        if (fromNotification) {
            val deepLinkRoute = intent?.getStringExtra("deep_link_route")
            val notificationType = intent?.getStringExtra("notification_type")"""

content = re.sub(r"    private fun handleNotificationIntent\(intent: Intent\?\) \{\n        if \(intent\?\.getBooleanExtra\(\"from_notification\", false\) == true\) \{\n            val deepLinkRoute = intent\.getStringExtra\(\"deep_link_route\"\)\n            val notificationType = intent\.getStringExtra\(\"notification_type\"\)", replacement, content)

with open("app/src/main/java/net/affscash/android/MainActivity.kt", "w") as f:
    f.write(content)
