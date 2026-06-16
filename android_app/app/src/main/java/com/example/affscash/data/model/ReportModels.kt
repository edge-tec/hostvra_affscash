package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class ReportFiltersResponse(
    val success: Boolean,
    val offers: List<OfferItem> = emptyList(),
    val countries: List<String> = emptyList(),
    val error: String? = null
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
