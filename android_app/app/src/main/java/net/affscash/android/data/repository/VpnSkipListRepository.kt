package net.affscash.android.data.repository

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import net.affscash.android.data.model.AddVpnSkipRequest
import net.affscash.android.data.model.GenericResponse
import net.affscash.android.data.model.RemoveVpnSkipRequest
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
            val response = apiService.addAdminVpnSkipEntry(AddVpnSkipRequest(affiliateId, note))
            if (response.isSuccessful) {
                val res = response.body()
                if (res != null) {
                    if (res.status == "error") {
                        return@withContext Result.failure(Exception(res.message ?: "Failed to add exemption"))
                    }
                    return@withContext Result.success(res)
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun removeVpnSkipEntry(id: Int): Result<GenericResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.removeAdminVpnSkipEntry(RemoveVpnSkipRequest(id))
            if (response.isSuccessful) {
                val res = response.body()
                if (res != null) {
                    if (res.status == "error") {
                        return@withContext Result.failure(Exception(res.message ?: "Failed to remove exemption"))
                    }
                    return@withContext Result.success(res)
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
