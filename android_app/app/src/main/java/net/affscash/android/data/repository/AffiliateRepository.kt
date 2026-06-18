package net.affscash.android.data.repository

import net.affscash.android.data.model.AdminAffiliateResponse
import net.affscash.android.data.model.ManagerAffiliateResponse
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import net.affscash.android.data.model.AffiliateActionRequest
import net.affscash.android.data.model.ManagerAffiliateActionRequest
import net.affscash.android.data.model.ImpersonateResponse
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AffiliateRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getAdminAffiliates(status: String = "all", search: String = ""): Result<AdminAffiliateResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminAffiliates(status, search)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch admin affiliates"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerAffiliates(status: String = "all", search: String = ""): Result<ManagerAffiliateResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerAffiliates(status, search)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch manager affiliates"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun adminAffiliateAction(action: String, userId: Int): Result<ImpersonateResponse> = withContext(Dispatchers.IO) {
        try {
            val request = AffiliateActionRequest(action = action, userId = userId)
            val response = apiService.adminAffiliateAction(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Action failed"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun managerAffiliateAction(action: String, affId: Int): Result<ImpersonateResponse> = withContext(Dispatchers.IO) {
        try {
            val request = ManagerAffiliateActionRequest(action = action, affId = affId)
            val response = apiService.managerAffiliateAction(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Action failed"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun stopImpersonate(): Result<ImpersonateResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.stopImpersonate()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Stop impersonate failed"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
