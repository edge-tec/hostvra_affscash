package net.affscash.android.data.repository

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import net.affscash.android.data.model.GenericResponse
import net.affscash.android.data.model.TrafficSourceOverrideLogsResponse
import net.affscash.android.data.model.TrafficSourceOverrideResponse
import net.affscash.android.data.network.ApiService
import javax.inject.Inject
import javax.inject.Singleton

import kotlinx.serialization.json.JsonArray
import kotlinx.serialization.json.JsonElement
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.JsonPrimitive

@Singleton
class TrafficSourceOverrideRepository @Inject constructor(
    private val apiService: ApiService
) {
    private fun Map<String, Any?>.toJsonObject(): JsonObject {
        val content = mutableMapOf<String, JsonElement>()
        for ((key, value) in this) {
            if (value == null) continue
            when (value) {
                is String -> content[key] = JsonPrimitive(value)
                is Number -> content[key] = JsonPrimitive(value)
                is Boolean -> content[key] = JsonPrimitive(value)
                is List<*> -> {
                    val jsonArr = value.mapNotNull {
                        when (it) {
                            is String -> JsonPrimitive(it)
                            is Number -> JsonPrimitive(it)
                            is Boolean -> JsonPrimitive(it)
                            else -> null
                        }
                    }
                    content[key] = JsonArray(jsonArr)
                }
                else -> content[key] = JsonPrimitive(value.toString())
            }
        }
        return JsonObject(content)
    }

    private fun parseTrafficSourceOverrideResponse(jsonString: String): TrafficSourceOverrideResponse {
        val root = org.json.JSONObject(jsonString)
        val status = root.optString("status", "error")
        val message = root.optString("message", null).takeIf { it.isNotEmpty() }
        
        if (status != "success" || !root.has("data")) {
            return TrafficSourceOverrideResponse(status = status, message = message, data = null)
        }
        
        val dataObj = root.getJSONObject("data")
        
        val jsonParser = kotlinx.serialization.json.Json { ignoreUnknownKeys = true; isLenient = true; coerceInputValues = true; explicitNulls = false }
        val rules = mutableListOf<net.affscash.android.data.model.TrafficSourceOverrideRule>()
        val rulesArr = dataObj.optJSONArray("rules")
        if (rulesArr != null) {
            for (i in 0 until rulesArr.length()) {
                try {
                    rules.add(jsonParser.decodeFromString(rulesArr.getJSONObject(i).toString()))
                } catch (e: Exception) {
                    android.util.Log.e("TSOverride", "Failed to parse rule at $i", e)
                }
            }
        }
        
        val chatSources = mutableListOf<String>()
        val chatArr = dataObj.optJSONArray("chat_sources")
        if (chatArr != null) {
            for (i in 0 until chatArr.length()) { chatSources.add(chatArr.optString(i)) }
        }
        
        val destinations = mutableListOf<net.affscash.android.data.model.TrafficSourceOverrideDestination>()
        val destArr = dataObj.optJSONArray("destinations")
        if (destArr != null) {
            for (i in 0 until destArr.length()) {
                try {
                    destinations.add(jsonParser.decodeFromString(destArr.getJSONObject(i).toString()))
                } catch (e: Exception) {}
            }
        }
        
        val affiliates = mutableListOf<net.affscash.android.data.model.SimpleOptionItem>()
        val affArr = dataObj.optJSONArray("affiliates")
        if (affArr != null) {
            for (i in 0 until affArr.length()) {
                try {
                    val obj = affArr.getJSONObject(i)
                    val idStr = obj.optString("id", "0")
                    val id = idStr.toIntOrNull() ?: 0
                    affiliates.add(net.affscash.android.data.model.SimpleOptionItem(
                        id = id,
                        name = obj.optString("name", ""),
                        affiliateCode = obj.optString("affiliate_code", null).takeIf { it.isNotEmpty() }
                    ))
                } catch (e: Exception) {
                    android.util.Log.e("TSOverride", "Failed to parse affiliate at $i", e)
                }
            }
        }
        
        val offers = mutableListOf<net.affscash.android.data.model.SimpleOptionItem>()
        val offArr = dataObj.optJSONArray("offers")
        if (offArr != null) {
            for (i in 0 until offArr.length()) {
                try {
                    val obj = offArr.getJSONObject(i)
                    val idStr = obj.optString("id", "0")
                    val id = idStr.toIntOrNull() ?: 0
                    offers.add(net.affscash.android.data.model.SimpleOptionItem(
                        id = id,
                        name = obj.optString("name", "")
                    ))
                } catch (e: Exception) {}
            }
        }
        
        val advertisers = mutableListOf<net.affscash.android.data.model.SimpleOptionItem>()
        val advArr = dataObj.optJSONArray("advertisers")
        if (advArr != null) {
            for (i in 0 until advArr.length()) {
                try {
                    val obj = advArr.getJSONObject(i)
                    val idStr = obj.optString("id", "0")
                    val id = idStr.toIntOrNull() ?: 0
                    advertisers.add(net.affscash.android.data.model.SimpleOptionItem(
                        id = id,
                        name = obj.optString("name", "")
                    ))
                } catch (e: Exception) {}
            }
        }
        
        val countries = mutableListOf<net.affscash.android.data.model.CountryOptionItem>()
        val ctryArr = dataObj.optJSONArray("countries")
        if (ctryArr != null) {
            for (i in 0 until ctryArr.length()) {
                try {
                    countries.add(jsonParser.decodeFromString(ctryArr.getJSONObject(i).toString()))
                } catch (e: Exception) {}
            }
        }
        
        val deviceTypes = mutableListOf<String>()
        val devArr = dataObj.optJSONArray("device_types")
        if (devArr != null) {
            for (i in 0 until devArr.length()) { deviceTypes.add(devArr.optString(i)) }
        }
        
        val data = net.affscash.android.data.model.TrafficSourceOverrideData(
            globalEnabled = dataObj.optBoolean("global_enabled", false),
            rules = rules,
            chatSources = chatSources,
            destinations = destinations,
            affiliates = affiliates,
            offers = offers,
            advertisers = advertisers,
            countries = countries,
            deviceTypes = deviceTypes
        )
        
        return TrafficSourceOverrideResponse(status = status, message = message, data = data)
    }

    suspend fun getAdminTrafficSourceOverride(): Result<TrafficSourceOverrideResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminTrafficSourceOverride()
            android.util.Log.d("TSOverride", "Admin API HTTP code: ${response.code()}, isSuccessful: ${response.isSuccessful}")
            if (response.isSuccessful) {
                response.body()?.string()?.let { jsonString ->
                    val parsed = parseTrafficSourceOverrideResponse(jsonString)
                    android.util.Log.d("TSOverride", "Admin API manually parsed: affiliates=${parsed.data?.affiliates?.size}, offers=${parsed.data?.offers?.size}")
                    return@withContext Result.success(parsed)
                }
            }
            android.util.Log.e("TSOverride", "Admin API failed with code: ${response.code()}")
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            android.util.Log.e("TSOverride", "Admin API exception: ${e.message}", e)
            Result.failure(e)
        }
    }

    suspend fun postAdminTrafficSourceOverride(body: Map<String, Any?>): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.postAdminTrafficSourceOverride(body.toJsonObject())
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getAdminTrafficSourceOverrideLogs(from: String?, to: String?): Result<TrafficSourceOverrideLogsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminTrafficSourceOverrideLogs(from = from, to = to, startDate = from, endDate = to)
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerTrafficSourceOverride(): Result<TrafficSourceOverrideResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerTrafficSourceOverride()
            android.util.Log.d("TSOverride", "Manager API HTTP code: ${response.code()}, isSuccessful: ${response.isSuccessful}")
            if (response.isSuccessful) {
                response.body()?.string()?.let { jsonString ->
                    val parsed = parseTrafficSourceOverrideResponse(jsonString)
                    android.util.Log.d("TSOverride", "Manager API manually parsed: affiliates=${parsed.data?.affiliates?.size}, offers=${parsed.data?.offers?.size}")
                    return@withContext Result.success(parsed)
                }
            }
            android.util.Log.e("TSOverride", "Manager API failed with code: ${response.code()}")
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            android.util.Log.e("TSOverride", "Manager API exception: ${e.message}", e)
            Result.failure(e)
        }
    }

    suspend fun postManagerTrafficSourceOverride(body: Map<String, Any?>): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.postManagerTrafficSourceOverride(body.toJsonObject())
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerTrafficSourceOverrideLogs(from: String?, to: String?): Result<TrafficSourceOverrideLogsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerTrafficSourceOverrideLogs(from = from, to = to, startDate = from, endDate = to)
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
