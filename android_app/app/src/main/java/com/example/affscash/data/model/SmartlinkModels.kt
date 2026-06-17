package com.example.affscash.data.model

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
    val status: String,
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
    val status: String, // pending, approved, rejected
    @SerialName("promotion_description") val promotionDescription: String? = null,
    @SerialName("admin_note") val adminNote: String? = null,
    @SerialName("created_at") val createdAt: String,
    
    // Joined fields
    @SerialName("smartlink_name") val smartlinkName: String,
    @SerialName("aff_name") val affName: String,
    @SerialName("affiliate_code") val affiliateCode: String,
    @SerialName("aff_email") val affEmail: String
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
