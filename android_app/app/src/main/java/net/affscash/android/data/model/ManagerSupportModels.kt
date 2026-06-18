package net.affscash.android.data.model

import com.google.gson.annotations.SerializedName

data class ManagerConversationResponse(
    val success: Boolean,
    val conversations: List<ManagerConversation>? = null
)

data class ManagerConversation(
    @SerializedName("affiliate_id") val affiliateId: Int,
    @SerializedName("name") val name: String,
    @SerializedName("affiliate_code") val affiliateCode: String?,
    @SerializedName("conversation_id") val conversationId: Int?,
    @SerializedName("status") val status: String?,
    @SerializedName("closed_at") val closedAt: String?,
    @SerializedName("last_message_at") val lastMessageAt: String?,
    @SerializedName("last_msg") val lastMsg: String?,
    @SerializedName("unread") val unread: Int
)

data class ManagerMessageResponse(
    val success: Boolean,
    val messages: List<ManagerMessage>? = null
)

data class ManagerMessage(
    val id: Int,
    @SerializedName("sender_id") val senderId: Int,
    @SerializedName("sender_role") val senderRole: String,
    val message: String,
    @SerializedName("created_at") val createdAt: String,
    @SerializedName("is_read") val isRead: Int,
    @SerializedName("attachment_path") val attachmentPath: String?,
    @SerializedName("attachment_name") val attachmentName: String?,
    @SerializedName("attachment_type") val attachmentType: String?,
    @SerializedName("attachment_size") val attachmentSize: Int?,
    @SerializedName("sender_name") val senderName: String?
)

data class ManagerSendMessageResponse(
    val success: Boolean,
    val message: ManagerMessage? = null,
    val error: String? = null
)

data class ManagerUploadResponse(
    val success: Boolean,
    @SerializedName("attachment_id") val attachmentId: Int?,
    @SerializedName("attachment_name") val attachmentName: String?,
    @SerializedName("attachment_size") val attachmentSize: Int?,
    @SerializedName("attachment_type") val attachmentType: String?,
    val error: String? = null
)
