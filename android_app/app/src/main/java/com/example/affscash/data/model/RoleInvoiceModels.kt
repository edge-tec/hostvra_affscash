package com.example.affscash.data.model

import com.google.gson.annotations.SerializedName

data class Invoice(
    @SerializedName("invoice_id") val invoiceId: Int,
    @SerializedName("invoice_number") val invoiceNumber: String,
    @SerializedName("type") val type: String,
    @SerializedName("entity_name") val entityName: String?,
    @SerializedName("total") val total: Double,
    @SerializedName("status") val status: String,
    @SerializedName("due_date") val dueDate: String?,
    @SerializedName("created_at") val createdAt: String
)

data class InvoiceResponse(
    val success: Boolean,
    val data: List<Invoice>,
    val error: String?
)
