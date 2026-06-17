package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class DashboardResponse(
    val success: Boolean,
    val user: DashboardUser? = null,
    val stats: DashboardStats? = null,
    @SerialName("header_counts") val headerCounts: DashboardHeaderCounts? = null,
    @SerialName("recent_offers") val recentOffers: List<DashboardOffer> = emptyList(),
    val error: String? = null
)

@Serializable
data class DashboardHeaderCounts(
    @SerialName("unread_news") val unreadNews: Int = 0,
    @SerialName("unread_notifs") val unreadNotifs: Int = 0,
    @SerialName("unread_alerts") val unreadAlerts: Int = 0,
    @SerialName("unread_chats") val unreadChats: Int = 0
)

@Serializable
data class DashboardUser(
    val name: String,
    val email: String,
    val status: String
)

@Serializable
data class DashboardStats(
    @SerialName("clicks_today") val clicksToday: Int,
    @SerialName("uclicks_today") val uclicksToday: Int,
    @SerialName("conv_today") val convToday: Int,
    @SerialName("approved_today") val approvedToday: Int,
    @SerialName("payout_today") val payoutToday: Double,
    @SerialName("clicks_month") val clicksMonth: Int,
    @SerialName("conv_month") val convMonth: Int,
    @SerialName("payout_month") val payoutMonth: Double,
    val balance: Double
)

@Serializable
data class DashboardOffer(
    val id: Int,
    val name: String,
    @SerialName("payout_type") val payoutType: String,
    val payout: Double,
    @SerialName("preview_url") val previewUrl: String? = null
)
