package net.affscash.android.service

import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.media.RingtoneManager
import android.os.PowerManager
import android.util.Log
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
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
 * Handles real-time FCM push notifications across all app states
 * (Foreground, Background, and Completely Closed / Killed).
 *
 * Implements:
 * - Immediate Heads-Up popup notification with sound & vibration
 * - Deep link intent routing for screen navigation
 * - Local Room DB persistence for offline notification history
 * - Instant badge count updates
 * - Notification grouping & stack summaries
 * - Transient WakeLock to wake the screen for urgent alerts
 * - Full Android 10-15+ compatibility
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

        // Save token locally and register with backend
        fcmTokenManager.onTokenRefreshed(token)
        fcmTokenManager.ensureTokenRegistered()
    }

    override fun onMessageReceived(remoteMessage: RemoteMessage) {
        Log.d(TAG, "FCM Message Received from: ${remoteMessage.from}, ID: ${remoteMessage.messageId}")

        val data = remoteMessage.data
        if (data.isNotEmpty()) {
            Log.d(TAG, "Message data payload: $data")
        }

        remoteMessage.notification?.let {
            Log.d(TAG, "Message Notification Title: ${it.title}, Body: ${it.body}")
        }

        // Extract exact badge counts from payload (if provided)
        val notifs = data["unread_notifs"]?.toIntOrNull()
        val chats = data["unread_chats"]?.toIntOrNull()
        val alerts = data["unread_alerts"]?.toIntOrNull()
        val approvals = data["pending_approvals"]?.toIntOrNull()

        // Update exact badge counts immediately
        if (notifs != null || chats != null || alerts != null || approvals != null) {
            Log.d(TAG, "Updating badge counts from payload: notifs=$notifs, chats=$chats")
            badgeManager.updateCounts(notifs, chats, alerts, approvals)
        } else {
            // Fallback: Increment unread count for generic payloads
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

        // Extract message fields from Data or Notification payload
        val title = data["title"] ?: remoteMessage.notification?.title ?: "AffsCash"
        val body = data["body"] ?: remoteMessage.notification?.body ?: ""
        val notificationType = data["notification_type"] ?: type
        val deepLinkRoute = data["deep_link_route"] ?: ""
        val notificationId = data["notification_id"] ?: remoteMessage.messageId ?: ""

        // Skip completely empty messages
        if (title.isBlank() && body.isBlank()) {
            Log.d(TAG, "Skipping notification: title and body are both empty")
            return
        }

        Log.d(TAG, "Processing push notification: title='$title', type='$notificationType', deepLink='$deepLinkRoute'")

        // Save notification locally for offline history
        saveNotificationLocally(notificationId, title, body, type, notificationType, deepLinkRoute)

        // Show system notification with Heads-Up popup, sound & vibration
        showNotification(title, body, notificationType, deepLinkRoute, notificationId)
    }

    /**
     * Saves a received push notification to local Room database for notification history.
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
                val id = notificationId.toIntOrNull() ?: (System.currentTimeMillis() % 1000000).toInt()
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
                        ).apply {
                            timeZone = java.util.TimeZone.getTimeZone("UTC")
                        }.format(java.util.Date())
                    )
                )
                Log.d(TAG, "Notification saved to local Room DB: id=$id")
            } catch (e: Exception) {
                Log.e(TAG, "Failed to save notification locally: ${e.message}")
            }
        }
    }

    /**
     * Constructs and posts a High-Priority system notification with Heads-Up popup,
     * custom sound, vibration, lockscreen visibility, and deep link navigation intent.
     */
    private fun showNotification(
        title: String,
        body: String,
        notificationType: String,
        deepLinkRoute: String,
        notificationId: String
    ) {
        // Ensure all channels are initialized
        NotificationChannelManager.createAllChannels(this)

        // Determine specific channel ID for notification type
        val channelId = NotificationChannelManager.getChannelForType(notificationType)

        // Intent for deep link routing upon tapping notification
        val intent = Intent(this, MainActivity::class.java).apply {
            addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP)
            putExtra("notification_type", notificationType)
            putExtra("deep_link_route", deepLinkRoute)
            putExtra("notification_id", notificationId)
            putExtra("from_notification", "true")
            putExtra("from_notification_bool", true)
        }

        val notificationIdInt = notificationId.toIntOrNull() ?: (System.currentTimeMillis() % 1000000).toInt()
        val requestCode = notificationIdInt

        val pendingIntent = PendingIntent.getActivity(
            this,
            requestCode,
            intent,
            PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT
        )

        val defaultSoundUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)
        val vibrationPattern = longArrayOf(0, 250, 250, 250)

        val notificationBuilder = NotificationCompat.Builder(this, channelId)
            .setSmallIcon(R.mipmap.ic_launcher)
            .setContentTitle(title)
            .setContentText(body)
            .setStyle(NotificationCompat.BigTextStyle().bigText(body))
            .setAutoCancel(true)
            .setSound(defaultSoundUri)
            .setVibrate(vibrationPattern)
            .setDefaults(NotificationCompat.DEFAULT_ALL)
            .setContentIntent(pendingIntent)
            .setPriority(NotificationCompat.PRIORITY_MAX)
            .setCategory(NotificationCompat.CATEGORY_MESSAGE)
            .setVisibility(NotificationCompat.VISIBILITY_PUBLIC)
            .setGroup(GROUP_KEY)

        try {
            notificationBuilder.setColor(ContextCompat.getColor(this, R.color.primary))
        } catch (_: Exception) {}

        // Check POST_NOTIFICATIONS permission on Android 13+ (Tiramisu+)
        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(this, android.Manifest.permission.POST_NOTIFICATIONS)
                != android.content.pm.PackageManager.PERMISSION_GRANTED
            ) {
                Log.e(TAG, "Missing POST_NOTIFICATIONS permission on Android 13+. Cannot display notification.")
                return
            }
        }

        // Wake lock: Turn screen on briefly for high-priority / heads-up notification
        wakeScreenForNotification()

        val notificationManager = NotificationManagerCompat.from(this)
        try {
            // Display notification
            notificationManager.notify(notificationIdInt, notificationBuilder.build())
            Log.d(TAG, "Notification successfully posted to system manager: id=$notificationIdInt, channel=$channelId")

            // Display group summary if needed
            val summaryNotification = NotificationCompat.Builder(this, channelId)
                .setSmallIcon(R.mipmap.ic_launcher)
                .setStyle(NotificationCompat.InboxStyle().setSummaryText("AffsCash Notifications"))
                .setGroup(GROUP_KEY)
                .setGroupSummary(true)
                .setAutoCancel(true)
                .setPriority(NotificationCompat.PRIORITY_HIGH)
                .build()

            notificationManager.notify(SUMMARY_ID, summaryNotification)
        } catch (e: Exception) {
            Log.e(TAG, "FATAL ERROR posting notification to system: ${e.message}", e)
        }
    }

    /**
     * Acquires a temporary WakeLock to wake up device screen for urgent notifications.
     */
    private fun wakeScreenForNotification() {
        try {
            val powerManager = getSystemService(Context.POWER_SERVICE) as PowerManager
            @Suppress("DEPRECATION")
            val wakeLock = powerManager.newWakeLock(
                PowerManager.SCREEN_BRIGHT_WAKE_LOCK or PowerManager.ACQUIRE_CAUSES_WAKEUP,
                "AffsCash:NotificationWakeLock"
            )
            wakeLock.acquire(3000L) // Wake screen for 3 seconds
            Log.d(TAG, "Acquired WakeLock to wake device screen")
        } catch (e: Exception) {
            Log.w(TAG, "Could not acquire WakeLock: ${e.message}")
        }
    }

    companion object {
        private const val TAG = "MyFirebaseMsgService"
        private const val GROUP_KEY = "net.affscash.android.NOTIFICATIONS"
        private const val SUMMARY_ID = 99999
    }
}
