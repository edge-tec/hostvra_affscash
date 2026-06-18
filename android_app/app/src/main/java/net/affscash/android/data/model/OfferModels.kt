package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class OfferResponse(
    val success: Boolean,
    val offers: List<Offer> = emptyList(),
    val error: String? = null
)

@Serializable
data class OfferDetailsResponse(
    val success: Boolean,
    val offer: OfferDetails? = null,
    val error: String? = null
)

@Serializable
data class Offer(
    val id: Int,
    val name: String,
    val description: String? = null,
    @SerialName("payout_type") val payoutType: String,
    val payout: Double,
    @SerialName("preview_url") val previewUrl: String? = null,
    val category: String? = null,
    @SerialName("offer_type") val offerType: String? = null,
    @SerialName("require_approval") val requireApproval: Int = 0,
    val countries: String? = null,
    val devices: String? = null,
    @SerialName("access_status") val accessStatus: String? = null
)

@Serializable
data class ApplyOfferRequest(
    @SerialName("offer_id") val offerId: Int,
    @SerialName("promotion_description") val promotionDescription: String? = null
)

@Serializable
data class ApplyOfferResponse(
    val success: Boolean,
    val message: String? = null,
    @SerialName("new_status") val newStatus: String? = null,
    val error: String? = null
)

@Serializable
data class OfferDetails(
    val id: Int,
    val name: String,
    val description: String? = null,
    @SerialName("payout_type") val payoutType: String,
    val payout: Double,
    @SerialName("preview_url") val previewUrl: String? = null,
    val countries: String? = null,
    val devices: String? = null,
    @SerialName("tracking_link") val trackingLink: String
)
