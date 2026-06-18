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
