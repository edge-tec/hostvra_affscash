package net.affscash.android.data.model

import com.google.gson.annotations.SerializedName

data class AdminInvoiceFormDataResponse(
    val status: String,
    val data: AdminInvoiceFormData? = null,
    val message: String? = null
)

data class AdminInvoiceFormData(
    val affiliates: List<InvoiceEntityOption>,
    val advertisers: List<InvoiceEntityOption>,
    val managers: List<InvoiceEntityOption>
)

data class InvoiceEntityOption(
    val id: Int,
    val label: String
)

data class AdminInvoiceAffiliateInfoResponse(
    val status: String,
    val data: AdminInvoiceAffiliateInfo? = null,
    val message: String? = null
)

data class AdminInvoiceAffiliateInfo(
    val balance: Double,
    val threshold: Double,
    @SerializedName("payment_method") val paymentMethod: String,
    @SerializedName("payment_details") val paymentDetails: String
)

data class AdminInvoiceManagerInfoResponse(
    val status: String,
    val data: AdminInvoiceManagerInfo? = null,
    val message: String? = null
)

data class AdminInvoiceManagerInfo(
    val balance: Double,
    val name: String,
    val email: String
)

data class AdminInvoiceLoadOffersResponse(
    val status: String,
    val data: AdminInvoiceLoadOffersData? = null,
    val message: String? = null
)

data class AdminInvoiceLoadOffersData(
    val offers: List<AdminInvoiceOfferItem>
)

data class AdminInvoiceOfferItem(
    @SerializedName("offer_id") val offerId: Int,
    @SerializedName("offer_name") val offerName: String,
    val conversions: Int,
    @SerializedName("total_payout") val totalPayout: Double,
    @SerializedName("avg_rate") val avgRate: Double
)

data class AdminCreateInvoiceRequest(
    val type: String,
    @SerializedName("entity_id") val entityId: Int,
    @SerializedName("period_start") val periodStart: String?,
    @SerializedName("period_end") val periodEnd: String?,
    @SerializedName("due_date") val dueDate: String?,
    val notes: String?,
    @SerializedName("tax_rate") val taxRate: Double,
    @SerializedName("total_override") val totalOverride: String?,
    @SerializedName("payment_details_override") val paymentDetailsOverride: String?,
    val items: List<AdminCreateInvoiceLineItem>
)

data class AdminCreateInvoiceLineItem(
    val description: String,
    val qty: Double,
    val rate: Double
)

data class AdminCreateInvoiceResponse(
    val status: String,
    val message: String?,
    val data: AdminCreateInvoiceResponseData? = null
)

data class AdminCreateInvoiceResponseData(
    @SerializedName("invoice_id") val invoiceId: Int
)
