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
