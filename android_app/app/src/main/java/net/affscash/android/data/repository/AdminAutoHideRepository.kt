package net.affscash.android.data.repository

import net.affscash.android.data.model.*
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AdminAutoHideRepository @Inject constructor(private val apiService: ApiService) {

    suspend fun getStats(): Result<AdminAutoHideStatsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminAutoHideStats()
            if (response.status == "success") Result.success(response)
            else Result.failure(Exception("Failed to load stats"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getRules(): Result<AdminAutoHideRulesResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminAutoHideRules()
            if (response.status == "success") Result.success(response)
            else Result.failure(Exception("Failed to load rules"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getHiddenConversions(): Result<AdminAutoHideConversionsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminAutoHideConversions()
            if (response.status == "success") Result.success(response)
            else Result.failure(Exception("Failed to load hidden conversions"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getFilters(): Result<AdminReportFilterResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminAutoHideFilters()
            if (response.status == "success") Result.success(response)
            else Result.failure(Exception("Failed to load filters"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun createRule(request: AdminAutoHideCreateRequest): Result<AdminAutoHideActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.createAdminAutoHideRule(request)
            if (response.status == "success") Result.success(response)
            else Result.failure(Exception(response.message ?: "Failed to create rule"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun toggleRule(ruleId: Int): Result<AdminAutoHideActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.toggleAdminAutoHideRule(AdminAutoHideActionRequest(rule_id = ruleId))
            if (response.status == "success") Result.success(response)
            else Result.failure(Exception(response.message ?: "Failed to toggle rule"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun deleteRule(ruleId: Int): Result<AdminAutoHideActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.deleteAdminAutoHideRule(AdminAutoHideActionRequest(rule_id = ruleId))
            if (response.status == "success") Result.success(response)
            else Result.failure(Exception(response.message ?: "Failed to delete rule"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun unhideConversion(conversionId: String): Result<AdminAutoHideActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.unhideAdminConversion(AdminAutoHideActionRequest(conversion_id = conversionId))
            if (response.status == "success") Result.success(response)
            else Result.failure(Exception(response.message ?: "Failed to unhide conversion"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
