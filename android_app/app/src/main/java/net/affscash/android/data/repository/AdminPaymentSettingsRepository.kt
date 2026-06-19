package net.affscash.android.data.repository

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import net.affscash.android.data.network.ApiService
import net.affscash.android.data.model.BaseResponse
import net.affscash.android.data.model.PaymentSettingsResponse
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AdminPaymentSettingsRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getPaymentSettings(): Result<PaymentSettingsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminPaymentSettings()
            if (response.isSuccessful) {
                response.body()?.let {
                    Result.success(it)
                } ?: Result.failure(Exception("Empty response body"))
            } else {
                Result.failure(Exception("Error: ${response.code()} ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun savePaymentMethod(request: Map<String, String>): Result<BaseResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.savePaymentMethod(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    Result.success(it)
                } ?: Result.failure(Exception("Empty response body"))
            } else {
                Result.failure(Exception("Error: ${response.code()} ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun savePaymentTerms(request: Map<String, Any>): Result<BaseResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.savePaymentTerms(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    Result.success(it)
                } ?: Result.failure(Exception("Empty response body"))
            } else {
                Result.failure(Exception("Error: ${response.code()} ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun saveManagerCommission(request: Map<String, String>): Result<BaseResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.saveManagerCommission(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    Result.success(it)
                } ?: Result.failure(Exception("Empty response body"))
            } else {
                Result.failure(Exception("Error: ${response.code()} ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun saveOfferCommission(request: Map<String, String>): Result<BaseResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.saveOfferCommission(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    Result.success(it)
                } ?: Result.failure(Exception("Empty response body"))
            } else {
                Result.failure(Exception("Error: ${response.code()} ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun savePayoutInfo(request: Map<String, Any>): Result<BaseResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.savePayoutInfo(request)
            if (response.isSuccessful) {
                response.body()?.let {
                    Result.success(it)
                } ?: Result.failure(Exception("Empty response body"))
            } else {
                Result.failure(Exception("Error: ${response.code()} ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
