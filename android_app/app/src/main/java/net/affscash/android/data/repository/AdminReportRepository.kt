package net.affscash.android.data.repository

import net.affscash.android.data.model.AdminReportFilterResponse
import net.affscash.android.data.model.AdminReportResponse
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AdminReportRepository @Inject constructor(private val apiService: ApiService) {

    suspend fun getFilters(): Result<AdminReportFilterResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminReportFilters()
            if (response.status == "success") {
                Result.success(response)
            } else {
                Result.failure(Exception("Failed to load filters"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getReports(
        tab: String,
        from: String? = null,
        to: String? = null,
        groupBy: String? = null,
        offerId: Int? = null,
        affiliateId: Int? = null,
        country: String? = null,
        sub1: String? = null,
        slId: Int? = null,
        limit: Int? = null
    ): Result<AdminReportResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminReports(
                tab = tab,
                from = from,
                to = to,
                groupBy = groupBy,
                offerId = offerId,
                affiliateId = affiliateId,
                country = country,
                sub1 = sub1,
                slId = slId,
                limit = limit
            )
            if (response.status == "success") {
                Result.success(response)
            } else {
                Result.failure(Exception(response.message ?: "Failed to load reports"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
