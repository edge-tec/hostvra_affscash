package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class SmartlinkResponse(
    val success: Boolean,
    val smartlinks: List<Smartlink> = emptyList(),
    val error: String? = null
)

@Serializable
data class Smartlink(
    val id: Int,
    val name: String,
    val description: String? = null,
    @SerialName("require_approval") val requireApproval: Int = 0,
    @SerialName("offer_count") val offerCount: Int,
    @SerialName("distribution_type") val distributionType: String,
    @SerialName("access_status") val accessStatus: String? = null,
    @SerialName("tracking_link") val trackingLink: String? = null
)

@Serializable
data class ApplySmartlinkRequest(
    @SerialName("smartlink_id") val smartlinkId: Int,
    @SerialName("promotion_description") val promotionDescription: String? = null
)

@Serializable
data class ApplySmartlinkResponse(
    val success: Boolean,
    val message: String? = null,
    @SerialName("new_status") val newStatus: String? = null,
    val error: String? = null
)
