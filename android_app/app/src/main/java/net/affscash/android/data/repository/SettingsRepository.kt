package net.affscash.android.data.repository

import net.affscash.android.data.model.*
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class SettingsRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getSettings(): Result<SettingsLoadResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getSettings()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch settings"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateProfile(request: UpdateProfileRequest): Result<SettingsActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.updateProfile(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to update profile"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateSecurity(request: UpdateSecurityRequest): Result<SettingsActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.updateSecurity(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to update security"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updatePayment(request: UpdatePaymentRequest): Result<SettingsActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.updatePayment(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to update payment"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun start2fa(): Result<SettingsActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.start2fa()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to start 2FA"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun verify2fa(request: TwoFactorVerifyRequest): Result<SettingsActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.verify2fa(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to verify 2FA"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun disable2fa(request: TwoFactorDisableRequest): Result<SettingsActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.disable2fa(request)
            if (response.isSuccessful) {
                val body = response.body()
                if (body != null && body.success) {
                    Result.success(body)
                } else {
                    Result.failure(Exception(body?.error ?: "Failed to disable 2FA"))
                }
            } else {
                Result.failure(Exception("API Error: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun requestAccountDelete(request: DeleteAccountRequest): Result<SettingsActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.requestAccountDelete(request)
            if (response.isSuccessful) {
                val body = response.body()
                if (body != null && body.success) {
                    Result.success(body)
                } else {
                    Result.failure(Exception(body?.error ?: "Failed to request account deletion"))
                }
            } else {
                Result.failure(Exception("API Error: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    suspend fun updateGlobalPostback(request: UpdateGlobalPostbackRequest): Result<SettingsActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.updateGlobalPostback(request)
            if (response.isSuccessful) {
                val body = response.body()
                if (body != null && body.success) {
                    Result.success(body)
                } else {
                    Result.failure(Exception(body?.error ?: "Failed to update global postback"))
                }
            } else {
                Result.failure(Exception("API Error: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
