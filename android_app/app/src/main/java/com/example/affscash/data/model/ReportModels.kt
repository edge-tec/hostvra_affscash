package com.example.affscash.data.model

import kotlinx.serialization.Serializable

@Serializable
data class ReportResponse(
    val success: Boolean,
    val tab: String? = null,
    val from: String? = null,
    val to: String? = null,
    val rows: List<ReportRow> = emptyList(),
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
