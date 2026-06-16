package com.example.affscash.data.repository

import com.example.affscash.data.model.ApplySmartlinkRequest
import com.example.affscash.data.model.ApplySmartlinkResponse
import com.example.affscash.data.model.SmartlinkResponse
import com.example.affscash.data.network.ApiService
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
}
