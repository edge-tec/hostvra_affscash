package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class InvoiceResponse(
    val success: Boolean,
    val data: List<Invoice> = emptyList(),
    val error: String? = null
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
