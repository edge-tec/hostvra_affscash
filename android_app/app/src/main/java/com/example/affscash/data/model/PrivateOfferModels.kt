package com.example.affscash.data.model

import kotlinx.serialization.Serializable

@Serializable
data class PrivateOffer(
    val id: Int,
    val name: String,
    val payout: String?,
    val payout_type: String?,
    val status: String,
    val access_count: Int? = 0
)

@Serializable
data class ConvertableOffer(
    val id: Int,
    val name: String,
    val payout: String?,
    val payout_type: String?,
    val status: String
)

@Serializable
data class PrivateOfferLog(
    val id: Int,
    val action: String,
    val offer_name: String?,
    val actor_name: String?,
    val aff_name: String?,
    val affiliate_code: String?,
    val source: String,
    val details: String?,
    val ip_address: String?,
    val created_at: String
)

@Serializable
data class PrivateOfferDashboardResponse(
    val privateOffers: List<PrivateOffer> = emptyList(),
    val convertableOffers: List<ConvertableOffer> = emptyList(),
    val recentLog: List<PrivateOfferLog> = emptyList()
)

@Serializable
data class PrivateOfferGrant(
    val grant_id: Int,
    val granted_at: String,
    val notes: String?,
    val affiliate_id: Int,
    val affiliate_code: String?,
    val email: String?,
    val first_name: String?,
    val last_name: String?,
    val user_status: String?
)

@Serializable
data class PrivateOfferDetail(
    val id: Int,
    val name: String,
    val payout: String?,
    val payout_type: String?,
    val status: String,
    val visibility: String?
)

@Serializable
data class PrivateOfferDetailResponse(
    val offer: PrivateOfferDetail,
    val grants: List<PrivateOfferGrant> = emptyList(),
    val log: List<PrivateOfferLog> = emptyList()
)

@Serializable
data class PrivateOfferActionRequest(
    val action: String,
    val offer_id: Int,
    val private: Int? = null,
    val affiliate_identifier: String? = null,
    val notes: String? = null,
    val affiliate_id: Int? = null
)

@Serializable
data class PrivateOfferDashboardWrapperResponse(
    val status: String,
    val data: PrivateOfferDashboardResponse?
)

@Serializable
data class PrivateOfferDetailWrapperResponse(
    val status: String,
    val data: PrivateOfferDetailResponse?
)

@Serializable
data class GenericResponse(
    val status: String,
    val message: String?
)
