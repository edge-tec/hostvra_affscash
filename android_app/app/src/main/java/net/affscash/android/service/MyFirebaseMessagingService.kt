package net.affscash.android.service

import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Intent
import android.media.RingtoneManager
import android.util.Log
import androidx.core.app.NotificationCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import net.affscash.android.data.local.NotificationBadgeManager
import net.affscash.android.data.local.NotificationDatabase
import net.affscash.android.data.local.LocalNotification
import net.affscash.android.MainActivity
import net.affscash.android.R
import javax.inject.Inject

/**
 * Handles FCM messages (both data-only and notification+data payloads).
 *
 * Data-only payloads are used by the backend so notifications arrive even when
 * the app is killed. This service manually constructs system notifications
 * and includes deep-link routing data in the pending intent extras.
 *
 * All received notifications are also persisted locally in Room database
 * for offline viewing and reliable badge counts.
 */
@AndroidEntryPoint
class MyFirebaseMessagingService : FirebaseMessagingService() {

    @Inject
    lateinit var badgeManager: NotificationBadgeManager

    @Inject
    lateinit var fcmTokenManager: FcmTokenManager

    private val serviceScope = CoroutineScope(Dispatchers.IO + SupervisorJob())

    override fun onNewToken(token: String) {
        super.onNewToken(token)
        Log.d(TAG, "Refreshed FCM token: $token")

        // Save token locally and schedule reliable registration
        fcmTokenManager.onTokenRefreshed(token)
        fcmTokenManager.ensureTokenRegistered()
    }

    override fun onMessageReceived(remoteMessage: RemoteMessage) {
        Log.d(TAG, "FCM Message Received from: ${remoteMessage.from}")
        Log.d(TAG, "Message ID: ${remoteMessage.messageId}")

        val data = remoteMessage.data
        if (data.isNotEmpty()) {
            Log.d(TAG, "Message data payload: $data")
        }

        remoteMessage.notification?.let {
            Log.d(TAG, "Message Notification Title: ${it.title}")
            Log.d(TAG, "Message Notification Body: ${it.body}")
            Log.d(TAG, "Message Notification Channel: ${it.channelId}")
        }

        // Extract exact counts from payload (if provided)
        val notifs = data["unread_notifs"]?.toIntOrNull()
        val chats = data["unread_chats"]?.toIntOrNull()
        val alerts = data["unread_alerts"]?.toIntOrNull()
        val approvals = data["pending_approvals"]?.toIntOrNull()

        // Update exact badge counts immediately
        if (notifs != null || chats != null || alerts != null || approvals != null) {
            Log.d(TAG, "Updating badge counts from payload: notifs=$notifs, chats=$chats")
            badgeManager.updateCounts(notifs, chats, alerts, approvals)
        } else {
            // Fallback for legacy generic payloads
            Log.d(TAG, "Incrementing unread count (legacy/unspecified payload)")
            badgeManager.updateCounts(
                notifs = (badgeManager.unreadNotifs.value + 1),
                chats = null, alerts = null, approvals = null
            )
        }

        // Silent sync push: only meant to update badges, no system notification shown
        val type = data["type"] ?: ""
        if (type == "silent_sync") {
            Log.d(TAG, "Received silent sync push. Badges updated. Stopping.")
            return
        }

        // Data-only payload (backend sends these for reliable delivery when killed)
        if (data.isNotEmpty()) {
            val title = data["title"] ?: remoteMessage.notification?.title ?: "AffsCash"
            val body = data["body"] ?: remoteMessage.notification?.body ?: ""
            val notificationType = data["notification_type"] ?: type
            val deepLinkRoute = data["deep_link_route"] ?: ""
            val notificationId = data["notification_id"] ?: ""

            // Skip empty title/body (shouldn't happen except for silent_sync already handled above)
            if (title.isBlank() && body.isBlank()) {
                Log.d(TAG, "Skipping notification: title and body are both empty")
                return
            }

            Log.d(TAG, "Manually building notification: $title")
            // Save notification locally
            saveNotificationLocally(notificationId, title, body, type, notificationType, deepLinkRoute)

            // Show system notification with deep link data
            showNotification(title, body, notificationType, deepLinkRoute, notificationId)
            return
        }

        // Notification payload (system handles this in background, we handle it in foreground)
        remoteMessage.notification?.let {
            Log.d(TAG, "Handling system notification payload in foreground")
            showNotification(
                it.title ?: "AffsCash",
                it.body ?: "",
                "", "", ""
            )
        }
    }

    /**
     * Saves a received push notification to local Room database.
     */
    private fun saveNotificationLocally(
        notificationId: String,
        title: String,
        body: String,
        type: String,
        notificationType: String,
        deepLinkRoute: String
    ) {
        serviceScope.launch {
            try {
                val id = notificationId.toIntOrNull() ?: return@launch
                val dao = NotificationDatabase.getInstance(applicationContext).notificationDao()
                dao.insert(
                    LocalNotification(
                        id = id,
                        title = title,
                        message = body,
                        type = type.ifEmpty { "info" },
                        notificationType = notificationType.ifEmpty { null },
                        deepLinkRoute = deepLinkRoute.ifEmpty { null },
                        isRead = 0,
                        createdAt = java.text.SimpleDateFormat(
                            "yyyy-MM-dd HH:mm:ss",
                            java.util.Locale.getDefault()
                        ).format(java.util.Date())
                    )
                )
                Log.d(TAG, "Notification saved locally: id=$id")
            } catch (e: Exception) {
                Log.e(TAG, "Failed to save notification locally: ${e.message}")
            }
        }
    }

    private fun showNotification(
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
    }

                val requestCode = (System.currentTimeMillis() % Integer.MAX_VALUE).toInt()
        val notificationIdInt = requestCode
        val pendingIntent = PendingIntent.getActivity(
            this, requestCode, intent,
            PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT
        )

        // Use centralized channel manager for consistent channel mapping
        val channelId = NotificationChannelManager.getChannelForType(notificationType)

        val defaultSoundUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)
        val notificationBuilder = NotificationCompat.Builder(this, channelId)
            .setSmallIcon(R.drawable.ic_notification)
            .setContentTitle(title)
            .setContentText(body)
            .setStyle(NotificationCompat.BigTextStyle().bigText(body))
            .setAutoCancel(true)
            .setSound(defaultSoundUri)
            .setDefaults(NotificationCompat.DEFAULT_ALL)
            .setContentIntent(pendingIntent)
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setCategory(NotificationCompat.CATEGORY_MESSAGE)
            .setVisibility(NotificationCompat.VISIBILITY_PUBLIC)

        // Add icon color for brand consistency
        try {
            notificationBuilder.setColor(getColor(R.color.primary))
        } catch (_: Exception) {}

                val notificationManager = androidx.core.app.NotificationManagerCompat.from(this)

        // Ensure channels exist (safe redundant call — channels are created at app startup)
        NotificationChannelManager.createAllChannels(this)

        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.TIRAMISU) {
            if (androidx.core.content.ContextCompat.checkSelfPermission(
                    this,
                    android.Manifest.permission.POST_NOTIFICATIONS
                ) != android.content.pm.PackageManager.PERMISSION_GRANTED
            ) {
                Log.e(TAG, "Missing POST_NOTIFICATIONS permission. Cannot show notification.")
                return
            }
        }

                try {
            notificationManager.notify(notificationIdInt, notificationBuilder.build())
            Log.d(TAG, "Notification delivered to system manager. id=$notificationIdInt")
        } catch (e: Exception) {
            Log.e(TAG, "FATAL ERROR posting notification to system: ${e.message}", e)
        }
    }

    /**
     * Creates a summary notification for grouped notifications.
     * Only shows when there are 2+ notifications in the group.
     */

    companion object {
        private const val TAG = "MyFirebaseMsgService"
        private const val GROUP_KEY = "net.affscash.android.NOTIFICATIONS"
    }
}

