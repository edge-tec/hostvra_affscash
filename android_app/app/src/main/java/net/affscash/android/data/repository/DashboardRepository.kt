package net.affscash.android.data.repository

import net.affscash.android.data.model.DashboardResponse
import net.affscash.android.data.model.AdminDashboardResponse
import net.affscash.android.data.model.ManagerDashboardResponse
import net.affscash.android.data.model.ManagerTrendResponse
import net.affscash.android.data.model.ManagerFiltersResponse
import net.affscash.android.data.model.ManagerDashboardExtraResponse
import net.affscash.android.data.model.DashboardAnalyticsStatsResponse
import net.affscash.android.data.model.DashboardTrendChartResponse
import net.affscash.android.data.model.DashboardPieChartResponse
import net.affscash.android.data.model.DashboardHourlyResponse
import net.affscash.android.data.model.DashboardCountriesResponse
import net.affscash.android.data.model.DashboardOffersResponse
import net.affscash.android.data.network.ApiService
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

    suspend fun getAffiliateStats(from: String, to: String, offerId: Int? = null, country: String? = null, device: String? = null): Result<DashboardAnalyticsStatsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAffiliateAnalyticsStats(from, to, offerId, country, device)
            if (response.isSuccessful) response.body()?.let { return@withContext Result.success(it) }
            Result.failure(Exception("Failed to fetch stats"))
        } catch (e: Exception) { Result.failure(e) }
    }

    suspend fun getAffiliateTrend(from: String, to: String, offerId: Int? = null, country: String? = null, device: String? = null): Result<DashboardTrendChartResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAffiliateAnalyticsTrend(from, to, offerId, country, device)
            if (response.isSuccessful) response.body()?.let { return@withContext Result.success(it) }
            Result.failure(Exception("Failed to fetch trend"))
        } catch (e: Exception) { Result.failure(e) }
    }

    suspend fun getAffiliateDevices(from: String, to: String, offerId: Int? = null, country: String? = null, device: String? = null): Result<DashboardPieChartResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAffiliateAnalyticsDevices(from, to, offerId, country, device)
            if (response.isSuccessful) response.body()?.let { return@withContext Result.success(it) }
            Result.failure(Exception("Failed to fetch devices"))
        } catch (e: Exception) { Result.failure(e) }
    }

    suspend fun getAffiliateBrowsers(from: String, to: String, offerId: Int? = null, country: String? = null, device: String? = null): Result<DashboardPieChartResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAffiliateAnalyticsBrowsers(from, to, offerId, country, device)
            if (response.isSuccessful) response.body()?.let { return@withContext Result.success(it) }
            Result.failure(Exception("Failed to fetch browsers"))
        } catch (e: Exception) { Result.failure(e) }
    }

    suspend fun getAffiliateSources(from: String, to: String, offerId: Int? = null, country: String? = null, device: String? = null): Result<DashboardPieChartResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAffiliateAnalyticsSources(from, to, offerId, country, device)
            if (response.isSuccessful) response.body()?.let { return@withContext Result.success(it) }
            Result.failure(Exception("Failed to fetch sources"))
        } catch (e: Exception) { Result.failure(e) }
    }

    suspend fun getAffiliateHourly(from: String, to: String, offerId: Int? = null, country: String? = null, device: String? = null): Result<DashboardHourlyResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAffiliateAnalyticsHourly(from, to, offerId, country, device)
            if (response.isSuccessful) response.body()?.let { return@withContext Result.success(it) }
            Result.failure(Exception("Failed to fetch hourly"))
        } catch (e: Exception) { Result.failure(e) }
    }

    suspend fun getAffiliateCountries(from: String, to: String, offerId: Int? = null, country: String? = null, device: String? = null): Result<DashboardCountriesResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAffiliateAnalyticsCountries(from, to, offerId, country, device)
            if (response.isSuccessful) response.body()?.let { return@withContext Result.success(it) }
            Result.failure(Exception("Failed to fetch countries"))
        } catch (e: Exception) { Result.failure(e) }
    }

    suspend fun getAffiliateOffers(from: String, to: String, offerId: Int? = null, country: String? = null, device: String? = null): Result<DashboardOffersResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAffiliateAnalyticsOffers(from, to, offerId, country, device)
            if (response.isSuccessful) response.body()?.let { return@withContext Result.success(it) }
            Result.failure(Exception("Failed to fetch offers"))
        } catch (e: Exception) { Result.failure(e) }
    }

    suspend fun getAdminDashboardData(from: String? = null, to: String? = null): Result<AdminDashboardResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminDashboard(from, to)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Failed to load admin dashboard: ${response.code()}"))
            }
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
        from: String, to: String, offerId: Int? = null, affiliateId: Int? = null, country: String? = null, device: String? = null
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

    suspend fun getManagerExtra(
        from: String, to: String, offerId: Int? = null, affiliateId: Int? = null, country: String? = null, device: String? = null
    ): Result<ManagerDashboardExtraResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerDashboardExtra(from = from, to = to, offerId = offerId, affiliateId = affiliateId, country = country, device = device)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch extra"))
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
