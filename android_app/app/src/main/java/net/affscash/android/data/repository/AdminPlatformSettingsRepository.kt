package net.affscash.android.data.repository

import net.affscash.android.data.model.AdminPlatformSettingsRequest
import net.affscash.android.data.model.AdminPlatformSettingsResponse
import net.affscash.android.data.model.GenericResponse
import net.affscash.android.data.network.ApiService
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
