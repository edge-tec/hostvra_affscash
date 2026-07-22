package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class AdminInvoiceResponse(
    val status: String,
    val data: List<AdminInvoiceRow>? = null,
    val message: String? = null
)

@Serializable
data class AdminInvoiceDetailResponse(
    val status: String,
    val data: AdminInvoiceDetail? = null,
    val message: String? = null
)

@Serializable
data class AdminInvoiceStatusResponse(
    val status: String,
    val message: String? = null
)

@Serializable
data class AdminInvoiceRow(
    val id: Int,
    val invoice_number: String,
    val type: String,
    val subtotal: String?,
    val tax_rate: String?,
    val tax_amount: String?,
    val total: String,
    val status: String,
    val due_date: String?,
    val created_at: String,
    val period_start: String?,
    val period_end: String?,
    val paid_at: String?,
    val recipient_name: String?,
    val recipient_email: String?
)

@Serializable
data class AdminInvoiceDetail(
    val id: Int,
    val invoice_number: String,
    val type: String,
    val subtotal: String?,
    val tax_rate: String?,
    val tax_amount: String?,
    val total: String,
    val status: String,
    val due_date: String?,
    val created_at: String,
    val period_start: String?,
    val period_end: String?,
    val paid_at: String?,
    val recipient_name: String?,
    val recipient_email: String?,
    val notes: String?,
    val items: List<AdminInvoiceItem>
)

@Serializable
data class AdminInvoiceItem(
    val description: String,
    val qty: String,
    val rate: String,
    val amount: String
)

@Serializable
data class AdminInvoiceStatusRequest(
    val invoice_id: Int,
    val status: String
)

@Serializable
data class AdminInvoiceDeleteRequest(
    val invoice_id: Int
)

@Serializable
data class AdminInvoiceRequestRow(
    val id: Int,
    @SerialName("manager_id") val managerId: Int = 0,
    @SerialName("affiliate_id") val affiliateId: Int? = null,
    val amount: String = "0.00",
    @SerialName("period_start") val periodStart: String = "",
    @SerialName("period_end") val periodEnd: String = "",
    val notes: String? = null,
    val status: String = "pending",
    @SerialName("admin_note") val adminNote: String? = null,
    @SerialName("reviewed_at") val reviewedAt: String? = null,
    @SerialName("invoice_id") val invoiceId: Int? = null,
    @SerialName("created_at") val createdAt: String = "",
    @SerialName("manager_name") val managerName: String? = null,
    @SerialName("manager_email") val managerEmail: String? = null,
    @SerialName("affiliate_name") val affiliateName: String? = null,
    @SerialName("affiliate_code") val affiliateCode: String? = null
)

@Serializable
data class AdminInvoiceRequestsResponse(
    val status: String = "success",
    val data: List<AdminInvoiceRequestRow>? = null,
    val message: String? = null
)

@Serializable
data class AdminApproveInvoiceRequestPayload(
    @SerialName("request_id") val requestId: Int,
    val amount: Double? = null,
    @SerialName("period_start") val periodStart: String? = null,
    @SerialName("period_end") val periodEnd: String? = null,
    @SerialName("due_date") val dueDate: String? = null,
    @SerialName("admin_note") val adminNote: String? = null
)

@Serializable
data class AdminRejectInvoiceRequestPayload(
    @SerialName("request_id") val requestId: Int,
    @SerialName("admin_note") val adminNote: String? = null
)
