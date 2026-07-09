package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class FraudConversion(
    @SerialName("conversion_id") val conversionId: String,
    @SerialName("click_id") val clickId: String? = null,
    @SerialName("offer_name") val offerName: String? = null,
    val status: String,
    val payout: String,
    @SerialName("ip_address") val ipAddress: String? = null,
    val country: String? = null,
    @SerialName("converted_at") val convertedAt: String,
    @SerialName("rejection_reason") val rejectionReason: String? = null,
    @SerialName("rejected_at") val rejectedAt: String? = null
)

@Serializable
data class FraudReportResponse(
    val success: Boolean,
    @SerialName("count_30_days") val count30Days: Int = 0,
    val conversions: List<FraudConversion> = emptyList(),
    val error: String? = null
)

// Admin Fraud Score Report Models

@Serializable
data class AdminFraudStats(
    @SerialName("total_conversions") val totalConversions: Int = 0,
    @SerialName("pending_check") val pendingCheck: Int = 0,
    @SerialName("avg_fraud_score") val avgFraudScore: Double = 0.0,
    @SerialName("max_score_seen") val maxScoreSeen: Int = 0,
    @SerialName("high_risk") val highRisk: Int = 0
)

@Serializable
data class AdminFraudFilterItem(
    val id: String,
    val name: String
)

@Serializable
data class AdminFraudConversion(
    @SerialName("conversion_id") val conversionId: String,
    @SerialName("click_id") val clickId: String? = null,
    @SerialName("affiliate_id") val affiliateId: String? = null,
    @SerialName("affiliate_code") val affiliateCode: String? = null,
    @SerialName("aff_name") val affName: String? = null,
    @SerialName("offer_id") val offerId: String? = null,
    @SerialName("offer_name") val offerName: String? = null,
    @SerialName("ip_address") val ipAddress: String? = null,
    @SerialName("fraud_score") val fraudScore: Int? = null,
    @SerialName("fraud_checked_at") val fraudCheckedAt: String? = null,
    val status: String? = null,
    val payout: String? = null,
    @SerialName("converted_at") val convertedAt: String? = null,
    @SerialName("ipquery_risk_score") val ipqueryRiskScore: Int? = null,
    @SerialName("ipquery_risk_level") val ipqueryRiskLevel: String? = null,
    @SerialName("scamalytics_score") val scamalyticsScore: Int? = null,
    @SerialName("proxycheck_score") val proxycheckScore: Int? = null,
    @SerialName("frauddefense_score") val frauddefenseScore: Int? = null,
    @SerialName("fraudlabspro_score") val fraudlabsproScore: Int? = null
)

@Serializable
data class AdminFraudReportResponse(
    val status: String,
    val stats: AdminFraudStats? = null,
    val conversions: List<AdminFraudConversion> = emptyList(),
    val affiliates: List<AdminFraudFilterItem> = emptyList(),
    val offers: List<AdminFraudFilterItem> = emptyList(),
    val message: String? = null
)

@Serializable
data class AdminFraudActionResponse(
    val status: String,
    val message: String? = null,
    val processed: Int? = null,
    val remaining: Int? = null,
    val checked: Int? = null,
    @SerialName("rejected_count") val rejectedCount: Int? = null
)

@Serializable
data class AdminFraudActionRequest(
    val action: String,
    val batch: Int? = null,
    @SerialName("conversion_id") val conversionId: String? = null,
    val status: String? = null, // approved/rejected
    @SerialName("rejection_reason") val rejectionReason: String? = null,
    @SerialName("conversion_ids") val conversionIds: List<String>? = null
)
