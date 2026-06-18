package net.affscash.android.data.model

import kotlinx.serialization.Serializable

@Serializable
data class BasicResponse(
    val success: Boolean,
    val message: String? = null,
    val error: String? = null
)
