import re

with open("app/src/main/java/net/affscash/android/service/MyFirebaseMessagingService.kt", "r") as f:
    content = f.read()

# Replace NotificationManager with NotificationManagerCompat and handle permissions
replacement = """        val notificationManager = androidx.core.app.NotificationManagerCompat.from(this)

        // Ensure channels exist (safe redundant call \u2014 channels are created at app startup)
        NotificationChannelManager.createAllChannels(this)

        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.TIRAMISU) {
            if (androidx.core.app.ActivityCompat.checkSelfPermission(
                    this,
                    android.Manifest.permission.POST_NOTIFICATIONS
                ) != android.content.pm.PackageManager.PERMISSION_GRANTED
            ) {
                Log.e(TAG, "Missing POST_NOTIFICATIONS permission. Cannot show notification.")
                return
            }
        }

        notificationManager.notify(requestCode, notificationBuilder.build())"""

content = re.sub(
    r"val notificationManager = getSystemService\(NOTIFICATION_SERVICE\) as NotificationManager.*?notificationManager\.notify\(requestCode, notificationBuilder\.build\(\)\)",
    replacement,
    content,
    flags=re.DOTALL
)

replacement_summary = """    private fun showGroupSummary(notificationManager: androidx.core.app.NotificationManagerCompat) {
        val summaryNotification = NotificationCompat.Builder(this, NotificationChannelManager.CHANNEL_DEFAULT)
            .setSmallIcon(R.drawable.ic_notification)
            .setContentTitle("AffsCash")
            .setContentText("You have new notifications")
            .setGroup(GROUP_KEY)
            .setGroupSummary(true)
            .setAutoCancel(true)
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .build()

        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.TIRAMISU) {
            if (androidx.core.app.ActivityCompat.checkSelfPermission(
                    this,
                    android.Manifest.permission.POST_NOTIFICATIONS
                ) != android.content.pm.PackageManager.PERMISSION_GRANTED
            ) {
                return
            }
        }
        notificationManager.notify(SUMMARY_NOTIFICATION_ID, summaryNotification)
    }"""

content = re.sub(
    r"private fun showGroupSummary\(notificationManager: NotificationManager\) \{.*?notificationManager\.notify\(SUMMARY_NOTIFICATION_ID, summaryNotification\)\n    \}",
    replacement_summary,
    content,
    flags=re.DOTALL
)

with open("app/src/main/java/net/affscash/android/service/MyFirebaseMessagingService.kt", "w") as f:
    f.write(content)
