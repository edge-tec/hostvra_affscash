package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class ChatMessage(
    @SerialName("id") val id: Int,
    @SerialName("sender_id") val senderId: Int,
    @SerialName("sender_role") val senderRole: String,
    @SerialName("message") val message: String,
    @SerialName("created_at") val createdAt: String,
    @SerialName("is_read") val isRead: Int,
    @SerialName("sender_name") val senderName: String,
    @SerialName("attachment_path") val attachmentPath: String? = null,
    @SerialName("attachment_name") val attachmentName: String? = null,
    @SerialName("attachment_type") val attachmentType: String? = null,
    @SerialName("attachment_size") val attachmentSize: Int? = null
)

@Serializable
data class ChatMessagesResponse(
    @SerialName("success") val success: Boolean = false,
    @SerialName("messages") val messages: List<ChatMessage> = emptyList(),
    @SerialName("error") val error: String? = null
)

@Serializable
data class SendChatMessageRequest(
    @SerialName("action") val action: String = "send",
    @SerialName("message") val message: String,
    @SerialName("attachment_id") val attachmentId: Int? = null
)

@Serializable
data class SendChatMessageResponse(
    @SerialName("success") val success: Boolean = false,
    @SerialName("message") val message: ChatMessage? = null,
    @SerialName("error") val error: String? = null
)

@Serializable
data class UploadFileResponse(
    @SerialName("success") val success: Boolean = false,
    @SerialName("attachment_id") val attachmentId: Int? = null,
    @SerialName("attachment_name") val attachmentName: String? = null,
    @SerialName("attachment_size") val attachmentSize: Int? = null,
    @SerialName("attachment_type") val attachmentType: String? = null,
    @SerialName("error") val error: String? = null
)
