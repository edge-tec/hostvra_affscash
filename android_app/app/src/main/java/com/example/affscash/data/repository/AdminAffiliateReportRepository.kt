package com.example.affscash.data.repository

import com.example.affscash.data.model.AdminAffiliateReportResponse
import com.example.affscash.data.model.AdminReportFilterResponse
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AdminAffiliateReportRepository @Inject constructor(private val apiService: ApiService) {

    suspend fun getReport(
        from: String? = null,
        to: String? = null,
        affiliateId: String? = null,
        affiliateCode: String? = null,
        affiliateName: String? = null,
        offerId: String? = null,
        country: String? = null,
        convStatus: String? = null,
        device: String? = null,
        trafficStatus: String? = null,
        ip: String? = null,
        limit: String? = null
    ): Result<AdminAffiliateReportResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminAffiliateReport(
                from = from,
                to = to,
                affiliateId = affiliateId,
                affiliateCode = affiliateCode,
                affiliateName = affiliateName,
                offerId = offerId,
                country = country,
                convStatus = convStatus,
                device = device,
                trafficStatus = trafficStatus,
                ip = ip,
                limit = limit
            )
            if (response.status == "success") Result.success(response)
            else Result.failure(Exception(response.message ?: "Failed to load affiliate report"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getFilters(): Result<AdminReportFilterResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminAffiliateReportFilters()
            if (response.status == "success") Result.success(response)
            else Result.failure(Exception("Failed to load filters"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
