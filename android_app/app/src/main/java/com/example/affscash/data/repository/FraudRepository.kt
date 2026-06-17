package com.example.affscash.data.repository

import com.example.affscash.data.model.FraudReportResponse
import com.example.affscash.data.network.ApiService
import javax.inject.Inject

class FraudRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getFraudReport(): Result<FraudReportResponse> {
        return try {
            val response = apiService.getFraudReport()
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.message() ?: "Unknown error"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
