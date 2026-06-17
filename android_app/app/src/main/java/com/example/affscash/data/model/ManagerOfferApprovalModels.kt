package com.example.affscash.data.model

import kotlinx.serialization.Serializable

@Serializable
data class ManagerOfferApprovalListResponse(
    val success: Boolean,
    val requests: List<OfferApprovalRequestItem> = emptyList(),
    val all_offers: List<OfferOption> = emptyList(),
    val pending_count: Int = 0,
    val message: String? = null
)

@Serializable
data class OfferApprovalRequestItem(
    val ao_id: Int,
    val affiliate_id: Int,
    val offer_id: Int,
    val status: String,
    val promotion_description: String?,
    val requested_at: String?,
    val approved_at: String?,
    val offer_name: String,
    val payout_amount: Double,
    val payout_type: String,
    val offer_category: String?,
    val affiliate_name: String,
    val affiliate_email: String,
    val affiliate_joined: String?,
    val affiliate_code: String?,
    val country: String?,
    val traffic_sources: String?,
    val total_clicks: Int,
    val total_conversions: Int
)

@Serializable
data class OfferOption(
    val id: Int,
    val name: String
)

@Serializable
data class ReviewOfferApprovalRequest(
    val affiliate_id: Int,
    val offer_id: Int,
    val review_action: String // "approve" or "reject"
)

@Serializable
data class DefaultResponse(
    val success: Boolean,
    val message: String? = null,
    val error: String? = null
)
