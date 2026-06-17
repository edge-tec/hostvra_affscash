package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class ManagerAffiliatesResponse(
    val success: Boolean,
    val data: ManagerAffiliatesData? = null,
    val error: String? = null
)

@Serializable
data class ManagerAffiliatesData(
    val affiliates: List<ManagerAffiliateListModel> = emptyList(),
    @SerialName("can_approve") val canApprove: Boolean = false
)

@Serializable
data class ManagerAffiliateListModel(
    @SerialName("user_id") val userId: Int,
    @SerialName("aff_id") val affId: Int,
    val email: String,
    @SerialName("first_name") val firstName: String,
    @SerialName("last_name") val lastName: String,
    val company: String? = null,
    val status: String,
    @SerialName("created_at") val createdAt: String,
    @SerialName("affiliate_code") val affiliateCode: String,
    val balance: String? = null,
    @SerialName("fraud_score") val fraudScore: Double = 0.0,
    @SerialName("fraud_checked_count") val fraudCheckedCount: Int = 0
)

@Serializable
data class ManagerAffiliateDetailsResponse(
    val success: Boolean,
    val data: ManagerAffiliateDetailsData? = null,
    val error: String? = null
)

@Serializable
data class ManagerAffiliateDetailsData(
    val affiliate: ManagerAffiliateProfile,
    val stats: ManagerAffiliateStats? = null
)

@Serializable
data class ManagerAffiliateProfile(
    val id: Int,
    val email: String,
    @SerialName("first_name") val firstName: String,
    @SerialName("last_name") val lastName: String,
    val company: String? = null,
    val phone: String? = null,
    val country: String? = null,
    val status: String,
    @SerialName("aff_id") val affId: Int,
    @SerialName("affiliate_code") val affiliateCode: String,
    val balance: String? = null,
    @SerialName("fraud_score") val fraudScore: Double = 0.0,
    @SerialName("payment_method") val paymentMethod: String? = null,
    @SerialName("payment_threshold") val paymentThreshold: String? = null
)

@Serializable
data class ManagerAffiliateStats(
    val clicks: Int = 0,
    val conv: Int = 0,
    val approved: Int = 0,
    val payout: Double = 0.0
)

@Serializable
data class CreateAffiliateRequest(
    @SerialName("first_name") val firstName: String,
    @SerialName("last_name") val lastName: String,
    val email: String,
    val password: String,
    val company: String? = null,
    val phone: String? = null,
    val country: String? = null,
    val status: String = "active"
)

@Serializable
data class EditAffiliateRequest(
    val id: Int,
    @SerialName("first_name") val firstName: String,
    @SerialName("last_name") val lastName: String,
    val company: String? = null,
    val phone: String? = null,
    val country: String? = null
)

@Serializable
data class UpdateAffiliateStatusRequest(
    @SerialName("aff_id") val affId: Int,
    val status: String
)

@Serializable
data class ImpersonateAffiliateRequest(
    @SerialName("aff_id") val affId: Int
)

@Serializable
data class ImpersonateResponse(
    val success: Boolean,
    val role: String? = null,
    val user: User? = null,
    val message: String? = null,
    val error: String? = null
)

@Serializable
data class StopImpersonateResponse(
    val success: Boolean,
    val role: String? = null,
    val user: User? = null,
    val message: String? = null,
    val error: String? = null
)

@Serializable
data class BasicManagerActionResponse(
    val success: Boolean,
    val message: String? = null,
    val error: String? = null
)
