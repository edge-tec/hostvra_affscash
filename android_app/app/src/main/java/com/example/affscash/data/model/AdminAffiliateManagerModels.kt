package com.example.affscash.data.model

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

data class AdminAffiliateManagerResponse(
    val status: String,
    val message: String?,
    val data: AdminAffiliateManagerData?
)

data class AdminAffiliateManagerData(
    val managers: List<AdminAffiliateManagerRow>
)

data class AdminAffiliateManagerDeleteRequest(
    val mgr_id: Int
)

data class AdminAffiliateManagerDeleteResponse(
    val status: String,
    val message: String?
)
