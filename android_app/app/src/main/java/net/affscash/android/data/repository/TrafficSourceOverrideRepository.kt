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

    suspend fun getAdminTrafficSourceOverride(): Result<TrafficSourceOverrideResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminTrafficSourceOverride()
            android.util.Log.d("TSOverride", "Admin API HTTP code: ${response.code()}, isSuccessful: ${response.isSuccessful}")
            if (response.isSuccessful) {
                response.body()?.let { body ->
                    android.util.Log.d("TSOverride", "Admin API body: affiliates=${body.data?.affiliates?.size}, offers=${body.data?.offers?.size}")
                    return@withContext Result.success(body)
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
                response.body()?.let { body ->
                    android.util.Log.d("TSOverride", "Manager API body: affiliates=${body.data?.affiliates?.size}, offers=${body.data?.offers?.size}")
                    return@withContext Result.success(body)
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
