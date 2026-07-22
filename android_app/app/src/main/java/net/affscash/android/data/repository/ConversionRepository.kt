package net.affscash.android.data.repository

import net.affscash.android.data.model.ConversionResponse
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ConversionRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getAdminConversions(from: String? = null, to: String? = null): Result<ConversionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminConversions(from = from, to = to, startDate = from, endDate = to)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch admin conversions"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerConversions(from: String? = null, to: String? = null): Result<ConversionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerConversions(from = from, to = to, startDate = from, endDate = to)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch manager conversions"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getAdminDuplicateConversions(from: String? = null, to: String? = null): Result<net.affscash.android.data.model.DuplicateConversionsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminDuplicateConversions(from = from, to = to, startDate = from, endDate = to)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch duplicate conversions"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
