package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class VpnSkipEntry(
    val id: Int,
    @SerialName("affiliate_id") val affiliateId: Int,
    @SerialName("affiliate_code") val affiliateCode: String = "",
    @SerialName("affiliate_name") val affiliateName: String = "",
    val email: String = "",
    val note: String? = null,
    @SerialName("added_by_name") val addedByName: String = "Admin",
    @SerialName("created_at") val createdAt: String = ""
)

@Serializable
data class AvailableSkipAffiliate(
    val id: Int,
    @SerialName("affiliate_code") val affiliateCode: String = "",
    val name: String = "",
    val email: String = ""
)

@Serializable
data class VpnSkipListData(
    val entries: List<VpnSkipEntry> = emptyList(),
    @SerialName("available_affiliates") val availableAffiliates: List<AvailableSkipAffiliate> = emptyList(),
    val total: Int = 0
)

@Serializable
data class VpnSkipListResponse(
    val status: String = "success",
    val message: String? = null,
    val data: VpnSkipListData? = null
)
