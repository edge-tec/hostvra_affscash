package com.example.affscash.data.repository

import com.example.affscash.data.model.*
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AdminAdvertiserRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getAdvertisers(status: String = "all", search: String = ""): Result<AdminAdvertiserResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminAdvertisers(status = status, search = search)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch advertisers"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getAdvertiserDetails(id: Int): Result<AdminAdvertiserDetailsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminAdvertiserDetails(id = id)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch advertiser details"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun createAdvertiser(request: CreateAdvertiserRequest): Result<BasicManagerActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.adminAdvertiserActionCreate(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to create advertiser"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun editAdvertiser(request: EditAdvertiserRequest): Result<BasicManagerActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.adminAdvertiserActionEdit(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to edit advertiser"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun deleteAdvertiser(id: Int): Result<BasicManagerActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.adminAdvertiserActionDelete(AdminAdvertiserActionRequest(id, "delete"))
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to delete advertiser"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateAdvertiserStatus(id: Int, status: String): Result<BasicManagerActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.adminAdvertiserActionStatus(AdminAdvertiserActionRequest(id, "update_status", status))
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

    suspend fun impersonateAdvertiser(id: Int): Result<ImpersonateResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.adminAdvertiserActionImpersonate(AdminAdvertiserActionRequest(id, "impersonate"))
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
}
