package net.affscash.android.data.repository

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put
import net.affscash.android.data.model.GenericResponse
import net.affscash.android.data.model.LiveUsersResponse
import net.affscash.android.data.model.LoginLogsResponse
import net.affscash.android.data.network.ApiService
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class LoginActivityRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getLiveUsers(): Result<LiveUsersResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminLiveUsers()
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getLoginLogs(from: String?, to: String?, search: String?): Result<LoginLogsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminLoginLogs(from = from, to = to, startDate = from, endDate = to, search = search)
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun forceLogoutUser(sessionId: String, userId: Int): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val body = buildJsonObject {
                put("session_id", sessionId)
                put("user_id", userId)
            }
            val response = apiService.forceLogoutUser(body)
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
