package net.affscash.android.data.model

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class ManagerConversationResponse(
    val success: Boolean,
    val conversations: List<ManagerConversation>? = null
)

@Serializable
data class ManagerConversation(
    @SerialName("affiliate_id") val affiliateId: Int,
    @SerialName("name") val name: String,
    @SerialName("affiliate_code") val affiliateCode: String? = null,
    @SerialName("conversation_id") val conversationId: Int? = null,
    @SerialName("status") val status: String? = null,
    @SerialName("closed_at") val closedAt: String? = null,
    @SerialName("last_message_at") val lastMessageAt: String? = null,
    @SerialName("last_msg") val lastMsg: String? = null,
    @SerialName("unread") val unread: Int = 0
)

@Serializable
data class ManagerMessageResponse(
    val success: Boolean,
    val messages: List<ManagerMessage>? = null
)

@Serializable
data class ManagerMessage(
    val id: Int,
    @SerialName("sender_id") val senderId: Int,
    @SerialName("sender_role") val senderRole: String,
    val message: String,
    @SerialName("created_at") val createdAt: String,
    @SerialName("is_read") val isRead: Int = 0,
    @SerialName("attachment_path") val attachmentPath: String? = null,
    @SerialName("attachment_name") val attachmentName: String? = null,
    @SerialName("attachment_type") val attachmentType: String? = null,
    @SerialName("attachment_size") val attachmentSize: Int? = null,
    @SerialName("sender_name") val senderName: String? = null
)

@Serializable
data class ManagerSendMessageResponse(
    val success: Boolean,
    val message: ManagerMessage? = null,
    val error: String? = null
)

@Serializable
data class ManagerUploadResponse(
    val success: Boolean,
    @SerialName("attachment_id") val attachmentId: Int? = null,
    @SerialName("attachment_name") val attachmentName: String? = null,
    @SerialName("attachment_size") val attachmentSize: Int? = null,
    @SerialName("attachment_type") val attachmentType: String? = null,
    val error: String? = null
)
