package net.affscash.android.service

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Intent
import android.media.AudioAttributes
import android.media.RingtoneManager
import android.os.Build
import android.util.Log
import androidx.core.app.NotificationCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import net.affscash.android.data.local.NotificationBadgeManager
import net.affscash.android.data.local.UserManager
import net.affscash.android.data.network.ApiService
import net.affscash.android.MainActivity
import net.affscash.android.R
import javax.inject.Inject

/**
 * Handles FCM messages (both data-only and notification+data payloads).
 *
 * Data-only payloads are used by the backend so notifications arrive even when
 * the app is killed. This service manually constructs system notifications
 * and includes deep-link routing data in the pending intent extras.
 */
@AndroidEntryPoint
class MyFirebaseMessagingService : FirebaseMessagingService() {

    @Inject
    lateinit var userManager: UserManager

    @Inject
    lateinit var apiService: ApiService

    @Inject
    lateinit var badgeManager: NotificationBadgeManager

    override fun onNewToken(token: String) {
        super.onNewToken(token)
        Log.d(TAG, "Refreshed token: $token")
        sendRegistrationToServer(token)
    }

    override fun onMessageReceived(remoteMessage: RemoteMessage) {
        Log.d(TAG, "From: ${remoteMessage.from}")

        val data = remoteMessage.data

        // Data-only payload (backend sends these for reliable delivery when killed)
        if (data.isNotEmpty()) {
            Log.d(TAG, "Message data payload: $data")

            val title = data["title"] ?: remoteMessage.notification?.title ?: "AffsCash"
            val body = data["body"] ?: remoteMessage.notification?.body ?: ""
            val notificationType = data["notification_type"] ?: ""
            val deepLinkRoute = data["deep_link_route"] ?: ""
            val notificationId = data["notification_id"] ?: ""

            // Increment badge counter
            badgeManager.increment()

            // Show system notification with deep link data
            showNotification(title, body, notificationType, deepLinkRoute, notificationId)
            return
        }

        // Notification payload (foreground only — shown by system when in background)
        remoteMessage.notification?.let {
            Log.d(TAG, "Message Notification Body: ${it.body}")
            badgeManager.increment()
            showNotification(
                it.title ?: "AffsCash",
                it.body ?: "",
                "", "", ""
            )
        }
    }

    private fun sendRegistrationToServer(token: String?) {
        token?.let {
            CoroutineScope(Dispatchers.IO).launch {
                try {
                    val androidId = android.provider.Settings.Secure.getString(
                        contentResolver,
                        android.provider.Settings.Secure.ANDROID_ID
                    )
                    val request = mapOf(
                        "token" to it,
                        "platform" to "android",
                        "device_id" to (androidId ?: "unknown")
                    )
                    apiService.registerFcmToken(request)
                    Log.d(TAG, "Token registered to server")
                } catch (e: Exception) {
                    Log.e(TAG, "Failed to register token: ${e.message}")
                }
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
        // Build intent with deep link extras
        val intent = Intent(this, MainActivity::class.java).apply {
            addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP)
            putExtra("notification_type", notificationType)
            putExtra("deep_link_route", deepLinkRoute)
            putExtra("notification_id", notificationId)
            putExtra("from_notification", true)
        }

        val requestCode = System.currentTimeMillis().toInt()
        val pendingIntent = PendingIntent.getActivity(
            this, requestCode, intent,
            PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT
        )

        // Choose channel based on notification type
        val channelId = when {
            notificationType.contains("conversion") -> CHANNEL_CONVERSIONS
            notificationType.contains("withdrawal") || notificationType.contains("invoice") -> CHANNEL_WITHDRAWALS
            notificationType.contains("news") || notificationType.contains("announcement") -> CHANNEL_NEWS
            else -> CHANNEL_DEFAULT
        }

        val defaultSoundUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)
        val notificationBuilder = NotificationCompat.Builder(this, channelId)
            .setSmallIcon(R.drawable.ic_notification)
            .setContentTitle(title)
            .setContentText(body)
            .setStyle(NotificationCompat.BigTextStyle().bigText(body))
            .setAutoCancel(true)
            .setSound(defaultSoundUri)
            .setContentIntent(pendingIntent)
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setCategory(NotificationCompat.CATEGORY_MESSAGE)
            .setVisibility(NotificationCompat.VISIBILITY_PRIVATE)

        // Add icon color for brand consistency
        try {
            notificationBuilder.setColor(getColor(R.color.primary))
        } catch (_: Exception) {}

        val notificationManager = getSystemService(NOTIFICATION_SERVICE) as NotificationManager
        createNotificationChannels(notificationManager)

        notificationManager.notify(requestCode, notificationBuilder.build())
    }

    private fun createNotificationChannels(notificationManager: NotificationManager) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val soundUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)
            val audioAttributes = AudioAttributes.Builder()
                .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
                .setUsage(AudioAttributes.USAGE_NOTIFICATION)
                .build()

            val channels = listOf(
                NotificationChannel(
                    CHANNEL_DEFAULT, "General",
                    NotificationManager.IMPORTANCE_HIGH
                ).apply {
                    description = "General notifications"
                    setSound(soundUri, audioAttributes)
                    enableVibration(true)
                },
                NotificationChannel(
                    CHANNEL_CONVERSIONS, "Conversions",
                    NotificationManager.IMPORTANCE_HIGH
                ).apply {
                    description = "New conversion notifications"
                    setSound(soundUri, audioAttributes)
                    enableVibration(true)
                },
                NotificationChannel(
                    CHANNEL_WITHDRAWALS, "Withdrawals",
                    NotificationManager.IMPORTANCE_HIGH
                ).apply {
                    description = "Payment and withdrawal notifications"
                    setSound(soundUri, audioAttributes)
                    enableVibration(true)
                },
                NotificationChannel(
                    CHANNEL_NEWS, "News & Announcements",
                    NotificationManager.IMPORTANCE_DEFAULT
                ).apply {
                    description = "News and announcement notifications"
                    setSound(soundUri, audioAttributes)
                }
            )

            channels.forEach { notificationManager.createNotificationChannel(it) }
        }
    }

    companion object {
        private const val TAG = "MyFirebaseMsgService"
        private const val CHANNEL_DEFAULT = "affscash_default"
        private const val CHANNEL_CONVERSIONS = "affscash_conversions"
        private const val CHANNEL_WITHDRAWALS = "affscash_withdrawals"
        private const val CHANNEL_NEWS = "affscash_news"
    }
}
