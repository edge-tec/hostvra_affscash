package com.example.affscash.data.model

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
