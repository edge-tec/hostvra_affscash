package com.example.affscash.data.repository

import com.example.affscash.data.model.*
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AdminAffiliateManagerRepository @Inject constructor(private val apiService: ApiService) {

    suspend fun getManagers(): Result<List<AdminAffiliateManagerRow>> {
        return withContext(Dispatchers.IO) {
            try {
                val response = apiService.getAdminAffiliateManagers()
                if (response.status == "success") {
                    Result.success(response.data?.managers ?: emptyList())
                } else {
                    Result.failure(Exception(response.message ?: "Failed to fetch managers"))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }

    suspend fun deleteManager(mgrId: Int): Result<String> {
        return withContext(Dispatchers.IO) {
            try {
                val request = AdminAffiliateManagerDeleteRequest(mgr_id = mgrId)
                val response = apiService.deleteAdminAffiliateManager(request)
                if (response.status == "success") {
                    Result.success(response.message ?: "Manager deleted")
                } else {
                    Result.failure(Exception(response.message ?: "Failed to delete manager"))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }
    suspend fun impersonateManager(userId: Int): Result<AuthResponse> {
        return withContext(Dispatchers.IO) {
            try {
                val request = AdminAffiliateManagerImpersonateRequest(user_id = userId)
                val response = apiService.impersonateAdminAffiliateManager(request)
                if (response.success) {
                    Result.success(response)
                } else {
                    Result.failure(Exception(response.error ?: "Failed to impersonate"))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }
}

