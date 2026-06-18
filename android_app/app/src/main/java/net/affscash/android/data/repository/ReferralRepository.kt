package net.affscash.android.data.repository

import net.affscash.android.data.model.AffiliateReferralResponse
import net.affscash.android.data.model.ManagerReferralResponse
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ReferralRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getAffiliateReferral(): Result<AffiliateReferralResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAffiliateReferral()
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.error ?: "Failed to load referral data"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerReferral(): Result<ManagerReferralResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerReferral()
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.error ?: "Failed to load manager referral data"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
