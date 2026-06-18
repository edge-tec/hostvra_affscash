package net.affscash.android.data.repository

import net.affscash.android.data.model.ApplySmartlinkRequest
import net.affscash.android.data.model.ApplySmartlinkResponse
import net.affscash.android.data.model.SmartlinkResponse
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class SmartlinkRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getSmartlinks(): Result<SmartlinkResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getSmartlinks()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch smartlinks"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun applySmartlink(smartlinkId: Int, promoDesc: String?): Result<ApplySmartlinkResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.applySmartlink(ApplySmartlinkRequest(smartlinkId, promoDesc))
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to apply for smartlink"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerSmartlinks(query: String? = null): Result<net.affscash.android.data.model.ManagerSmartlinkResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerSmartlinks(query = query)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch manager smartlinks"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerSmartlinkRequests(status: String = "all"): Result<net.affscash.android.data.model.ManagerSmartlinkRequestsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerSmartlinkRequests(status = status)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch manager smartlink requests"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun reviewManagerSmartlinkRequest(requestId: Int, decision: String, note: String? = null): Result<net.affscash.android.data.model.ReviewSmartlinkResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.reviewManagerSmartlinkRequest(net.affscash.android.data.model.ReviewSmartlinkRequestAction(requestId, decision, note))
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to review request"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
