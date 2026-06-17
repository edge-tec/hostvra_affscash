package com.example.affscash.data.repository

import com.example.affscash.data.model.AdminPlatformSettingsRequest
import com.example.affscash.data.model.AdminPlatformSettingsResponse
import com.example.affscash.data.model.GenericResponse
import com.example.affscash.data.network.ApiService
import javax.inject.Inject

class AdminPlatformSettingsRepository @Inject constructor(private val apiService: ApiService) {
    suspend fun getPlatformSettings(): Result<AdminPlatformSettingsResponse> {
        return try {
            val response = apiService.getAdminPlatformSettings()
            Result.success(response)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updatePlatformSettings(request: AdminPlatformSettingsRequest): Result<GenericResponse> {
        return try {
            val response = apiService.updateAdminPlatformSettings(request)
            Result.success(response)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
