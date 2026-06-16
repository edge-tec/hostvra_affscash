package com.example.affscash.data.repository

import com.example.affscash.data.model.AdminAffiliateResponse
import com.example.affscash.data.model.ManagerAffiliateResponse
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AffiliateRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getAdminAffiliates(): Result<AdminAffiliateResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminAffiliates()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch admin affiliates"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerAffiliates(): Result<ManagerAffiliateResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerAffiliates()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch manager affiliates"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
