package net.affscash.android.data.model

import com.google.gson.annotations.SerializedName

data class AffiliateDuplicateConversionRow(
    val id: Int,
    @SerializedName("conversion_id") val conversionId: String,
    val status: String,
    val payout: Double,
    @SerializedName("transaction_id") val transactionId: String?,
    @SerializedName("goal_name") val goalName: String?,
    @SerializedName("converted_at") val convertedAt: String
)

data class AffiliateDuplicateConversionCluster(
    @SerializedName("offer_id") val offerId: Int,
    @SerializedName("offer_name") val offerName: String,
    @SerializedName("ip_address") val ipAddress: String,
    @SerializedName("dup_count") val dupCount: Int,
    val conversions: List<AffiliateDuplicateConversionRow>
)

data class AffiliateDuplicateConversionsData(
    @SerializedName("total_clusters") val totalClusters: Int,
    @SerializedName("total_conversions") val totalConversions: Int,
    val clusters: List<AffiliateDuplicateConversionCluster>
)

data class AffiliateDuplicateConversionsResponse(
    val success: Boolean,
    val data: AffiliateDuplicateConversionsData?,
    val error: String?
)
