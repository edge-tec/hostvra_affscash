package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class TrafficSourceOverrideRule(
    val id: Int = 0,
    val name: String = "",
    val enabled: Boolean = true,
    val priority: Int = 0,
    @SerialName("override_source") val overrideSource: String = "",
    @SerialName("target_original_sources") val targetOriginalSources: List<String> = emptyList(),
    val conditions: TrafficSourceOverrideConditions? = null,
    @SerialName("created_at") val createdAt: String? = null
)

@Serializable
data class TrafficSourceOverrideConditions(
    @SerialName("affiliate_ids") val affiliateIds: List<Int>? = null,
    @SerialName("offer_ids") val offerIds: List<Int>? = null,
    @SerialName("advertiser_ids") val advertiserIds: List<Int>? = null,
    val countries: List<String>? = null,
    @SerialName("device_types") val deviceTypes: List<String>? = null
)

@Serializable
data class TrafficSourceOverrideDestination(
    val key: String,
    val label: String
)

@Serializable
data class SimpleOptionItem(
    val id: Int,
    val name: String,
    @SerialName("affiliate_code") val affiliateCode: String? = null
)

@Serializable
data class CountryOptionItem(
    val code: String,
    val name: String
)

@Serializable
data class TrafficSourceOverrideData(
    @SerialName("global_enabled") val globalEnabled: Boolean = false,
    val rules: List<TrafficSourceOverrideRule> = emptyList(),
    @SerialName("chat_sources") val chatSources: List<String> = emptyList(),
    val destinations: List<TrafficSourceOverrideDestination> = emptyList(),
    val affiliates: List<SimpleOptionItem> = emptyList(),
    val offers: List<SimpleOptionItem> = emptyList(),
    val advertisers: List<SimpleOptionItem> = emptyList(),
    val countries: List<CountryOptionItem> = emptyList(),
    @SerialName("device_types") val deviceTypes: List<String> = emptyList()
)

@Serializable
data class TrafficSourceOverrideResponse(
    val status: String = "success",
    val message: String? = null,
    val data: TrafficSourceOverrideData? = null
)

@Serializable
data class TrafficSourceOverrideLog(
    val id: Int,
    @SerialName("click_id") val clickId: String = "",
    @SerialName("ip_address") val ipAddress: String = "",
    @SerialName("original_source") val originalSource: String = "",
    @SerialName("override_source") val overrideSource: String = "",
    @SerialName("offer_name") val offerName: String = "",
    @SerialName("affiliate_name") val affiliateName: String = "",
    @SerialName("affiliate_code") val affiliateCode: String = "",
    @SerialName("clicked_at") val clickedAt: String = ""
)

@Serializable
data class TrafficSourceOverrideLogsData(
    @SerialName("total_logs") val totalLogs: Int = 0,
    val logs: List<TrafficSourceOverrideLog> = emptyList()
)

@Serializable
data class TrafficSourceOverrideLogsResponse(
    val status: String = "success",
    val message: String? = null,
    val data: TrafficSourceOverrideLogsData? = null
)
