package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class NotificationItem(
    val id: Int,
    val title: String,
    val message: String,
    val link: String?,
    @SerialName("is_read") val isRead: Int,
    @SerialName("created_at") val createdAt: String
)

@Serializable
data class NotificationsResponse(
    val success: Boolean,
    val notifications: List<NotificationItem> = emptyList(),
    val unread: Int = 0,
    val error: String? = null
)

@Serializable
data class MarkNotificationRequest(
    val id: Int? = null
)
