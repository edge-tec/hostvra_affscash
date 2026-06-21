package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class NotificationItem(
    val id: Int,
    val title: String,
    val message: String,
    val type: String? = "info",
    val link: String? = null,
    @SerialName("notification_type") val notificationType: String? = null,
    @SerialName("deep_link_route") val deepLinkRoute: String? = null,
    @SerialName("is_read") val isRead: Int = 0,
    @SerialName("created_at") val createdAt: String
)

@Serializable
data class NotificationsResponse(
    val success: Boolean,
    val notifications: List<NotificationItem> = emptyList(),
    val unread: Int = 0,
    val page: Int = 1,
    @SerialName("per_page") val perPage: Int = 20,
    @SerialName("has_more") val hasMore: Boolean = false,
    val error: String? = null
)

@Serializable
data class MarkNotificationRequest(
    val id: Int? = null
)

@Serializable
data class NotificationDeleteRequest(
    val id: Int
)

@Serializable
data class UnreadCountResponse(
    val success: Boolean,
    val unread: Int = 0
)
