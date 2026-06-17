package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class AdminAdvertiserResponse(
    val success: Boolean,
    val data: AdminAdvertiserData? = null,
    val error: String? = null
)

@Serializable
data class AdminAdvertiserData(
    val advertisers: List<AdminAdvertiserListModel> = emptyList()
)

@Serializable
data class AdminAdvertiserListModel(
    @SerialName("user_id") val userId: Int,
    @SerialName("adv_id") val advId: Int,
    val email: String,
    @SerialName("first_name") val firstName: String,
    @SerialName("last_name") val lastName: String,
    val company: String? = null,
    val phone: String? = null,
    val country: String? = null,
    val status: String,
    @SerialName("created_at") val createdAt: String,
    @SerialName("advertiser_code") val advertiserCode: String,
    val balance: Double = 0.0,
    @SerialName("budget_exempt") val budgetExempt: Int = 0
)

@Serializable
data class AdminAdvertiserDetailsResponse(
    val success: Boolean,
    val data: AdminAdvertiserDetailsData? = null,
    val error: String? = null
)

@Serializable
data class AdminAdvertiserDetailsData(
    val advertiser: AdminAdvertiserListModel
)

@Serializable
data class CreateAdvertiserRequest(
    @SerialName("first_name") val firstName: String,
    @SerialName("last_name") val lastName: String,
    val email: String,
    val password: String,
    val company: String? = null,
    val phone: String? = null,
    val country: String? = null,
    val status: String = "active",
    @SerialName("budget_exempt") val budgetExempt: Int = 0,
    val action: String = "create"
)

@Serializable
data class EditAdvertiserRequest(
    val id: Int,
    @SerialName("first_name") val firstName: String,
    @SerialName("last_name") val lastName: String,
    val company: String? = null,
    val phone: String? = null,
    val country: String? = null,
    @SerialName("budget_exempt") val budgetExempt: Int = 0,
    val action: String = "edit"
)

@Serializable
data class AdminAdvertiserActionRequest(
    val id: Int,
    val action: String,
    val status: String? = null
)
