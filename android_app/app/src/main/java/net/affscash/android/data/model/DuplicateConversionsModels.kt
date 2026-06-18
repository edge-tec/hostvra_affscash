package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class AffiliateDuplicateConversionRow(
    val id: Int,
    @SerialName("conversion_id") val conversionId: String,
    val status: String,
    val payout: Double,
    @SerialName("transaction_id") val transactionId: String? = null,
    @SerialName("goal_name") val goalName: String? = null,
    @SerialName("converted_at") val convertedAt: String
)

@Serializable
data class AffiliateDuplicateConversionCluster(
    @SerialName("offer_id") val offerId: Int,
    @SerialName("offer_name") val offerName: String,
    @SerialName("ip_address") val ipAddress: String,
    @SerialName("dup_count") val dupCount: Int,
    val conversions: List<AffiliateDuplicateConversionRow>
)

@Serializable
data class AffiliateDuplicateConversionsData(
    @SerialName("total_clusters") val totalClusters: Int,
    @SerialName("total_conversions") val totalConversions: Int,
    val clusters: List<AffiliateDuplicateConversionCluster>
)

@Serializable
data class AffiliateDuplicateConversionsResponse(
    val success: Boolean,
    val data: AffiliateDuplicateConversionsData? = null,
    val error: String? = null
)
