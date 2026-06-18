package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class ManagerOfferApprovalListResponse(
    val success: Boolean,
    val requests: List<ManagerOfferApprovalRequest> = emptyList(),
    @SerialName("all_offers") val allOffers: List<OfferSimple> = emptyList(),
    @SerialName("pending_count") val pendingCount: Int = 0,
    val message: String? = null
)

@Serializable
data class ManagerOfferApprovalRequest(
    @SerialName("ao_id") val aoId: Int,
    @SerialName("affiliate_id") val affiliateId: Int,
    @SerialName("offer_id") val offerId: Int,
    val status: String,
    @SerialName("promotion_description") val promotionDescription: String?,
    @SerialName("requested_at") val requestedAt: String?,
    @SerialName("approved_at") val approvedAt: String?,
    @SerialName("offer_name") val offerName: String,
    @SerialName("payout_amount") val payoutAmount: Double,
    @SerialName("payout_type") val payoutType: String,
    @SerialName("offer_category") val offerCategory: String?,
    @SerialName("affiliate_name") val affiliateName: String,
    @SerialName("affiliate_email") val affiliateEmail: String,
    @SerialName("affiliate_joined") val affiliateJoined: String?,
    @SerialName("affiliate_code") val affiliateCode: String?,
    val country: String?,
    @SerialName("traffic_sources") val trafficSources: String?,
    @SerialName("total_clicks") val totalClicks: Int,
    @SerialName("total_conversions") val totalConversions: Int
)

@Serializable
data class OfferSimple(
    val id: Int,
    val name: String
)

@Serializable
data class ReviewOfferApprovalRequest(
    val affiliate_id: Int,
    val offer_id: Int,
    val review_action: String // "approve" or "reject"
)

@Serializable
data class DefaultResponse(
    val success: Boolean,
    val message: String? = null,
    val error: String? = null
)
