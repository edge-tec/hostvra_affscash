package net.affscash.android.data.model

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class PaymentMethodItem(
    @SerialName("id") val id: Int,
    @SerialName("name") val name: String,
    @SerialName("description") val description: String? = null,
    @SerialName("instructions") val instructions: String? = null,
    @SerialName("method_type") val methodType: String,
    @SerialName("is_active") val isActive: Int,
    @SerialName("is_default") val isDefault: Int
)

@Serializable
data class AffiliatePaymentInfo(
    @SerialName("id") val id: Int,
    @SerialName("name") val name: String,
    @SerialName("email") val email: String,
    @SerialName("payment_terms") val paymentTerms: String,
    @SerialName("allow_email_change") val allowEmailChange: Int,
    @SerialName("payment_method") val paymentMethod: String? = null,
    @SerialName("payment_details") val paymentDetails: String? = null
)

@Serializable
data class ManagerPaymentInfo(
    @SerialName("mgr_id") val mgrId: Int,
    @SerialName("user_id") val userId: Int,
    @SerialName("name") val name: String,
    @SerialName("email") val email: String,
    @SerialName("commission_rate") val commissionRate: Double,
    @SerialName("payment_method") val paymentMethod: String? = null,
    @SerialName("payment_details") val paymentDetails: String? = null
)

@Serializable
data class OfferCommissionInfo(
    @SerialName("id") val id: Int,
    @SerialName("commission_rate") val commissionRate: Double,
    @SerialName("mgr_id") val mgrId: Int,
    @SerialName("manager_name") val managerName: String,
    @SerialName("offer_id") val offerId: Int,
    @SerialName("offer_name") val offerName: String
)

@Serializable
data class OfferBasicItem(
    @SerialName("id") val id: Int,
    @SerialName("name") val name: String
)

@Serializable
data class PaymentSettingsData(
    @SerialName("payment_methods") val paymentMethods: List<PaymentMethodItem>,
    @SerialName("affiliates") val affiliates: List<AffiliatePaymentInfo>,
    @SerialName("managers") val managers: List<ManagerPaymentInfo>,
    @SerialName("offers") val offers: List<OfferBasicItem>,
    @SerialName("offer_commissions") val offerCommissions: List<OfferCommissionInfo>
)

@Serializable
data class PaymentSettingsResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("data") val data: PaymentSettingsData? = null,
    @SerialName("error") val error: String? = null
)

@Serializable
data class PaymentTermsRequest(
    @SerialName("payment_terms") val paymentTerms: String,
    @SerialName("apply_to") val applyTo: String,
    @SerialName("affiliate_ids") val affiliateIds: List<Int>? = null
)

@Serializable
data class PayoutInfoRequest(
    @SerialName("payout_type") val type: String,
    @SerialName("entity_id") val id: Int,
    @SerialName("payment_method") val paymentMethod: String,
    @SerialName("pd_method_type") val pdMethodType: String = "custom",
    @SerialName("payment_details") val paymentDetails: String
)
