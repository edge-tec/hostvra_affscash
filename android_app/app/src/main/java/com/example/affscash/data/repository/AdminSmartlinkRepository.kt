package com.example.affscash.data.repository

import com.example.affscash.data.model.AdminSmartlinkActionRequest
import com.example.affscash.data.model.AdminSmartlinkDashboardWrapperResponse
import com.example.affscash.data.model.AdminSmartlinkDetailWrapperResponse
import com.example.affscash.data.model.AdminSmartlinkRequestsWrapperResponse
import com.example.affscash.data.model.AdminSmartlinkSaveRequest
import com.example.affscash.data.model.GenericResponse
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import retrofit2.Response

import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AdminSmartlinkRepository @Inject constructor(private val apiService: ApiService) {

    suspend fun getAdminSmartlinksDashboard(): Result<AdminSmartlinkDashboardWrapperResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminSmartlinksDashboard()
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.message() ?: "Unknown error"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getAdminSmartlinkRequests(): Result<AdminSmartlinkRequestsWrapperResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminSmartlinkRequests()
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.message() ?: "Unknown error"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getAdminSmartlinkDetail(id: Int): Result<AdminSmartlinkDetailWrapperResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminSmartlinkDetail(id)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.message() ?: "Unknown error"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun submitAdminSmartlinkAction(request: AdminSmartlinkActionRequest): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.submitAdminSmartlinkAction(request)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.message() ?: "Unknown error"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun saveAdminSmartlink(request: AdminSmartlinkSaveRequest): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.saveAdminSmartlink(request)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.message() ?: "Unknown error"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
