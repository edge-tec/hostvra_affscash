package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class Invoice(
    @SerialName("invoice_id") val invoiceId: Int,
    @SerialName("invoice_number") val invoiceNumber: String,
    @SerialName("type") val type: String,
    @SerialName("entity_name") val entityName: String?,
    @SerialName("total") val total: Double,
    @SerialName("status") val status: String,
    @SerialName("due_date") val dueDate: String?,
    @SerialName("created_at") val createdAt: String
)

@Serializable
data class InvoiceResponse(
    val success: Boolean,
    val data: List<Invoice> = emptyList(),
    val error: String? = null
)
