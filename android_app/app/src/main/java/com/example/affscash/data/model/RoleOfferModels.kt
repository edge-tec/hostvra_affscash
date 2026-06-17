package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class AdminOfferResponse(
    val success: Boolean,
    val data: List<AdminOffer> = emptyList(),
    val meta: AdminOfferMeta? = null,
    val error: String? = null
)

@Serializable
data class AdminOfferMeta(
    val categories: List<String> = emptyList(),
    val offerTypes: List<String> = emptyList(),
    val advertisers: List<AdvertiserOption> = emptyList()
)

@Serializable
data class AdvertiserOption(
    val id: Int,
    val label: String
)

@Serializable
data class AdminOffer(
    val id: Int,
    val name: String,
    val description: String? = null,
    @SerialName("payout_type") val payoutType: String,
    val payout: Double,
    val revenue: Double = 0.0,
    val status: String,
    val category: String? = null,
    @SerialName("adv_name") val advName: String,
    @SerialName("advertiser_id") val advertiserId: Int = 0,
    @SerialName("created_at") val createdAt: String,
    @SerialName("daily_cap") val dailyCap: Int = 0,
    @SerialName("total_cap") val totalCap: Int = 0,
    @SerialName("geo_targeting") val geoTargeting: List<String> = emptyList(),
    @SerialName("device_targeting") val deviceTargeting: List<String> = emptyList(),
    @SerialName("require_approval") val requireApproval: Boolean = false,
    val visibility: String = "public",
    @SerialName("offer_type") val offerType: String? = null,
    @SerialName("offer_url") val offerUrl: String? = null
)

@Serializable
data class AdminOfferActionResponse(
    val success: Boolean,
    val message: String? = null,
    val id: Int? = null,
    val error: String? = null
)

@Serializable
data class AdminOfferCreateRequest(
    val name: String,
    @SerialName("advertiser_id") val advertiserId: Int,
    @SerialName("offer_url") val offerUrl: String,
    @SerialName("payout_type") val payoutType: String,
    val payout: Double,
    val revenue: Double,
    val status: String,
    val category: String?,
    @SerialName("offer_type") val offerType: String?,
    val visibility: String,
    @SerialName("require_approval") val requireApproval: Boolean,
    @SerialName("daily_cap") val dailyCap: Int,
    @SerialName("total_cap") val totalCap: Int,
    @SerialName("geo_targeting") val geoTargeting: List<String>,
    @SerialName("device_targeting") val deviceTargeting: List<String>,
    val description: String?,
    @SerialName("is_inhouse") val isInhouse: Boolean? = null
)

@Serializable
data class AdminOfferEditRequest(
    val id: Int,
    val name: String,
    @SerialName("advertiser_id") val advertiserId: Int,
    @SerialName("offer_url") val offerUrl: String,
    @SerialName("payout_type") val payoutType: String,
    val payout: Double,
    val revenue: Double,
    val status: String,
    val category: String?,
    @SerialName("offer_type") val offerType: String?,
    val visibility: String,
    @SerialName("require_approval") val requireApproval: Boolean,
    @SerialName("daily_cap") val dailyCap: Int,
    @SerialName("total_cap") val totalCap: Int,
    @SerialName("geo_targeting") val geoTargeting: List<String>,
    @SerialName("device_targeting") val deviceTargeting: List<String>,
    val description: String?,
    @SerialName("is_inhouse") val isInhouse: Boolean? = null
)

@Serializable
data class ManagerOfferResponse(
    val success: Boolean,
    val data: ManagerOfferData? = null,
    val error: String? = null
)

@Serializable
data class ManagerOfferData(
    val offers: List<ManagerOffer> = emptyList(),
    @SerialName("tracking_url_base") val trackingUrlBase: String = ""
)

@Serializable
data class ManagerOffer(
    val id: Int,
    val name: String,
    val description: String? = null,
    @SerialName("payout_type") val payoutType: String,
    val payout: Double,
    val status: String,
    val category: String? = null,
    @SerialName("adv_name") val advName: String,
    @SerialName("aff_count") val affCount: Int,
    @SerialName("created_at") val createdAt: String,
    @SerialName("is_inhouse") val isInhouse: Boolean = false,
    @SerialName("offer_type") val offerType: String? = null,
    @SerialName("fraud_score") val fraudScore: Int = 0,
    @SerialName("fraud_level") val fraudLevel: String = "low",
    @SerialName("geo_targeting") val geoTargeting: List<String> = emptyList()
)

@Serializable
data class ManagerOfferFiltersResponse(
    val success: Boolean,
    val data: ManagerOfferFilters? = null,
    val error: String? = null
)

@Serializable
data class ManagerOfferFilters(
    val categories: List<String> = emptyList(),
    @SerialName("offer_types") val offerTypes: List<String> = emptyList(),
    @SerialName("managed_affiliates") val managedAffiliates: List<ManagedAffiliate> = emptyList(),
    @SerialName("payout_types") val payoutTypes: List<String> = emptyList(),
    val statuses: List<String> = emptyList(),
    val devices: List<String> = emptyList()
)

@Serializable
data class ManagedAffiliate(
    val id: Int,
    @SerialName("affiliate_code") val affiliateCode: String,
    val name: String
)
