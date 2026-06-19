package net.affscash.android.data.model

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class VpnLogItem(
    @SerialName("id") val id: Int,
    @SerialName("affiliate_id") val affiliateId: Int? = null,
    @SerialName("offer_id") val offerId: Int? = null,
    @SerialName("offer_name") val offerName: String? = null,
    @SerialName("ip_address") val ipAddress: String,
    @SerialName("detection_type") val detectionType: String,
    @SerialName("user_agent") val userAgent: String? = null,
    @SerialName("country") val country: String,
    @SerialName("blocked_at") val blockedAt: String,
    @SerialName("aff_name") val affName: String? = null,
    @SerialName("affiliate_code") val affiliateCode: String? = null
)

@Serializable
data class VpnLogTypeCount(
    @SerialName("detection_type") val detectionType: String,
    @SerialName("cnt") val count: Int
)

@Serializable
data class VpnLogStats(
    @SerialName("total_last_30_days") val totalLast30Days: Int = 0,
    @SerialName("today_blocked") val todayBlocked: Int = 0,
    @SerialName("vpn_hosting_count") val vpnHostingCount: Int = 0,
    @SerialName("proxy_count") val proxyCount: Int = 0,
    @SerialName("types") val types: List<VpnLogTypeCount> = emptyList()
)

@Serializable
data class VpnLogListResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("data") val data: List<VpnLogItem>? = null,
    @SerialName("error") val error: String? = null
)

@Serializable
data class VpnLogStatsResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("data") val data: VpnLogStats? = null,
    @SerialName("error") val error: String? = null
)
