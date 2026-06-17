package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class ManagerProfileResponse(
    val success: Boolean,
    val profile: ManagerProfileData? = null,
    val payment: ManagerPaymentData? = null,
    @SerialName("two_factor_enabled") val twoFactorEnabled: Boolean = false,
    val error: String? = null
)

@Serializable
data class ManagerProfileData(
    @SerialName("first_name") val firstName: String,
    @SerialName("last_name") val lastName: String,
    val email: String,
    val company: String?,
    val phone: String?,
    val skype: String?,
    val telegram: String?,
    val discord: String?,
    @SerialName("profile_pic") val profilePic: String?
)

@Serializable
data class ManagerPaymentData(
    val method: String?,
    val details: String?
)

@Serializable
data class TwoFactorStartResponse(
    val success: Boolean,
    val secret: String? = null,
    @SerialName("qr_url") val qrUrl: String? = null,
    val error: String? = null
)

@Serializable
data class SimpleResponse(
    val success: Boolean,
    val message: String? = null,
    val error: String? = null
)
