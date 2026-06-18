package net.affscash.android.data.repository

import net.affscash.android.data.model.RewardsResponse
import net.affscash.android.data.network.ApiService
import javax.inject.Inject

class RewardsRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getRewards(): Result<RewardsResponse> {
        return try {
            val response = apiService.getRewards()
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
