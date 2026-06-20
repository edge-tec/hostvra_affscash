package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class ConversionResponse(
    val success: Boolean,
    val data: List<Conversion> = emptyList(),
    val error: String? = null
)

@Serializable
data class Conversion(
    @SerialName("conversion_id") val conversionId: String,
    @SerialName("click_id") val clickId: String,
    @SerialName("offer_id") val offerId: Int,
    @SerialName("affiliate_id") val affiliateId: Int,
    val payout: Double,
    val revenue: Double,
    val status: String,
    val country: String? = null,
    @SerialName("ip_address") val ipAddress: String,
    @SerialName("postback_sent") val postbackSent: Int,
    @SerialName("converted_at") val convertedAt: String,
    @SerialName("fraud_score") val fraudScore: Int? = null,
    @SerialName("offer_name") val offerName: String? = null,
    @SerialName("aff_name") val affName: String? = null,
    @SerialName("affiliate_code") val affiliateCode: String? = null,
    @SerialName("source") val source: String? = null,
    @SerialName("device_type") val deviceType: String? = null,
    @SerialName("os") val os: String? = null,
    @SerialName("city") val city: String? = null,
    @SerialName("region") val region: String? = null
)
