package com.example.affscash.data.model

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
    @SerialName("preview_url") val previewUrl: String? = null
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
