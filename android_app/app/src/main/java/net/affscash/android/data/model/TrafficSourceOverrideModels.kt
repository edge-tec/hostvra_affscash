package net.affscash.android.data.model

import kotlinx.serialization.KSerializer
import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import kotlinx.serialization.builtins.ListSerializer
import kotlinx.serialization.builtins.serializer
import kotlinx.serialization.descriptors.PrimitiveKind
import kotlinx.serialization.descriptors.PrimitiveSerialDescriptor
import kotlinx.serialization.descriptors.SerialDescriptor
import kotlinx.serialization.encoding.Decoder
import kotlinx.serialization.encoding.Encoder
import kotlinx.serialization.json.JsonDecoder
import kotlinx.serialization.json.JsonPrimitive
import kotlinx.serialization.json.intOrNull

/**
 * Handles both `"id": 5` (int) and `"id": "5"` (string) from PHP/MySQL JSON responses.
 */
object FlexibleIntSerializer : KSerializer<Int> {
    override val descriptor: SerialDescriptor = PrimitiveSerialDescriptor("FlexibleInt", PrimitiveKind.INT)
    override fun serialize(encoder: Encoder, value: Int) = encoder.encodeInt(value)
    override fun deserialize(decoder: Decoder): Int {
        return when (val jsonDecoder = decoder as? JsonDecoder) {
            null -> decoder.decodeInt()
            else -> {
                val element = jsonDecoder.decodeJsonElement()
                if (element is JsonPrimitive) {
                    element.intOrNull ?: element.content.toIntOrNull() ?: 0
                } else 0
            }
        }
    }
}

@Serializable
data class TrafficSourceOverrideRule(
    @Serializable(with = FlexibleIntSerializer::class)
    val id: Int = 0,
    val name: String = "",
    val enabled: Boolean = true,
    @Serializable(with = FlexibleIntSerializer::class)
    val priority: Int = 0,
    @SerialName("override_source") val overrideSource: String = "",
    @SerialName("target_original_sources") val targetOriginalSources: List<String> = emptyList(),
    val conditions: TrafficSourceOverrideConditions? = null,
    @SerialName("created_at") val createdAt: String? = null
)

object FlexibleIntListSerializer : KSerializer<List<Int>> {
    override val descriptor: SerialDescriptor = ListSerializer(Int.serializer()).descriptor
    override fun serialize(encoder: Encoder, value: List<Int>) {
        encoder.encodeSerializableValue(ListSerializer(Int.serializer()), value)
    }
    override fun deserialize(decoder: Decoder): List<Int> {
        return when (val jsonDecoder = decoder as? JsonDecoder) {
            null -> decoder.decodeSerializableValue(ListSerializer(Int.serializer()))
            else -> {
                val arr = jsonDecoder.decodeJsonElement()
                if (arr is kotlinx.serialization.json.JsonArray) {
                    arr.mapNotNull { elem ->
                        if (elem is JsonPrimitive) elem.intOrNull ?: elem.content.toIntOrNull() else null
                    }
                } else emptyList()
            }
        }
    }
}

@Serializable
data class TrafficSourceOverrideConditions(
    @Serializable(with = FlexibleIntListSerializer::class)
    @SerialName("affiliate_ids") val affiliateIds: List<Int> = emptyList(),
    @Serializable(with = FlexibleIntListSerializer::class)
    @SerialName("offer_ids") val offerIds: List<Int> = emptyList(),
    @Serializable(with = FlexibleIntListSerializer::class)
    @SerialName("advertiser_ids") val advertiserIds: List<Int> = emptyList(),
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
    @Serializable(with = FlexibleIntSerializer::class)
    val id: Int,
    val name: String? = "",
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
