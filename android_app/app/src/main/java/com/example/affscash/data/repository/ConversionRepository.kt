package com.example.affscash.data.repository

import com.example.affscash.data.model.ConversionResponse
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ConversionRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getAdminConversions(): Result<ConversionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminConversions()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch admin conversions"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerConversions(): Result<ConversionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerConversions()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch manager conversions"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
