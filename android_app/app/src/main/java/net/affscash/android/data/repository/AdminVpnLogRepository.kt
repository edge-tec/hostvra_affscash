package net.affscash.android.data.repository

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import net.affscash.android.data.network.ApiService
import net.affscash.android.data.model.BaseResponse
import net.affscash.android.data.model.VpnLogListResponse
import net.affscash.android.data.model.VpnLogStatsResponse
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AdminVpnLogRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getVpnLogs(
        ip: String? = null,
        affiliate: String? = null,
        type: String? = null,
        dateFrom: String? = null,
        dateTo: String? = null
    ): Result<VpnLogListResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminVpnLogs(ip, affiliate, type, dateFrom, dateTo)
            if (response.isSuccessful) {
                response.body()?.let {
                    Result.success(it)
                } ?: Result.failure(Exception("Empty response body"))
            } else {
                Result.failure(Exception("Error: ${response.code()} ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getVpnLogStats(): Result<VpnLogStatsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminVpnLogStats()
            if (response.isSuccessful) {
                response.body()?.let {
                    Result.success(it)
                } ?: Result.failure(Exception("Empty response body"))
            } else {
                Result.failure(Exception("Error: ${response.code()} ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun clearOldLogs(): Result<BaseResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.clearAdminVpnLogs()
            if (response.isSuccessful) {
                response.body()?.let {
                    Result.success(it)
                } ?: Result.failure(Exception("Empty response body"))
            } else {
                Result.failure(Exception("Error: ${response.code()} ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
