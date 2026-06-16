package com.example.affscash.data.repository

import com.example.affscash.data.model.ReportResponse
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ReportRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getReports(tab: String, fromDate: String, toDate: String): Result<ReportResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getReports(tab, fromDate, toDate)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch reports"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
