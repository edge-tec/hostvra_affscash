package net.affscash.android.data.repository

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import net.affscash.android.data.model.GenericResponse
import net.affscash.android.data.model.VpnSkipListResponse
import net.affscash.android.data.network.ApiService
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class VpnSkipListRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getVpnSkipList(): Result<VpnSkipListResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminVpnSkipList()
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun addVpnSkipEntry(affiliateId: Int, note: String?): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val body = mapOf("affiliate_id" to affiliateId, "note" to note)
            val response = apiService.addAdminVpnSkipEntry(body)
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun removeVpnSkipEntry(id: Int): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val body = mapOf("id" to id)
            val response = apiService.removeAdminVpnSkipEntry(body)
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
