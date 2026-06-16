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
    @SerialName("total_affiliates") val totalAffiliates: Int,
    @SerialName("pending_affiliates") val pendingAffiliates: Int,
    @SerialName("total_advertisers") val totalAdvertisers: Int,
    @SerialName("total_offers") val totalOffers: Int,
    @SerialName("profit_summary") val profitSummary: AdminProfitSummary,
    @SerialName("top_profit_offers") val topProfitOffers: List<AdminTopOffer> = emptyList()
)

@Serializable
data class AdminProfitSummary(
    @SerialName("total_revenue") val totalRevenue: Double,
    @SerialName("total_payout") val totalPayout: Double,
    @SerialName("total_profit") val totalProfit: Double
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
    @SerialName("total_affiliates") val totalAffiliates: Int,
    @SerialName("fraud_score_average") val fraudScoreAverage: Int,
    @SerialName("fraud_score_counts") val fraudScoreCounts: ManagerFraudScoreCounts
)

@Serializable
data class ManagerFraudScoreCounts(
    val high: Int,
    val medium: Int,
    val low: Int
)
