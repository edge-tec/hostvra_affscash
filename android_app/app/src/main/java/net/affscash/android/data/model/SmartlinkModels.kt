package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class SmartlinkResponse(
    val success: Boolean,
    val smartlinks: List<Smartlink> = emptyList(),
    val error: String? = null
)

@Serializable
data class Smartlink(
    val id: Int,
    val name: String,
    val description: String? = null,
    @SerialName("require_approval") val requireApproval: Int = 0,
    @SerialName("offer_count") val offerCount: Int,
    @SerialName("distribution_type") val distributionType: String,
    @SerialName("access_status") val accessStatus: String? = null,
    @SerialName("tracking_link") val trackingLink: String? = null
)

@Serializable
data class ApplySmartlinkRequest(
    @SerialName("smartlink_id") val smartlinkId: Int,
    @SerialName("promotion_description") val promotionDescription: String? = null
)

@Serializable
data class ApplySmartlinkResponse(
    val success: Boolean,
    val message: String? = null,
    @SerialName("new_status") val newStatus: String? = null,
    val error: String? = null
)

@Serializable
data class ManagerSmartlinkResponse(
    val success: Boolean,
    val data: ManagerSmartlinkData? = null,
    val error: String? = null
)

@Serializable
data class ManagerSmartlinkData(
    val smartlinks: List<ManagerSmartlink> = emptyList(),
    @SerialName("pending_requests_count") val pendingRequestsCount: Int = 0,
    @SerialName("managed_affiliates") val managedAffiliates: List<ManagedAffiliate> = emptyList(),
    @SerialName("tracking_url_base") val trackingUrlBase: String = ""
)

@Serializable
data class ManagerSmartlink(
    val id: Int,
    val name: String,
    val description: String? = null,
    val status: String? = null,
    @SerialName("my_approved") val myApproved: Int = 0,
    @SerialName("my_pending") val myPending: Int = 0,
    @SerialName("total_approved") val totalApproved: Int = 0,
    @SerialName("created_at") val createdAt: String? = null
)

@Serializable
data class ManagerSmartlinkRequestsResponse(
    val success: Boolean,
    val data: ManagerSmartlinkRequestsData? = null,
    val error: String? = null
)

@Serializable
data class ManagerSmartlinkRequestsData(
    val requests: List<ManagerSmartlinkRequest> = emptyList()
)

@Serializable
data class ManagerSmartlinkRequest(
    val id: Int,
    @SerialName("smartlink_id") val smartlinkId: Int,
    @SerialName("affiliate_id") val affiliateId: Int,
    val status: String? = null, // pending, approved, rejected
    @SerialName("promotion_description") val promotionDescription: String? = null,
    @SerialName("admin_note") val adminNote: String? = null,
    @SerialName("created_at") val createdAt: String,
    
    // Joined fields
    @SerialName("smartlink_name") val smartlinkName: String? = null,
    @SerialName("aff_name") val affName: String? = null,
    @SerialName("affiliate_code") val affiliateCode: String? = null,
    @SerialName("aff_email") val affEmail: String? = null
)

@Serializable
data class ReviewSmartlinkRequestAction(
    @SerialName("request_id") val requestId: Int,
    val decision: String, // approved, rejected
    @SerialName("admin_note") val adminNote: String? = null
)

@Serializable
data class ReviewSmartlinkResponse(
    val success: Boolean,
    val message: String? = null,
    val error: String? = null
)

@Serializable
data class AdminSmartlink(
    val id: Int,
    val name: String,
    val slug: String,
    @SerialName("rotation_type") val rotationType: String,
    val status: String,
    @SerialName("offer_count") val offerCount: Int? = 0,
    @SerialName("total_clicks") val totalClicks: Int? = 0,
    @SerialName("total_convs") val totalConvs: Int? = 0,
    @SerialName("require_approval") val requireApproval: Int? = 0
)

@Serializable
data class AdminSmartlinkRequest(
    val id: Int,
    @SerialName("smartlink_id") val smartlinkId: Int,
    @SerialName("affiliate_id") val affiliateId: Int,
    val status: String,
    @SerialName("smartlink_name") val smartlinkName: String? = null,
    val slug: String? = null,
    @SerialName("aff_name") val affName: String? = null,
    @SerialName("affiliate_code") val affiliateCode: String? = null,
    @SerialName("aff_email") val affEmail: String? = null,
    @SerialName("created_at") val createdAt: String
)

@Serializable
data class AdminSmartlinkOffer(
    val id: Int? = null,
    @SerialName("smartlink_id") val smartlinkId: Int? = null,
    @SerialName("offer_id") val offerId: Int?,
    @SerialName("offer_name") val offerName: String? = null,
    val weight: Int = 10,
    @SerialName("payout_type") val payoutType: String = "default",
    @SerialName("payout_value") val payoutValue: Double? = null,
    @SerialName("direct_url") val directUrl: String? = null,
    @SerialName("geo_rules") val geoRules: List<String> = emptyList(),
    @SerialName("device_rules") val deviceRules: List<String> = emptyList()
)

@Serializable
data class AdminAvailableOffer(
    val id: Int,
    val name: String,
    val payout: String? = null
)

@Serializable
data class AdminSmartlinkDetail(
    val id: Int,
    val name: String,
    val slug: String,
    @SerialName("rotation_type") val rotationType: String,
    val description: String? = null,
    val status: String,
    @SerialName("require_approval") val requireApproval: Int
)

@Serializable
data class AdminSmartlinkDashboardResponse(
    val smartlinks: List<AdminSmartlink> = emptyList(),
    @SerialName("pending_requests_count") val pendingRequestsCount: Int = 0
)

@Serializable
data class AdminSmartlinkDashboardWrapperResponse(
    val status: String,
    val data: AdminSmartlinkDashboardResponse?
)

@Serializable
data class AdminSmartlinkRequestsResponse(
    val requests: List<AdminSmartlinkRequest> = emptyList()
)

@Serializable
data class AdminSmartlinkRequestsWrapperResponse(
    val status: String,
    val data: AdminSmartlinkRequestsResponse?
)

@Serializable
data class AdminSmartlinkDetailResponse(
    val smartlink: AdminSmartlinkDetail,
    val offers: List<AdminSmartlinkOffer> = emptyList(),
    @SerialName("available_offers") val availableOffers: List<AdminAvailableOffer> = emptyList()
)

@Serializable
data class AdminSmartlinkDetailWrapperResponse(
    val status: String,
    val data: AdminSmartlinkDetailResponse?
)

@Serializable
data class AdminSmartlinkActionRequest(
    val action: String,
    val id: Int? = null,
    @SerialName("request_id") val requestId: Int? = null,
    val decision: String? = null,
    @SerialName("admin_note") val adminNote: String? = null
)

@Serializable
data class AdminSmartlinkSaveRequest(
    val action: String = "save",
    val id: Int? = null,
    val name: String,
    val slug: String,
    @SerialName("rotation_type") val rotationType: String,
    val description: String? = null,
    val status: String,
    @SerialName("require_approval") val requireApproval: Int,
    val offers: List<AdminSmartlinkOffer> = emptyList()
)
