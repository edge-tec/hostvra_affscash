package net.affscash.android.data.model

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class AdminInvoiceFormDataResponse(
    val status: String,
    val data: AdminInvoiceFormData? = null,
    val message: String? = null
)

@Serializable
data class AdminInvoiceFormData(
    val affiliates: List<InvoiceEntityOption>,
    val advertisers: List<InvoiceEntityOption>,
    val managers: List<InvoiceEntityOption>
)

@Serializable
data class InvoiceEntityOption(
    val id: Int,
    val label: String
)

@Serializable
data class AdminInvoiceAffiliateInfoResponse(
    val status: String,
    val data: AdminInvoiceAffiliateInfo? = null,
    val message: String? = null
)

@Serializable
data class AdminInvoiceAffiliateInfo(
    val balance: Double,
    val threshold: Double,
    @SerialName("payment_method") val paymentMethod: String,
    @SerialName("payment_details") val paymentDetails: String
)

@Serializable
data class AdminInvoiceManagerInfoResponse(
    val status: String,
    val data: AdminInvoiceManagerInfo? = null,
    val message: String? = null
)

@Serializable
data class AdminInvoiceManagerInfo(
    val balance: Double,
    val name: String,
    val email: String
)

@Serializable
data class AdminInvoiceLoadOffersResponse(
    val status: String,
    val data: AdminInvoiceLoadOffersData? = null,
    val message: String? = null
)

@Serializable
data class AdminInvoiceLoadOffersData(
    val offers: List<AdminInvoiceOfferItem>
)

@Serializable
data class AdminInvoiceOfferItem(
    @SerialName("offer_id") val offerId: Int,
    @SerialName("offer_name") val offerName: String,
    val conversions: Int,
    @SerialName("total_payout") val totalPayout: Double,
    @SerialName("avg_rate") val avgRate: Double
)

@Serializable
data class AdminCreateInvoiceRequest(
    val type: String,
    @SerialName("entity_id") val entityId: Int,
    @SerialName("period_start") val periodStart: String?,
    @SerialName("period_end") val periodEnd: String?,
    @SerialName("due_date") val dueDate: String?,
    val notes: String?,
    @SerialName("tax_rate") val taxRate: Double,
    @SerialName("total_override") val totalOverride: String?,
    @SerialName("payment_details_override") val paymentDetailsOverride: String?,
    val items: List<AdminCreateInvoiceLineItem>
)

@Serializable
data class AdminCreateInvoiceLineItem(
    val description: String,
    val qty: Double,
    val rate: Double
)

@Serializable
data class AdminCreateInvoiceResponse(
    val status: String,
    val message: String?,
    val data: AdminCreateInvoiceResponseData? = null
)

@Serializable
data class AdminCreateInvoiceResponseData(
    @SerialName("invoice_id") val invoiceId: Int
)
