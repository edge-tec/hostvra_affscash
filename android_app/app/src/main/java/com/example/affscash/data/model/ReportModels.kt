package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class ReportFiltersResponse(
    val success: Boolean,
    val offers: List<OfferItem> = emptyList(),
    val countries: List<String> = emptyList(),
    val affiliates: List<ManagerAffiliateFilterItem> = emptyList(),
    val error: String? = null
)

@Serializable
data class DuplicateConversionsResponse(
    val success: Boolean,
    @SerialName("total_groups") val totalGroups: Int = 0,
    @SerialName("total_rows") val totalRows: Int = 0,
    val groups: List<DuplicateConversionGroup> = emptyList(),
    val error: String? = null
)

@Serializable
data class DuplicateConversionGroup(
    @SerialName("offer_id") val offerId: Int,
    @SerialName("offer_name") val offerName: String,
    @SerialName("ip_address") val ipAddress: String,
    @SerialName("dup_count") val dupCount: Int,
    val conversions: List<DuplicateConversionRow>
)

@Serializable
data class DuplicateConversionRow(
    val id: Int,
    @SerialName("conversion_id") val conversionId: String,
    @SerialName("affiliate_id") val affiliateId: Int,
    @SerialName("affiliate_name") val affiliateName: String,
    @SerialName("affiliate_code") val affiliateCode: String,
    val payout: Double,
    val status: String,
    @SerialName("transaction_id") val transactionId: String?,
    @SerialName("goal_name") val goalName: String?,
    @SerialName("converted_at") val convertedAt: String
)

@Serializable
data class ManagerAffiliateFilterItem(
    val id: Int,
    val name: String,
    @SerialName("affiliate_code") val affiliateCode: String
)

@Serializable
data class OfferItem(
    val id: Int,
    val name: String
)

@Serializable
data class ReportResponse(
    val success: Boolean,
    val tab: String? = null,
    val from: String? = null,
    val to: String? = null,
    val rows: List<ReportRow>? = null,
    val clicks: List<ClickRow>? = null,
    val conversions: List<ConversionRow>? = null,
    @SerialName("sl_clicks") val slClicks: List<SmartlinkClickRow>? = null,
    val totals: ReportTotals? = null,
    val error: String? = null
)

@Serializable
data class ReportRow(
    val label: String,
    val clicks: Int,
    val uclicks: Int,
    val conv: Int,
    val approved: Int,
    val rejected: Int,
    val payout: Double,
    val fraud: Int
)

@Serializable
data class ReportTotals(
    val clicks: Int,
    val uclicks: Int,
    val conv: Int,
    val approved: Int,
    val rejected: Int,
    val payout: Double,
    val fraud: Int
)

@Serializable
data class ClickRow(
    @SerialName("click_id") val clickId: String,
    val sub1: String? = null,
    val os: String? = null,
    val browser: String? = null,
    @SerialName("device_type") val deviceType: String? = null,
    val country: String? = null,
    @SerialName("clicked_at") val clickedAt: String? = null,
    @SerialName("offer_name") val offerName: String? = null,
    @SerialName("conv_status") val convStatus: String? = null,
    @SerialName("conv_payout") val convPayout: Double? = null
)

@Serializable
data class ConversionRow(
    @SerialName("conversion_id") val conversionId: String,
    @SerialName("click_id") val clickId: String,
    val status: String,
    val payout: Double,
    @SerialName("converted_at") val convertedAt: String,
    @SerialName("offer_name") val offerName: String? = null,
    val sub1: String? = null,
    val country: String? = null,
    val os: String? = null,
    val browser: String? = null
)

@Serializable
data class SmartlinkClickRow(
    @SerialName("click_id") val clickId: String,
    @SerialName("smartlink_name") val smartlinkName: String? = null,
    @SerialName("offer_name") val offerName: String? = null,
    val sub1: String? = null,
    val country: String? = null,
    @SerialName("clicked_at") val clickedAt: String? = null,
    @SerialName("conv_status") val convStatus: String? = null,
    @SerialName("conv_payout") val convPayout: Double? = null
)
