package net.affscash.android.data.model

import com.google.gson.annotations.SerializedName

data class VpnLogItem(
    @SerializedName("id") val id: Int,
    @SerializedName("affiliate_id") val affiliateId: Int?,
    @SerializedName("offer_id") val offerId: Int?,
    @SerializedName("offer_name") val offerName: String?,
    @SerializedName("ip_address") val ipAddress: String,
    @SerializedName("detection_type") val detectionType: String,
    @SerializedName("user_agent") val userAgent: String?,
    @SerializedName("country") val country: String,
    @SerializedName("blocked_at") val blockedAt: String,
    @SerializedName("aff_name") val affName: String?,
    @SerializedName("affiliate_code") val affiliateCode: String?
)

data class VpnLogTypeCount(
    @SerializedName("detection_type") val detectionType: String,
    @SerializedName("cnt") val count: Int
)

data class VpnLogStats(
    @SerializedName("total_last_30_days") val totalLast30Days: Int,
    @SerializedName("today_blocked") val todayBlocked: Int,
    @SerializedName("vpn_hosting_count") val vpnHostingCount: Int,
    @SerializedName("proxy_count") val proxyCount: Int,
    @SerializedName("types") val types: List<VpnLogTypeCount>
)

data class VpnLogListResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: List<VpnLogItem>?,
    @SerializedName("error") val error: String?
)

data class VpnLogStatsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("data") val data: VpnLogStats?,
    @SerializedName("error") val error: String?
)
