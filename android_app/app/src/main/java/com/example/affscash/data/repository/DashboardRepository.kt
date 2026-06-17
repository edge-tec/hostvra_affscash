package com.example.affscash.data.repository

import com.example.affscash.data.model.DashboardResponse
import com.example.affscash.data.model.AdminDashboardResponse
import com.example.affscash.data.model.ManagerDashboardResponse
import com.example.affscash.data.model.ManagerTrendResponse
import com.example.affscash.data.model.ManagerFiltersResponse
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class DashboardRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getDashboardData(): Result<DashboardResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getDashboard()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch dashboard"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getAdminDashboardData(): Result<AdminDashboardResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminDashboard()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch admin dashboard"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerDashboardData(): Result<ManagerDashboardResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerDashboard()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch manager dashboard"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerStats(
        from: String? = null, to: String? = null, offerId: Int? = null, affiliateId: Int? = null, country: String? = null, device: String? = null
    ): Result<ManagerDashboardResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerDashboardStats(from = from, to = to, offerId = offerId, affiliateId = affiliateId, country = country, device = device)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch stats"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerTrend(
        from: String? = null, to: String? = null, offerId: Int? = null, affiliateId: Int? = null, country: String? = null, device: String? = null
    ): Result<ManagerTrendResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerDashboardTrend(from = from, to = to, offerId = offerId, affiliateId = affiliateId, country = country, device = device)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch trend"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerFilters(): Result<ManagerFiltersResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerDashboardFilters()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch filters"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
