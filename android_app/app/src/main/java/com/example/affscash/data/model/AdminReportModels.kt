package com.example.affscash.data.model

data class AdminReportFilterResponse(
    val status: String,
    val offers: List<AdminReportFilterOption> = emptyList(),
    val affiliates: List<AdminReportFilterOption> = emptyList(),
    val countries: List<String> = emptyList()
)

data class AdminReportFilterOption(
    val id: String?,
    val name: String? = null,
    val affiliate_code: String? = null
)

data class AdminReportResponse(
    val status: String,
    val message: String? = null,
    val totals: AdminReportTotals? = null,
    // Depending on the tab, the API returns a generic 'rows' list
    // We use a Map to handle the flexible structure dynamically or generic row classes
    val rows: List<Map<String, Any>> = emptyList()
)

data class AdminReportTotals(
    val clicks: Double = 0.0,
    val uclicks: Double = 0.0,
    val conversions: Double = 0.0,
    val approved: Double = 0.0,
    val rejected: Double = 0.0,
    val fraud_clicks: Double = 0.0,
    val payout: Double = 0.0,
    val revenue: Double = 0.0,
    val profit: Double = 0.0,
    val impressions: Double = 0.0,
    val fraud_conv: Double = 0.0
)
