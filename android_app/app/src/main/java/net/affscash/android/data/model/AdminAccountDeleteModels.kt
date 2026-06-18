package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class AdminAccountDeleteStats(
    val pending: Int = 0,
    val approved: Int = 0,
    val rejected: Int = 0,
    val total: Int = 0
)

@Serializable
data class AdminAccountDeleteRequestItem(
    val id: String,
    @SerialName("affiliate_id") val affiliateId: String,
    @SerialName("user_id") val userId: String,
    val email: String? = null,
    @SerialName("first_name") val firstName: String? = null,
    @SerialName("last_name") val lastName: String? = null,
    @SerialName("affiliate_code") val affiliateCode: String? = null,
    val balance: String? = null,
    val reason: String,
    val status: String,
    @SerialName("admin_note") val adminNote: String? = null,
    @SerialName("requested_at") val requestedAt: String,
    @SerialName("reviewed_at") val reviewedAt: String? = null,
    @SerialName("reviewed_by_name") val reviewedByName: String? = null
)

@Serializable
data class AdminAccountDeleteResponse(
    val success: Boolean,
    val requests: List<AdminAccountDeleteRequestItem> = emptyList(),
    val stats: AdminAccountDeleteStats? = null,
    val error: String? = null
)

@Serializable
data class AdminAccountDeleteActionRequest(
    val action: String = "update_status",
    @SerialName("request_id") val requestId: String,
    val decision: String,
    @SerialName("admin_note") val adminNote: String
)
