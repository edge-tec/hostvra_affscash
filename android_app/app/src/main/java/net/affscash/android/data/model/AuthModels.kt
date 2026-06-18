package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class AuthRequest(
    val email: String,
    val password: String,
    val role: String = "affiliate"
)

@Serializable
data class AuthResponse(
    val success: Boolean,
    val error: String? = null,
    val user: User? = null
)

@Serializable
data class User(
    val id: Int,
    @SerialName("first_name") val firstName: String,
    @SerialName("last_name") val lastName: String,
    val email: String,
    val role: String,
    val status: String
)

@Serializable
data class ForgotPasswordRequest(
    val email: String
)

@Serializable
data class VerifyOtpRequest(
    val email: String,
    val otp: String
)

@Serializable
data class VerifyOtpResponse(
    val success: Boolean,
    val error: String? = null,
    val token: String? = null
)

@Serializable
data class ResetPasswordRequest(
    val email: String,
    val token: String,
    val password: String
)

@Serializable
data class BasicResponse(
    val success: Boolean,
    val error: String? = null,
    val message: String? = null
)
