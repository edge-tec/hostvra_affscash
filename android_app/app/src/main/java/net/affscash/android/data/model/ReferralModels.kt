package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

// --- Common Referral Models ---
@Serializable
data class ReferredAffiliate(
    @SerialName("joined_at") val joinedAt: String,
    val name: String,
    val email: String,
    val status: String,
    @SerialName("affiliate_id") val affiliateId: Int,
    @SerialName("affiliate_code") val affiliateCode: String,
    val balance: Double,
    @SerialName("conv_count") val convCount: Int,
    @SerialName("click_count") val clickCount: Int? = null,
    @SerialName("total_earned") val totalEarned: Double? = null
)

@Serializable
data class ReferralCommission(
    val id: Int,
    @SerialName("conversion_id") val conversionId: String,
    @SerialName("base_payout") val basePayout: Double,
    @SerialName("commission_rate") val commissionRate: Double,
    @SerialName("commission_type") val commissionType: String,
    @SerialName("commission_amount") val commissionAmount: Double,
    val status: String,
    @SerialName("created_at") val createdAt: String,
    @SerialName("referred_name") val referredName: String,
    @SerialName("referred_code") val referredCode: String
)

// --- Affiliate Referral Response ---
@Serializable
data class AffiliateReferralStats(
    @SerialName("total_referrals") val totalReferrals: Int,
    @SerialName("commissions_earned") val commissionsEarned: Double,
    @SerialName("commission_rate") val commissionRate: String,
    @SerialName("commission_type") val commissionType: String
)

@Serializable
data class AffiliateReferralData(
    @SerialName("referral_code") val referralCode: String,
    @SerialName("referral_link") val referralLink: String,
    val stats: AffiliateReferralStats,
    @SerialName("referred_affiliates") val referredAffiliates: List<ReferredAffiliate>,
    val commissions: List<ReferralCommission>
)

@Serializable
data class AffiliateReferralResponse(
    val success: Boolean,
    val data: AffiliateReferralData? = null,
    val error: String? = null
)

// --- Manager Referral Response ---
@Serializable
data class ManagerReferralStats(
    @SerialName("total_referrals") val totalReferrals: Int,
    @SerialName("active_referrals") val activeReferrals: Int,
    @SerialName("total_earned") val totalEarned: Double
)

@Serializable
data class ManagerReferralData(
    @SerialName("referral_code") val referralCode: String,
    @SerialName("referral_link") val referralLink: String,
    val stats: ManagerReferralStats,
    @SerialName("referred_affiliates") val referredAffiliates: List<ReferredAffiliate>
)

@Serializable
data class ManagerReferralResponse(
    val success: Boolean,
    val data: ManagerReferralData? = null,
    val error: String? = null
)
