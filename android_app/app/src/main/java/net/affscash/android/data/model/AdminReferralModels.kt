package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class AdminReferralDashboardResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("data") val data: AdminReferralDashboardData? = null
)

@Serializable
data class AdminReferralDashboardData(
    @SerialName("total_signups") val totalSignups: Int,
    @SerialName("total_codes") val totalCodes: Int,
    @SerialName("total_commission_paid") val totalCommissionPaid: Double,
    @SerialName("pending_commissions") val pendingCommissions: Int,
    @SerialName("commission_rate") val commissionRate: String? = null,
    @SerialName("commission_type") val commissionType: String? = null
)

@Serializable
data class AdminReferralSignupsResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("data") val data: AdminReferralSignupsData? = null
)

@Serializable
data class AdminReferralSignupsData(
    @SerialName("signups") val signups: List<AdminReferralSignup> = emptyList()
)

@Serializable
data class AdminReferralSignup(
    @SerialName("id") val id: Int,
    @SerialName("referrer_name") val referrerName: String? = null,
    @SerialName("referrer_email") val referrerEmail: String? = null,
    @SerialName("referrer_code") val referrerCode: String? = null,
    @SerialName("referrer_role") val referrerRole: String? = null,
    @SerialName("referred_name") val referredName: String? = null,
    @SerialName("referred_email") val referredEmail: String? = null,
    @SerialName("referred_status") val referredStatus: String? = null,
    @SerialName("referred_aff_code") val referredAffCode: String? = null,
    @SerialName("referred_balance") val referredBalance: Double? = null,
    @SerialName("created_at") val createdAt: String? = null
)

@Serializable
data class AdminReferralCommissionsResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("data") val data: AdminReferralCommissionsData? = null
)

@Serializable
data class AdminReferralCommissionsData(
    @SerialName("commissions") val commissions: List<AdminReferralCommission> = emptyList(),
    @SerialName("totals") val totals: AdminReferralCommissionTotals? = null
)

@Serializable
data class AdminReferralCommissionTotals(
    @SerialName("total") val total: Int,
    @SerialName("approved") val approved: Int,
    @SerialName("pending") val pending: Int,
    @SerialName("amount") val amount: Double
)

@Serializable
data class AdminReferralCommission(
    @SerialName("id") val id: Int,
    @SerialName("referrer_name") val referrerName: String? = null,
    @SerialName("referrer_code") val referrerCode: String? = null,
    @SerialName("referred_name") val referredName: String? = null,
    @SerialName("referred_aff_code") val referredAffCode: String? = null,
    @SerialName("base_payout") val basePayout: Double? = null,
    @SerialName("commission_rate") val commissionRate: String? = null,
    @SerialName("commission_type") val commissionType: String? = null,
    @SerialName("commission_amount") val commissionAmount: Double? = null,
    @SerialName("status") val status: String? = null,
    @SerialName("created_at") val createdAt: String? = null
)

@Serializable
data class AdminReferralCodesResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("data") val data: AdminReferralCodesData? = null
)

@Serializable
data class AdminReferralCodesData(
    @SerialName("codes") val codes: List<AdminReferralCode> = emptyList()
)

@Serializable
data class AdminReferralCode(
    @SerialName("id") val id: Int,
    @SerialName("user_name") val userName: String? = null,
    @SerialName("email") val email: String? = null,
    @SerialName("role") val role: String? = null,
    @SerialName("code") val code: String? = null,
    @SerialName("signup_count") val signupCount: Int? = null,
    @SerialName("total_earned") val totalEarned: Double? = null,
    @SerialName("created_at") val createdAt: String? = null
)

@Serializable
data class AdminReferralActionRequest(
    @SerialName("commission_id") val commissionId: Int
)
