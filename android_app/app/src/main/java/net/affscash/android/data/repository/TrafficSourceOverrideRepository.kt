package net.affscash.android.data.repository

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import net.affscash.android.data.model.GenericResponse
import net.affscash.android.data.model.TrafficSourceOverrideLogsResponse
import net.affscash.android.data.model.TrafficSourceOverrideResponse
import net.affscash.android.data.network.ApiService
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class TrafficSourceOverrideRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getAdminTrafficSourceOverride(): Result<TrafficSourceOverrideResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminTrafficSourceOverride()
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun postAdminTrafficSourceOverride(body: Map<String, Any?>): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.postAdminTrafficSourceOverride(body)
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
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun postManagerTrafficSourceOverride(body: Map<String, Any?>): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.postManagerTrafficSourceOverride(body)
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
