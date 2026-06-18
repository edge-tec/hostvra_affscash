package net.affscash.android.data.model

import kotlinx.serialization.Serializable

@Serializable
data class AdminAutoHideStatsResponse(
    val status: String,
    val stats: AdminAutoHideStats? = null
)

@Serializable
data class AdminAutoHideStats(
    val total_hidden: Int,
    val payout_saved: Double,
    val active_rules: Int,
    val total_rules: Int
)

@Serializable
data class AdminAutoHideRulesResponse(
    val status: String,
    val rules: List<AdminAutoHideRule> = emptyList()
)

@Serializable
data class AdminAutoHideRule(
    val id: Int,
    val name: String,
    val type: String,
    val offer_id: Int?,
    val affiliate_id: Int?,
    val offer_name: String?,
    val aff_name: String?,
    val affiliate_code: String?,
    val hide_percent: String,
    val reason: String?,
    val is_active: Int,
    val created_at: String,
    val activated_at: String?
)

@Serializable
data class AdminAutoHideConversionsResponse(
    val status: String,
    val conversions: List<AdminAutoHideConversion> = emptyList()
)

@Serializable
data class AdminAutoHideConversion(
    val id: Int,
    val conversion_id: String,
    val status: String,
    val payout: String,
    val revenue: String,
    val converted_at: String,
    val hide_reason: String?,
    val offer_name: String?,
    val aff_name: String?,
    val affiliate_code: String?
)

@Serializable
data class AdminAutoHideCreateRequest(
    val name: String,
    val type: String,
    val offer_id: Int? = null,
    val affiliate_id: Int? = null,
    val hide_percent: Double,
    val reason: String = "",
    val apply_existing: Boolean = false
)

@Serializable
data class AdminAutoHideActionRequest(
    val rule_id: Int? = null,
    val conversion_id: String? = null
)

@Serializable
data class AdminAutoHideActionResponse(
    val status: String,
    val message: String? = null
)
