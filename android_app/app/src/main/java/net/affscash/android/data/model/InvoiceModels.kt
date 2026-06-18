package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class InvoiceResponse(
    val success: Boolean,
    val data: List<Invoice> = emptyList(),
    val error: String? = null
)

@Serializable
data class ManagerInvoicesResponse(
    val success: Boolean,
    @SerialName("affiliate_invoices") val affiliateInvoices: List<Invoice> = emptyList(),
    @SerialName("my_invoices") val myInvoices: List<Invoice> = emptyList(),
    val totals: ManagerInvoiceTotals? = null,
    val error: String? = null
)

@Serializable
data class ManagerInvoiceTotals(
    val affiliate: ManagerInvoiceTabTotals? = null,
    val my: ManagerInvoiceTabTotals? = null,
    @SerialName("my_balance") val myBalance: Double = 0.0
)

@Serializable
data class ManagerInvoiceTabTotals(
    @SerialName("total_invoices") val totalInvoices: Int,
    val pending: Double,
    val paid: Double
)

@Serializable
data class Invoice(
    @SerialName("invoice_id") val invoiceId: Int,
    @SerialName("invoice_number") val invoiceNumber: String,
    val type: String? = null,
    @SerialName("entity_name") val entityName: String? = null,
    val total: Double,
    val status: String,
    @SerialName("due_date") val dueDate: String? = null,
    @SerialName("created_at") val createdAt: String? = null,
    @SerialName("period_start") val periodStart: String? = null,
    @SerialName("period_end") val periodEnd: String? = null
)

@Serializable
data class PdfDownloadResponse(
    val success: Boolean,
    val url: String? = null,
    val error: String? = null
)
