package net.affscash.android.data.model

import com.google.gson.annotations.SerializedName
import java.io.Serializable

data class AdminReferralDashboardResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: AdminReferralDashboardData?
) : Serializable

data class AdminReferralDashboardData(
    @SerializedName("total_signups") val totalSignups: Int,
    @SerializedName("total_codes") val totalCodes: Int,
    @SerializedName("total_commission_paid") val totalCommissionPaid: Double,
    @SerializedName("pending_commissions") val pendingCommissions: Int,
    @SerializedName("commission_rate") val commissionRate: String?,
    @SerializedName("commission_type") val commissionType: String?
) : Serializable

data class AdminReferralSignupsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: AdminReferralSignupsData?
) : Serializable

data class AdminReferralSignupsData(
    @SerializedName("signups") val signups: List<AdminReferralSignup>
) : Serializable

data class AdminReferralSignup(
    @SerializedName("id") val id: Int,
    @SerializedName("referrer_name") val referrerName: String?,
    @SerializedName("referrer_email") val referrerEmail: String?,
    @SerializedName("referrer_code") val referrerCode: String?,
    @SerializedName("referrer_role") val referrerRole: String?,
    @SerializedName("referred_name") val referredName: String?,
    @SerializedName("referred_email") val referredEmail: String?,
    @SerializedName("referred_status") val referredStatus: String?,
    @SerializedName("referred_aff_code") val referredAffCode: String?,
    @SerializedName("referred_balance") val referredBalance: Double?,
    @SerializedName("created_at") val createdAt: String?
) : Serializable

data class AdminReferralCommissionsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: AdminReferralCommissionsData?
) : Serializable

data class AdminReferralCommissionsData(
    @SerializedName("commissions") val commissions: List<AdminReferralCommission>,
    @SerializedName("totals") val totals: AdminReferralCommissionTotals?
) : Serializable

data class AdminReferralCommissionTotals(
    @SerializedName("total") val total: Int,
    @SerializedName("approved") val approved: Int,
    @SerializedName("pending") val pending: Int,
    @SerializedName("amount") val amount: Double
) : Serializable

data class AdminReferralCommission(
    @SerializedName("id") val id: Int,
    @SerializedName("referrer_name") val referrerName: String?,
    @SerializedName("referrer_code") val referrerCode: String?,
    @SerializedName("referred_name") val referredName: String?,
    @SerializedName("referred_aff_code") val referredAffCode: String?,
    @SerializedName("base_payout") val basePayout: Double?,
    @SerializedName("commission_rate") val commissionRate: String?,
    @SerializedName("commission_type") val commissionType: String?,
    @SerializedName("commission_amount") val commissionAmount: Double?,
    @SerializedName("status") val status: String?,
    @SerializedName("created_at") val createdAt: String?
) : Serializable

data class AdminReferralCodesResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: AdminReferralCodesData?
) : Serializable

data class AdminReferralCodesData(
    @SerializedName("codes") val codes: List<AdminReferralCode>
) : Serializable

data class AdminReferralCode(
    @SerializedName("id") val id: Int,
    @SerializedName("user_name") val userName: String?,
    @SerializedName("email") val email: String?,
    @SerializedName("role") val role: String?,
    @SerializedName("code") val code: String?,
    @SerializedName("signup_count") val signupCount: Int?,
    @SerializedName("total_earned") val totalEarned: Double?,
    @SerializedName("created_at") val createdAt: String?
) : Serializable

data class AdminReferralActionRequest(
    @SerializedName("commission_id") val commissionId: Int
) : Serializable
