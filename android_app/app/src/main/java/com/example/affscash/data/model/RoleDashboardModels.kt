package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class AdminDashboardResponse(
    val success: Boolean,
    val data: AdminDashboardData? = null,
    val error: String? = null
)

@Serializable
data class AdminDashboardData(
    @SerialName("total_affiliates") val totalAffiliates: Int = 0,
    @SerialName("pending_affiliates") val pendingAffiliates: Int = 0,
    @SerialName("total_advertisers") val totalAdvertisers: Int = 0,
    @SerialName("total_offers") val totalOffers: Int = 0,
    @SerialName("profit_summary") val profitSummary: AdminProfitSummary? = null,
    @SerialName("top_profit_offers") val topProfitOffers: List<AdminTopOffer> = emptyList()
)

@Serializable
data class AdminProfitSummary(
    @SerialName("total_revenue") val totalRevenue: Double = 0.0,
    @SerialName("total_payout") val totalPayout: Double = 0.0,
    @SerialName("total_profit") val totalProfit: Double = 0.0
)

@Serializable
data class AdminTopOffer(
    val id: Int,
    val name: String,
    val revenue: Double,
    val payout: Double,
    val profit: Double,
    val conversions: Int
)

@Serializable
data class ManagerDashboardResponse(
    val success: Boolean,
    val data: ManagerDashboardData? = null,
    val error: String? = null
)

@Serializable
data class ManagerDashboardData(
    @SerialName("total_affiliates") val totalAffiliates: Int = 0,
    val clicks: Int = 0,
    val unique: Int = 0,
    val conv: Int = 0,
    val cr: Double = 0.0,
    @SerialName("fraud_conv") val fraudConv: Int = 0,
    @SerialName("fraud_conv_pct") val fraudConvPct: Double = 0.0,
    @SerialName("fraud_score_average") val fraudScoreAverage: Int = 0,
    @SerialName("commission_balance") val commissionBalance: Double = 0.0,
    @SerialName("header_counts") val headerCounts: DashboardHeaderCounts? = null,
    val trend: ManagerTrend? = null
)

@Serializable
data class ManagerTrend(
    val clicks: Double = 0.0,
    val conv: Double = 0.0,
    @SerialName("fraud_conv_pct") val fraudConvPct: Double = 0.0
)

@Serializable
data class ManagerTrendResponse(
    val success: Boolean,
    val data: ManagerTrendData? = null,
    val error: String? = null
)

@Serializable
data class ManagerTrendData(
    val labels: List<String> = emptyList(),
    @SerialName("clicks_data") val clicksData: List<Int> = emptyList(),
    @SerialName("conv_data") val convData: List<Int> = emptyList(),
    @SerialName("fraud_data") val fraudData: List<Int> = emptyList()
)

@Serializable
data class ManagerFiltersResponse(
    val success: Boolean,
    val data: ManagerFiltersData? = null,
    val error: String? = null
)

@Serializable
data class ManagerFiltersData(
    val offers: List<ManagerFilterOffer> = emptyList(),
    val affiliates: List<ManagerFilterAffiliate> = emptyList(),
    val countries: List<String> = emptyList(),
    val devices: List<String> = emptyList()
)

@Serializable
data class ManagerFilterOffer(
    val id: Int,
    val name: String
)

@Serializable
data class ManagerFilterAffiliate(
    val id: Int,
    val label: String
)

@Serializable
data class ManagerDashboardExtraResponse(
    val success: Boolean,
    val data: ManagerDashboardExtraData? = null,
    val error: String? = null
)

@Serializable
data class ManagerDashboardExtraData(
    val hourly: ChartDataHourly? = null,
    @SerialName("conv_status") val convStatus: ChartDataPie? = null,
    val countries: List<CountryStats> = emptyList(),
    val devices: ChartDataPie? = null,
    val browsers: ChartDataPie? = null,
    val os: ChartDataPie? = null,
    val offers: List<ManagerTopOffer> = emptyList(),
    val affiliates: List<ManagerTopAffiliate> = emptyList(),
    @SerialName("recent_convs") val recentConvs: List<RecentConversion> = emptyList(),
    @SerialName("fraud_convs") val fraudConvs: List<FraudConversion> = emptyList()
)

@Serializable
data class ChartDataHourly(
    val labels: List<String> = emptyList(),
    val data: List<Int> = emptyList()
)

@Serializable
data class ChartDataPie(
    val labels: List<String> = emptyList(),
    val data: List<Int> = emptyList(),
    val colors: List<String> = emptyList()
)

@Serializable
data class CountryStats(
    val country: String,
    val clicks: Int,
    val unique: Int,
    val conv: Int
)

@Serializable
data class ManagerTopOffer(
    val id: Int,
    val name: String,
    val clicks: Int,
    val uclicks: Int,
    val conv: Int,
    val payout: Double,
    val cr: Double
)

@Serializable
data class ManagerTopAffiliate(
    val id: Int,
    val name: String,
    val code: String,
    val clicks: Int,
    val uclicks: Int,
    val conv: Int,
    val payout: Double,
    val cr: Double
)

@Serializable
data class RecentConversion(
    val id: Int,
    val status: String,
    val payout: Double,
    val revenue: Double,
    @SerialName("converted_at") val convertedAt: String,
    val country: String? = null,
    @SerialName("device_type") val deviceType: String? = null,
    @SerialName("offer_name") val offerName: String? = null,
    @SerialName("aff_name") val affName: String? = null
)

@Serializable
data class FraudConversion(
    @SerialName("conversion_id") val conversionId: Int,
    val payout: Double,
    @SerialName("converted_at") val convertedAt: String,
    val country: String? = null,
    @SerialName("ip_address") val ipAddress: String? = null,
    @SerialName("affiliate_code") val affiliateCode: String? = null,
    @SerialName("aff_name") val affName: String? = null,
    @SerialName("offer_name") val offerName: String? = null
)
