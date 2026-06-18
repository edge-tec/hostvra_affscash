package net.affscash.android.data.repository

import net.affscash.android.data.model.ReportFiltersResponse
import net.affscash.android.data.model.ReportResponse
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ReportRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getReports(
        tab: String,
        from: String?,
        to: String?,
        offerId: Int?,
        country: String?,
        sub1: String?
    ): Result<ReportResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getReports(tab, from, to, offerId, country, sub1)
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

    suspend fun getReportFilters(): Result<ReportFiltersResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getReportFilters()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch report filters"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
