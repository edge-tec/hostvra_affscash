package net.affscash.android.data.model

import com.google.gson.annotations.SerializedName

// --- Common Referral Models ---
data class ReferredAffiliate(
    @SerializedName("joined_at") val joinedAt: String,
    val name: String,
    val email: String,
    val status: String,
    @SerializedName("affiliate_id") val affiliateId: Int,
    @SerializedName("affiliate_code") val affiliateCode: String,
    val balance: Double,
    @SerializedName("conv_count") val convCount: Int,
    @SerializedName("click_count") val clickCount: Int?,
    @SerializedName("total_earned") val totalEarned: Double?
)

data class ReferralCommission(
    val id: Int,
    @SerializedName("conversion_id") val conversionId: String,
    @SerializedName("base_payout") val basePayout: Double,
    @SerializedName("commission_rate") val commissionRate: Double,
    @SerializedName("commission_type") val commissionType: String,
    @SerializedName("commission_amount") val commissionAmount: Double,
    val status: String,
    @SerializedName("created_at") val createdAt: String,
    @SerializedName("referred_name") val referredName: String,
    @SerializedName("referred_code") val referredCode: String
)

// --- Affiliate Referral Response ---
data class AffiliateReferralStats(
    @SerializedName("total_referrals") val totalReferrals: Int,
    @SerializedName("commissions_earned") val commissionsEarned: Double,
    @SerializedName("commission_rate") val commissionRate: String,
    @SerializedName("commission_type") val commissionType: String
)

data class AffiliateReferralData(
    @SerializedName("referral_code") val referralCode: String,
    @SerializedName("referral_link") val referralLink: String,
    val stats: AffiliateReferralStats,
    @SerializedName("referred_affiliates") val referredAffiliates: List<ReferredAffiliate>,
    val commissions: List<ReferralCommission>
)

data class AffiliateReferralResponse(
    val success: Boolean,
    val data: AffiliateReferralData?,
    val error: String?
)

// --- Manager Referral Response ---
data class ManagerReferralStats(
    @SerializedName("total_referrals") val totalReferrals: Int,
    @SerializedName("active_referrals") val activeReferrals: Int,
    @SerializedName("total_earned") val totalEarned: Double
)

data class ManagerReferralData(
    @SerializedName("referral_code") val referralCode: String,
    @SerializedName("referral_link") val referralLink: String,
    val stats: ManagerReferralStats,
    @SerializedName("referred_affiliates") val referredAffiliates: List<ReferredAffiliate>
)

data class ManagerReferralResponse(
    val success: Boolean,
    val data: ManagerReferralData?,
    val error: String?
)
