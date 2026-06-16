package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class AdminAffiliateResponse(
    val success: Boolean,
    val data: List<AdminAffiliate> = emptyList(),
    val error: String? = null
)

@Serializable
data class AdminAffiliate(
    @SerialName("user_id") val userId: Int,
    val email: String,
    @SerialName("first_name") val firstName: String,
    @SerialName("last_name") val lastName: String,
    val company: String? = null,
    val status: String,
    @SerialName("last_login") val lastLogin: String? = null,
    @SerialName("days_inactive") val daysInactive: Int? = null,
    @SerialName("affiliate_code") val affiliateCode: String,
    val balance: Double,
    @SerialName("fraud_score") val fraudScore: Int,
    @SerialName("created_at") val createdAt: String
)

@Serializable
data class ManagerAffiliateResponse(
    val success: Boolean,
    val data: List<ManagerAffiliate> = emptyList(),
    val error: String? = null
)

@Serializable
data class ManagerAffiliate(
    @SerialName("user_id") val userId: Int,
    val email: String,
    @SerialName("first_name") val firstName: String,
    @SerialName("last_name") val lastName: String,
    val company: String? = null,
    val status: String,
    @SerialName("aff_id") val affId: Int,
    @SerialName("last_login") val lastLogin: String? = null,
    @SerialName("days_inactive") val daysInactive: Int? = null,
    @SerialName("affiliate_code") val affiliateCode: String,
    val balance: Double,
    @SerialName("fraud_score") val fraudScore: Int,
    @SerialName("created_at") val createdAt: String
)

@Serializable
data class AffiliateActionRequest(
    val action: String,
    @SerialName("user_id") val userId: Int? = null
)

@Serializable
data class ManagerAffiliateActionRequest(
    val action: String,
    @SerialName("aff_id") val affId: Int? = null
)

@Serializable
data class ImpersonateResponse(
    val success: Boolean,
    val role: String? = null,
    val user: User? = null,
    val error: String? = null
)
