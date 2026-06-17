package com.example.affscash.data.model

data class AdminInvoiceResponse(
    val status: String,
    val data: List<AdminInvoiceRow>? = null,
    val message: String? = null
)

data class AdminInvoiceDetailResponse(
    val status: String,
    val data: AdminInvoiceDetail? = null,
    val message: String? = null
)

data class AdminInvoiceStatusResponse(
    val status: String,
    val message: String? = null
)

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

data class AdminInvoiceItem(
    val description: String,
    val qty: String,
    val rate: String,
    val amount: String
)

data class AdminInvoiceStatusRequest(
    val invoice_id: Int,
    val status: String
)

data class AdminInvoiceDeleteRequest(
    val invoice_id: Int
)
