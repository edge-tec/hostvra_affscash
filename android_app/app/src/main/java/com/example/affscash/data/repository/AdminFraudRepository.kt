package com.example.affscash.data.repository

import com.example.affscash.data.model.AdminFraudActionRequest
import com.example.affscash.data.model.AdminFraudActionResponse
import com.example.affscash.data.model.AdminFraudReportResponse
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class AdminFraudRepository(private val apiService: ApiService) {

    suspend fun getFraudScoreReport(
        from: String? = null,
        to: String? = null,
        status: String? = null,
        affiliateId: Int? = null,
        offerId: Int? = null,
        clickId: String? = null,
        scoreMin: Int? = null,
        scoreMax: Int? = null,
        sort: String? = null,
        dir: String? = null
    ): Result<AdminFraudReportResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminFraudScoreReport(
                action = "report",
                from = from,
                to = to,
                status = status,
                affiliateId = affiliateId,
                offerId = offerId,
                clickId = clickId,
                scoreMin = scoreMin,
                scoreMax = scoreMax,
                sort = sort,
                dir = dir
            )
            if (response.status == "success") {
                Result.success(response)
            } else {
                Result.failure(Exception(response.message ?: "Failed to fetch report"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun submitAction(request: AdminFraudActionRequest): Result<AdminFraudActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.submitAdminFraudAction(request)
            if (response.status == "success") {
                Result.success(response)
            } else {
                Result.failure(Exception(response.message ?: "Action failed"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
