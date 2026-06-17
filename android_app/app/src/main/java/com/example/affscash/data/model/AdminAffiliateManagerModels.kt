package com.example.affscash.data.model

import kotlinx.serialization.Serializable

@Serializable
data class AdminAffiliateManagerRow(
    val user_id: Int,
    val mgr_id: Int,
    val name: String,
    val email: String,
    val status: String,
    val created_at: String,
    val permissions: List<String>,
    val balance: String,
    val commission_rate: String,
    val aff_count: Int
)

@Serializable
data class AdminAffiliateManagerResponse(
    val status: String,
    val message: String?,
    val data: AdminAffiliateManagerData?
)

@Serializable
data class AdminAffiliateManagerData(
    val managers: List<AdminAffiliateManagerRow>
)

@Serializable
data class AdminAffiliateManagerDeleteRequest(
    val mgr_id: Int
)

@Serializable
data class AdminAffiliateManagerDeleteResponse(
    val status: String,
    val message: String?
)

@Serializable
data class AdminAffiliateManagerImpersonateRequest(
    val action: String = "impersonate",
    val user_id: Int
)
