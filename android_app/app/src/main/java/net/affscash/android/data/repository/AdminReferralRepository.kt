package net.affscash.android.data.repository

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import net.affscash.android.data.model.*
import net.affscash.android.data.network.ApiService
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AdminReferralRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getDashboard(): Flow<Result<AdminReferralDashboardResponse>> = flow {
        try {
            val response = apiService.getAdminReferralDashboard()
            if (response.isSuccessful && response.body() != null) {
                emit(Result.success(response.body()!!))
            } else {
                emit(Result.failure(Exception("Failed to load dashboard: ${response.message()}")))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    suspend fun getSignups(): Flow<Result<AdminReferralSignupsResponse>> = flow {
        try {
            val response = apiService.getAdminReferralSignups()
            if (response.isSuccessful && response.body() != null) {
                emit(Result.success(response.body()!!))
            } else {
                emit(Result.failure(Exception("Failed to load signups: ${response.message()}")))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    suspend fun getCommissions(): Flow<Result<AdminReferralCommissionsResponse>> = flow {
        try {
            val response = apiService.getAdminReferralCommissions()
            if (response.isSuccessful && response.body() != null) {
                emit(Result.success(response.body()!!))
            } else {
                emit(Result.failure(Exception("Failed to load commissions: ${response.message()}")))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    suspend fun getCodes(): Flow<Result<AdminReferralCodesResponse>> = flow {
        try {
            val response = apiService.getAdminReferralCodes()
            if (response.isSuccessful && response.body() != null) {
                emit(Result.success(response.body()!!))
            } else {
                emit(Result.failure(Exception("Failed to load codes: ${response.message()}")))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    suspend fun approveCommission(commissionId: Int): Flow<Result<BasicResponse>> = flow {
        try {
            val response = apiService.approveAdminReferralCommission(AdminReferralActionRequest(commissionId))
            if (response.isSuccessful && response.body() != null) {
                emit(Result.success(response.body()!!))
            } else {
                emit(Result.failure(Exception("Failed to approve commission: ${response.message()}")))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    suspend fun rejectCommission(commissionId: Int): Flow<Result<BasicResponse>> = flow {
        try {
            val response = apiService.rejectAdminReferralCommission(AdminReferralActionRequest(commissionId))
            if (response.isSuccessful && response.body() != null) {
                emit(Result.success(response.body()!!))
            } else {
                emit(Result.failure(Exception("Failed to reject commission: ${response.message()}")))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }
}
