package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class DashboardAnalyticsStatsResponse(
    val clicks: Int = 0,
    val unique: Int = 0,
    val conversions: Int = 0,
    val revenue: Double = 0.0,
    val cr: Double = 0.0,
    val balance: Double = 0.0,
    val impressions: Int = 0,
    @SerialName("fraud_conv") val fraudConv: Int = 0,
    @SerialName("fraud_conv_pct") val fraudConvPct: Double = 0.0,
    val trend: DashboardAnalyticsTrend = DashboardAnalyticsTrend()
)

@Serializable
data class DashboardAnalyticsTrend(
    val clicks: Double = 0.0,
    val conv: Double = 0.0,
    val revenue: Double = 0.0,
    @SerialName("fraud_conv_pct") val fraudConvPct: Double = 0.0
)

@Serializable
data class DashboardTrendChartResponse(
    val labels: List<String> = emptyList(),
    @SerialName("clicks_data") val clicksData: List<Int> = emptyList(),
    @SerialName("unique_data") val uniqueData: List<Int> = emptyList(),
    @SerialName("conv_data") val convData: List<Int> = emptyList(),
    @SerialName("revenue_data") val revenueData: List<Double> = emptyList(),
    @SerialName("fraud_data") val fraudData: List<Int> = emptyList()
)

@Serializable
data class DashboardPieChartResponse(
    val labels: List<String> = emptyList(),
    val data: List<Int> = emptyList()
)

@Serializable
data class DashboardHourlyResponse(
    val labels: List<String> = emptyList(),
    val data: List<Int> = emptyList()
)

@Serializable
data class DashboardCountriesResponse(
    val rows: List<DashboardCountryRow> = emptyList()
)

@Serializable
data class DashboardCountryRow(
    val country: String = "",
    val clicks: Int = 0,
    val unique: Int = 0,
    val conv: Int = 0
)

@Serializable
data class DashboardOffersResponse(
    val rows: List<DashboardOfferRow> = emptyList()
)

@Serializable
data class DashboardOfferRow(
    val id: Int = 0,
    val name: String = "",
    val clicks: Int = 0,
    val conv: Int = 0,
    val payout: Double = 0.0,
    val cr: Double = 0.0,
    @SerialName("fraud_conv") val fraudConv: Int = 0,
    @SerialName("fraud_conv_pct") val fraudConvPct: Double = 0.0
)
