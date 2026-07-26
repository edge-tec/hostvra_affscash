package net.affscash.android.data.repository

import net.affscash.android.data.model.AffiliateDuplicateConversionsResponse
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class DuplicateConversionsRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getDuplicateConversions(from: String, to: String): Result<AffiliateDuplicateConversionsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getDuplicateConversions(from = from, to = to, startDate = from, endDate = to)
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.error ?: "Failed to load duplicate conversions"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
