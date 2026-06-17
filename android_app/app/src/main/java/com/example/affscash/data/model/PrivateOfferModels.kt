package com.example.affscash.data.model

data class PrivateOffer(
    val id: Int,
    val name: String,
    val payout: String?,
    val payout_type: String?,
    val status: String,
    val access_count: Int? = 0
)

data class ConvertableOffer(
    val id: Int,
    val name: String,
    val payout: String?,
    val payout_type: String?,
    val status: String
)

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

data class PrivateOfferDashboardResponse(
    val privateOffers: List<PrivateOffer> = emptyList(),
    val convertableOffers: List<ConvertableOffer> = emptyList(),
    val recentLog: List<PrivateOfferLog> = emptyList()
)

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

data class PrivateOfferDetail(
    val id: Int,
    val name: String,
    val payout: String?,
    val payout_type: String?,
    val status: String,
    val visibility: String?
)

data class PrivateOfferDetailResponse(
    val offer: PrivateOfferDetail,
    val grants: List<PrivateOfferGrant> = emptyList(),
    val log: List<PrivateOfferLog> = emptyList()
)

data class PrivateOfferActionRequest(
    val action: String,
    val offer_id: Int,
    val private: Int? = null,
    val affiliate_identifier: String? = null,
    val notes: String? = null,
    val affiliate_id: Int? = null
)

data class PrivateOfferDashboardWrapperResponse(
    val status: String,
    val data: PrivateOfferDashboardResponse?
)

data class PrivateOfferDetailWrapperResponse(
    val status: String,
    val data: PrivateOfferDetailResponse?
)

data class GenericResponse(
    val status: String,
    val message: String?
)
