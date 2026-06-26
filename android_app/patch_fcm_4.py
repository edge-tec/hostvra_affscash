import re

with open("app/src/main/java/net/affscash/android/service/MyFirebaseMessagingService.kt", "r") as f:
    content = f.read()

# Replace the entire showNotification function
new_show_notif = """    private fun showNotification(
        title: String,
        body: String,
        notificationType: String,
        deepLinkRoute: String,
        notificationId: String
    ) {
        val intent = Intent(this, MainActivity::class.java).apply {
            addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP)
            putExtra("notification_type", notificationType)
            putExtra("deep_link_route", deepLinkRoute)
            putExtra("notification_id", notificationId)
            putExtra("from_notification", true)
        }

        val requestCode = (System.currentTimeMillis() % Integer.MAX_VALUE).toInt()
        val pendingIntent = PendingIntent.getActivity(
            this, requestCode, intent,
            PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT
        )

        // Force default channel to guarantee it exists
        val channelId = NotificationChannelManager.CHANNEL_DEFAULT
        NotificationChannelManager.createAllChannels(this)

        val notificationBuilder = NotificationCompat.Builder(this, channelId)
            .setSmallIcon(R.drawable.ic_notification)
            .setContentTitle(title)
            .setContentText(body)
            .setStyle(NotificationCompat.BigTextStyle().bigText(body))
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .setPriority(NotificationCompat.PRIORITY_HIGH)

        val notificationManager = androidx.core.app.NotificationManagerCompat.from(this)

        try {
            notificationManager.notify(requestCode, notificationBuilder.build())
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }"""

content = re.sub(r"    private fun showNotification\([\s\S]*?    }\n", new_show_notif + "\n", content)

with open("app/src/main/java/net/affscash/android/service/MyFirebaseMessagingService.kt", "w") as f:
    f.write(content)
