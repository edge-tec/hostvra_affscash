package net.affscash.android.data.model

import com.google.gson.annotations.SerializedName

data class AdminSupportConversationsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: AdminSupportConversationsData?,
    @SerializedName("error") val error: String?
)

data class AdminSupportConversationsData(
    @SerializedName("conversations") val conversations: List<AdminSupportConversationRow>
)

data class AdminSupportConversationRow(
    @SerializedName("conversation_id") val conversationId: Int,
    @SerializedName("affiliate_id") val affiliateId: Int,
    @SerializedName("name") val name: String,
    @SerializedName("affiliate_code") val affiliateCode: String,
    @SerializedName("status") val status: String,
    @SerializedName("last_message_at") val lastMessageAt: String?,
    @SerializedName("unread") val unread: Int,
    @SerializedName("last_msg") val lastMsg: String
)

data class AdminSupportMessagesResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: AdminSupportMessagesData?,
    @SerializedName("error") val error: String?
)

data class AdminSupportMessagesData(
    @SerializedName("conversation") val conversation: AdminSupportConversationState?,
    @SerializedName("messages") val messages: List<AdminSupportMessage>
)

data class AdminSupportConversationState(
    @SerializedName("id") val id: Int,
    @SerializedName("status") val status: String
)

data class AdminSupportMessage(
    @SerializedName("id") val id: Int,
    @SerializedName("sender_id") val senderId: Int,
    @SerializedName("sender_role") val senderRole: String,
    @SerializedName("sender_name") val senderName: String,
    @SerializedName("message") val message: String,
    @SerializedName("created_at") val createdAt: String,
    @SerializedName("is_read") val isRead: Int,
    @SerializedName("attachment_path") val attachmentPath: String?,
    @SerializedName("attachment_name") val attachmentName: String?,
    @SerializedName("attachment_type") val attachmentType: String?,
    @SerializedName("attachment_size") val attachmentSize: Int?,
    @SerializedName("edited_at") val editedAt: String?
)

data class AdminSupportSendRequest(
    @SerializedName("affiliate_id") val affiliateId: Int,
    @SerializedName("owner_type") val ownerType: String,
    @SerializedName("message") val message: String
)

data class AdminSupportSendResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: AdminSupportSendResponseData?,
    @SerializedName("error") val error: String?
)

data class AdminSupportSendResponseData(
    @SerializedName("message_id") val messageId: Int
)

data class AdminSupportActionRequest(
    @SerializedName("conversation_id") val conversationId: Int
)

data class AdminSupportActionResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("error") val error: String?
)
