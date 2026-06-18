package net.affscash.android.data.model

import kotlinx.serialization.Serializable

@Serializable
data class AdminAffiliateReportResponse(
    val status: String,
    val rows: List<AdminAffiliateReportRow> = emptyList(),
    val ipqs_stats: Map<String, AdminAffiliateReportIpqsStats> = emptyMap(),
    val traffic_detail: List<AdminAffiliateReportTrafficRow> = emptyList(),
    val message: String? = null
)

@Serializable
data class AdminAffiliateReportRow(
    val affiliate_id: Int,
    val affiliate_code: String,
    val aff_name: String,
    val email: String,
    val user_status: String,
    val total_clicks: String,
    val unique_clicks: String,
    val fraud_clicks: String,
    val blocked_clicks: String,
    val conv_total: String,
    val conv_approved: String,
    val conv_pending: String,
    val conv_rejected: String,
    val payout: String,
    val revenue: String,
    val last_click_at: String?,
    val last_conv_at: String?,
    val last_login: String?,
    val registered_at: String?
)

@Serializable
data class AdminAffiliateReportIpqsStats(
    val affiliate_id: Int,
    val total_checked: Int,
    val avg_score: String?,
    val high_risk: Int,
    val medium_risk: Int,
    val low_risk: Int
)

@Serializable
data class AdminAffiliateReportTrafficRow(
    val click_id: String,
    val sub1: String?,
    val sub2: String?,
    val sub3: String?,
    val sub4: String?,
    val sub5: String?,
    val ip_address: String,
    val country: String,
    val city: String?,
    val region: String?,
    val device_type: String,
    val os: String,
    val browser: String,
    val user_agent: String,
    val is_fraud: Int,
    val fraud_score: String?,
    val click_status: String,
    val clicked_at: String,
    val offer_name: String?,
    val conv_status: String?,
    val conv_payout: String?,
    val conv_revenue: String?
)
