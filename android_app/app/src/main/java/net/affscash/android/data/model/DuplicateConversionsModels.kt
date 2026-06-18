package net.affscash.android.data.model

import com.google.gson.annotations.SerializedName

data class DuplicateConversionRow(
    val id: Int,
    @SerializedName("conversion_id") val conversionId: String,
    val status: String,
    val payout: Double,
    @SerializedName("transaction_id") val transactionId: String?,
    @SerializedName("goal_name") val goalName: String?,
    @SerializedName("converted_at") val convertedAt: String
)

data class DuplicateConversionCluster(
    @SerializedName("offer_id") val offerId: Int,
    @SerializedName("offer_name") val offerName: String,
    @SerializedName("ip_address") val ipAddress: String,
    @SerializedName("dup_count") val dupCount: Int,
    val conversions: List<DuplicateConversionRow>
)

data class DuplicateConversionsData(
    @SerializedName("total_clusters") val totalClusters: Int,
    @SerializedName("total_conversions") val totalConversions: Int,
    val clusters: List<DuplicateConversionCluster>
)

data class DuplicateConversionsResponse(
    val success: Boolean,
    val data: DuplicateConversionsData?,
    val error: String?
)
