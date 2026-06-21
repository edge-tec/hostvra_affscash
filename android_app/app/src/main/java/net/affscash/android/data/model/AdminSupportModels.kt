package net.affscash.android.data.model

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class AdminSupportConversationsResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("data") val data: AdminSupportConversationsData? = null,
    @SerialName("error") val error: String? = null
)

@Serializable
data class AdminSupportConversationsData(
    @SerialName("conversations") val conversations: List<AdminSupportConversationRow> = emptyList()
)

@Serializable
data class AdminSupportConversationRow(
    @SerialName("conversation_id") val conversationId: Int,
    @SerialName("affiliate_id") val affiliateId: Int,
    @SerialName("name") val name: String,
    @SerialName("affiliate_code") val affiliateCode: String,
    @SerialName("status") val status: String,
    @SerialName("last_message_at") val lastMessageAt: String? = null,
    @SerialName("unread") val unread: Int = 0,
    @SerialName("last_msg") val lastMsg: String? = null
)

@Serializable
data class AdminSupportMessagesResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("data") val data: AdminSupportMessagesData? = null,
    @SerialName("error") val error: String? = null
)

@Serializable
data class AdminSupportMessagesData(
    @SerialName("conversation") val conversation: AdminSupportConversationState? = null,
    @SerialName("messages") val messages: List<AdminSupportMessage> = emptyList()
)

@Serializable
data class AdminSupportConversationState(
    @SerialName("id") val id: Int,
    @SerialName("status") val status: String
)

@Serializable
data class AdminSupportMessage(
    @SerialName("id") val id: Int,
    @SerialName("sender_id") val senderId: Int,
    @SerialName("sender_role") val senderRole: String,
    @SerialName("sender_name") val senderName: String,
    @SerialName("message") val message: String,
    @SerialName("created_at") val createdAt: String,
    @SerialName("is_read") val isRead: Int = 0,
    @SerialName("attachment_path") val attachmentPath: String? = null,
    @SerialName("attachment_name") val attachmentName: String? = null,
    @SerialName("attachment_type") val attachmentType: String? = null,
    @SerialName("attachment_size") val attachmentSize: Int? = null,
    @SerialName("edited_at") val editedAt: String? = null
)

@Serializable
data class AdminSupportSendRequest(
    @SerialName("affiliate_id") val affiliateId: Int,
    @SerialName("owner_type") val ownerType: String,
    @SerialName("message") val message: String,
    @SerialName("attachment_id") val attachmentId: Int? = null
)

@Serializable
data class AdminSupportSendResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("data") val data: AdminSupportSendResponseData? = null,
    @SerialName("error") val error: String? = null
)

@Serializable
data class AdminSupportSendResponseData(
    @SerialName("message_id") val messageId: Int
)

@Serializable
data class AdminSupportActionRequest(
    @SerialName("conversation_id") val conversationId: Int
)

@Serializable
data class AdminSupportActionResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("error") val error: String? = null
)

@Serializable
data class AdminUploadResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("attachment_id") val attachmentId: Int? = null,
    @SerialName("error") val error: String? = null
)
