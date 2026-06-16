package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class AdminOfferResponse(
    val success: Boolean,
    val data: List<AdminOffer> = emptyList(),
    val error: String? = null
)

@Serializable
data class AdminOffer(
    val id: Int,
    val name: String,
    val description: String? = null,
    @SerialName("payout_type") val payoutType: String,
    val payout: Double,
    val status: String,
    val category: String? = null,
    @SerialName("adv_name") val advName: String,
    @SerialName("created_at") val createdAt: String
)

@Serializable
data class ManagerOfferResponse(
    val success: Boolean,
    val data: List<ManagerOffer> = emptyList(),
    val error: String? = null
)

@Serializable
data class ManagerOffer(
    val id: Int,
    val name: String,
    val description: String? = null,
    @SerialName("payout_type") val payoutType: String,
    val payout: Double,
    val status: String,
    val category: String? = null,
    @SerialName("adv_name") val advName: String,
    @SerialName("aff_count") val affCount: Int,
    @SerialName("created_at") val createdAt: String
)
