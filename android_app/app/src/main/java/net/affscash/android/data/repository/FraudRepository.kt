package net.affscash.android.data.repository

import net.affscash.android.data.model.FraudReportResponse
import net.affscash.android.data.network.ApiService
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
