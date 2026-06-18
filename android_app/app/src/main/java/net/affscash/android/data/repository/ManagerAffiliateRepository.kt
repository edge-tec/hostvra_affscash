package net.affscash.android.data.repository

import net.affscash.android.data.model.*
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ManagerAffiliateRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getManagerAffiliates(query: String?, fraudScoreFilter: String, status: String = "all"): Result<ManagerAffiliatesResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerAffiliates(query = query, fraudScoreFilter = fraudScoreFilter, status = status)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch affiliates"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun createAffiliate(request: CreateAffiliateRequest): Result<BasicManagerActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.createManagerAffiliate(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to create affiliate"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getAffiliateDetails(affId: Int): Result<ManagerAffiliateDetailsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerAffiliateDetails(affId = affId)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch affiliate details"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun editAffiliate(request: EditAffiliateRequest): Result<BasicManagerActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.editManagerAffiliate(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to edit affiliate"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateAffiliateStatus(affId: Int, status: String): Result<BasicManagerActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.updateManagerAffiliateStatus(UpdateAffiliateStatusRequest(affId, status))
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to update status"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun impersonateAffiliate(affId: Int): Result<ImpersonateResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.impersonateAffiliate(ImpersonateAffiliateRequest(affId))
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to impersonate"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun stopImpersonating(): Result<StopImpersonateResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.stopImpersonating()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to stop impersonating"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
