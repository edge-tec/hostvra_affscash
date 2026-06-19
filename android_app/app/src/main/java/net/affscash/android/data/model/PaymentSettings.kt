package net.affscash.android.data.model

import com.google.gson.annotations.SerializedName

data class PaymentMethodItem(
    @SerializedName("id") val id: Int,
    @SerializedName("name") val name: String,
    @SerializedName("description") val description: String?,
    @SerializedName("instructions") val instructions: String?,
    @SerializedName("method_type") val methodType: String,
    @SerializedName("is_active") val isActive: Int,
    @SerializedName("is_default") val isDefault: Int
)

data class AffiliatePaymentInfo(
    @SerializedName("id") val id: Int,
    @SerializedName("name") val name: String,
    @SerializedName("email") val email: String,
    @SerializedName("payment_terms") val paymentTerms: String,
    @SerializedName("allow_email_change") val allowEmailChange: Int,
    @SerializedName("payment_method") val paymentMethod: String?,
    @SerializedName("payment_details") val paymentDetails: String?
)

data class ManagerPaymentInfo(
    @SerializedName("mgr_id") val mgrId: Int,
    @SerializedName("user_id") val userId: Int,
    @SerializedName("name") val name: String,
    @SerializedName("email") val email: String,
    @SerializedName("commission_rate") val commissionRate: Double,
    @SerializedName("payment_method") val paymentMethod: String?,
    @SerializedName("payment_details") val paymentDetails: String?
)

data class OfferCommissionInfo(
    @SerializedName("id") val id: Int,
    @SerializedName("commission_rate") val commissionRate: Double,
    @SerializedName("mgr_id") val mgrId: Int,
    @SerializedName("manager_name") val managerName: String,
    @SerializedName("offer_id") val offerId: Int,
    @SerializedName("offer_name") val offerName: String
)

data class OfferBasicItem(
    @SerializedName("id") val id: Int,
    @SerializedName("name") val name: String
)

data class PaymentSettingsData(
    @SerializedName("payment_methods") val paymentMethods: List<PaymentMethodItem>,
    @SerializedName("affiliates") val affiliates: List<AffiliatePaymentInfo>,
    @SerializedName("managers") val managers: List<ManagerPaymentInfo>,
    @SerializedName("offers") val offers: List<OfferBasicItem>,
    @SerializedName("offer_commissions") val offerCommissions: List<OfferCommissionInfo>
)

data class PaymentSettingsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: PaymentSettingsData?,
    @SerializedName("error") val error: String?
)
