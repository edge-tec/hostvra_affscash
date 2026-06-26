package net.affscash.android.service

import android.app.NotificationChannel
import android.app.NotificationChannelGroup
import android.app.NotificationManager
import android.content.Context
import android.media.AudioAttributes
import android.media.RingtoneManager
import android.os.Build
import androidx.core.app.NotificationManagerCompat

/**
 * Centralized notification channel management.
 *
 * All channel IDs are defined here and must match the backend
 * FirebaseMessaging.php `$channelMap` exactly.
 *
 * Called from Application.onCreate() so channels exist before any
 * notification arrives — required by Android O+ (API 26+).
 */
object NotificationChannelManager {

    // Channel IDs — must match backend FirebaseMessaging.php
    const val CHANNEL_DEFAULT      = "affscash_default"
    const val CHANNEL_GENERAL      = "affscash_general"
    const val CHANNEL_CONVERSIONS  = "affscash_conversions"
    const val CHANNEL_WITHDRAWALS  = "affscash_withdrawals"
    const val CHANNEL_NEWS         = "affscash_news"
    const val CHANNEL_SUPPORT      = "affscash_support"
    const val CHANNEL_ANNOUNCEMENTS = "affscash_announcements"
    const val CHANNEL_SECURITY     = "affscash_security"
    const val CHANNEL_MESSAGES     = "affscash_messages"

    // Channel group IDs
    private const val GROUP_TRANSACTIONS = "group_transactions"
    private const val GROUP_COMMUNICATION = "group_communication"
    private const val GROUP_SYSTEM = "group_system"

    /**
     * Creates all notification channels. Safe to call multiple times —
     * existing channels are not modified (user preferences are preserved).
     */
    fun createAllChannels(context: Context) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return

        val notificationManager = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        val soundUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)
        val audioAttributes = AudioAttributes.Builder()
            .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
            .setUsage(AudioAttributes.USAGE_NOTIFICATION)
            .build()

        // Create channel groups for organization
        notificationManager.createNotificationChannelGroups(listOf(
            NotificationChannelGroup(GROUP_TRANSACTIONS, "Transactions"),
            NotificationChannelGroup(GROUP_COMMUNICATION, "Communication"),
            NotificationChannelGroup(GROUP_SYSTEM, "System")
        ))

        val channels = listOf(
            // Default / General — catch-all for unmatched types
            NotificationChannel(
                CHANNEL_DEFAULT, "General",
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "General notifications"
                group = GROUP_SYSTEM
                setSound(soundUri, audioAttributes)
                enableVibration(true)
                setShowBadge(true)
                lockscreenVisibility = android.app.Notification.VISIBILITY_PUBLIC
            },

            NotificationChannel(
                CHANNEL_GENERAL, "General Updates",
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "Offer updates, account alerts, and other general notifications"
                group = GROUP_SYSTEM
                setSound(soundUri, audioAttributes)
                enableVibration(true)
                setShowBadge(true)
                lockscreenVisibility = android.app.Notification.VISIBILITY_PUBLIC
            },

            // Conversions — high priority (money events)
            NotificationChannel(
                CHANNEL_CONVERSIONS, "Conversions",
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "New conversion and lead notifications"
                group = GROUP_TRANSACTIONS
                setSound(soundUri, audioAttributes)
                enableVibration(true)
                setShowBadge(true)
                lockscreenVisibility = android.app.Notification.VISIBILITY_PUBLIC
            },

            // Withdrawals & Payments — high priority (money events)
            NotificationChannel(
                CHANNEL_WITHDRAWALS, "Payments & Withdrawals",
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "Payment, withdrawal, invoice, and billing notifications"
                group = GROUP_TRANSACTIONS
                setSound(soundUri, audioAttributes)
                enableVibration(true)
                setShowBadge(true)
                lockscreenVisibility = android.app.Notification.VISIBILITY_PUBLIC
            },

            // Support — high priority
            NotificationChannel(
                CHANNEL_SUPPORT, "Support",
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "Support ticket and chat notifications"
                group = GROUP_COMMUNICATION
                setSound(soundUri, audioAttributes)
                enableVibration(true)
                setShowBadge(true)
                lockscreenVisibility = android.app.Notification.VISIBILITY_PUBLIC
            },

            // Messages
            NotificationChannel(
                CHANNEL_MESSAGES, "Messages",
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "Direct messages and chat notifications"
                group = GROUP_COMMUNICATION
                setSound(soundUri, audioAttributes)
                enableVibration(true)
                setShowBadge(true)
                lockscreenVisibility = android.app.Notification.VISIBILITY_PUBLIC
            },

            // News & Announcements — default priority
            NotificationChannel(
                CHANNEL_NEWS, "News",
                NotificationManager.IMPORTANCE_DEFAULT
            ).apply {
                description = "News and promotional notifications"
                group = GROUP_COMMUNICATION
                setSound(soundUri, audioAttributes)
                setShowBadge(true)
            },

            NotificationChannel(
                CHANNEL_ANNOUNCEMENTS, "Announcements",
                NotificationManager.IMPORTANCE_DEFAULT
            ).apply {
                description = "System announcements and updates"
                group = GROUP_COMMUNICATION
                setSound(soundUri, audioAttributes)
                setShowBadge(true)
            },

            // Security — max priority
            NotificationChannel(
                CHANNEL_SECURITY, "Security",
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "Login alerts, security warnings, and account security"
                group = GROUP_SYSTEM
                setSound(soundUri, audioAttributes)
                enableVibration(true)
                setShowBadge(true)
                lockscreenVisibility = android.app.Notification.VISIBILITY_PUBLIC
            }
        )

        channels.forEach { notificationManager.createNotificationChannel(it) }
    }

    /**
     * Maps a notification type string to the correct channel ID.
     * Used by MyFirebaseMessagingService when building system notifications.
     */
    fun getChannelForType(notificationType: String): String {
        return when {
            notificationType.contains("conversion") || notificationType.contains("lead") -> CHANNEL_CONVERSIONS
            notificationType.contains("withdrawal") || notificationType.contains("invoice")
                || notificationType.contains("payment") || notificationType.contains("payout")
                || notificationType.contains("billing") || notificationType.contains("commission") -> CHANNEL_WITHDRAWALS
            notificationType.contains("support") || notificationType.contains("ticket") -> CHANNEL_SUPPORT
            notificationType.contains("chat") || notificationType.contains("message") -> CHANNEL_MESSAGES
            notificationType.contains("news") || notificationType.contains("promo") -> CHANNEL_NEWS
            notificationType.contains("announcement") -> CHANNEL_ANNOUNCEMENTS
            notificationType.contains("security") || notificationType.contains("login")
                || notificationType.contains("fraud") -> CHANNEL_SECURITY
            notificationType.contains("offer") || notificationType.contains("account")
                || notificationType.contains("affiliate") -> CHANNEL_GENERAL
            else -> CHANNEL_DEFAULT
        }
    }

    /**
     * Returns true if notifications are enabled for the app.
     */
    fun areNotificationsEnabled(context: Context): Boolean {
        return NotificationManagerCompat.from(context).areNotificationsEnabled()
    }
}
