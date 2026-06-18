package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class GlobalPostbackInfo(
    val url: String? = null,
    val status: String? = null
)

@Serializable
data class SettingsLoadResponse(
    val success: Boolean,
    val profile: ProfileInfo? = null,
    val payment: PaymentInfo? = null,
    val manager: ManagerInfo? = null,
    @SerialName("payment_methods") val paymentMethods: List<String> = emptyList(),
    @SerialName("two_factor_enabled") val twoFactorEnabled: Boolean = false,
    @SerialName("delete_request") val deleteRequest: DeleteRequestInfo? = null,
    @SerialName("global_postback") val globalPostback: GlobalPostbackInfo? = null,
    val error: String? = null
)

@Serializable
data class UpdateGlobalPostbackRequest(
    @SerialName("global_postback_url") val globalPostbackUrl: String
)

@Serializable
data class DeleteRequestInfo(
    val status: String,
    @SerialName("requested_at") val requestedAt: String,
    val reason: String
)

@Serializable
data class ProfileInfo(
    @SerialName("first_name") val firstName: String? = null,
    @SerialName("last_name") val lastName: String? = null,
    val email: String? = null,
    val company: String? = null,
    val phone: String? = null
)

@Serializable
data class PaymentInfo(
    val method: String?,
    val details: String?
)

@Serializable
data class ManagerInfo(
    @SerialName("first_name") val firstName: String? = null,
    @SerialName("last_name") val lastName: String? = null,
    val email: String? = null,
    val company: String? = null,
    val phone: String? = null,
    val skype: String? = null,
    val telegram: String? = null
)

@Serializable
data class SettingsActionResponse(
    val success: Boolean,
    val message: String? = null,
    val error: String? = null,
    val secret: String? = null,
    @SerialName("qr_url") val qrUrl: String? = null
)

@Serializable
data class UpdateProfileRequest(
    @SerialName("first_name") val firstName: String,
    @SerialName("last_name") val lastName: String,
    val company: String,
    val phone: String
)

@Serializable
data class UpdateSecurityRequest(
    @SerialName("current_password") val currentPassword: String,
    @SerialName("new_password") val newPassword: String
)

@Serializable
data class UpdatePaymentRequest(
    @SerialName("payment_method") val paymentMethod: String,
    @SerialName("payment_details") val paymentDetails: String
)

@Serializable
data class TwoFactorVerifyRequest(
    val secret: String,
    val code: String
)

@Serializable
data class TwoFactorDisableRequest(
    val password: String,
    val code: String
)

@Serializable
data class DeleteAccountRequest(
    val reason: String
)
