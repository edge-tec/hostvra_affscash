package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class AdminPointsConfig(
    val enabled: Boolean,
    @SerialName("usd_per_point") val usdPerPoint: Int
)

@Serializable
data class AdminPointsBalance(
    @SerialName("affiliate_id") val affiliateId: Int,
    val name: String? = null,
    val email: String? = null,
    val balance: Int = 0,
    @SerialName("lifetime_earned") val lifetimeEarned: Int = 0,
    @SerialName("lifetime_spent") val lifetimeSpent: Int = 0,
    @SerialName("updated_at") val updatedAt: String? = null
)

@Serializable
data class AdminPointsTransaction(
    val id: Int,
    @SerialName("affiliate_id") val affiliateId: Int,
    @SerialName("aff_name") val affName: String? = null,
    val email: String? = null,
    val type: String,
    val amount: Int,
    val reason: String? = null,
    @SerialName("created_at") val createdAt: String
)

@Serializable
data class AdminPointsResponse(
    val success: Boolean,
    val config: AdminPointsConfig? = null,
    val balances: List<AdminPointsBalance> = emptyList(),
    val recent: List<AdminPointsTransaction> = emptyList(),
    val error: String? = null
)

@Serializable
data class AdminPointsActionRequest(
    val action: String,
    // For config
    val enabled: Boolean? = null,
    @SerialName("usd_per_point") val usdPerPoint: Int? = null,
    // For adjust
    @SerialName("affiliate_id") val affiliateId: Int? = null,
    val delta: Int? = null,
    val reason: String? = null,
    // For sync
    @SerialName("dry_run") val dryRun: Boolean? = null,
    val since: String? = null
)

@Serializable
data class AdminPointsSyncResponse(
    val success: Boolean,
    val message: String? = null,
    val log: List<String> = emptyList(),
    val error: String? = null
)
