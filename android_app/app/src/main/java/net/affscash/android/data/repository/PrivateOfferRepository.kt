package net.affscash.android.data.repository

import net.affscash.android.data.model.*
import net.affscash.android.data.network.ApiService
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class PrivateOfferRepository @Inject constructor(
    private val apiService: ApiService
) {

    suspend fun getDashboard(): Result<PrivateOfferDashboardResponse> {
        return try {
            val response = apiService.getPrivateOffersDashboard()
            if (response.isSuccessful) {
                val body = response.body()
                if (body?.status == "success" && body.data != null) {
                    Result.success(body.data)
                } else {
                    Result.failure(Exception("Failed to get dashboard data"))
                }
            } else {
                Result.failure(Exception("Server error: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getDetail(id: Int): Result<PrivateOfferDetailResponse> {
        return try {
            val response = apiService.getPrivateOfferDetail(id)
            if (response.isSuccessful) {
                val body = response.body()
                if (body?.status == "success" && body.data != null) {
                    Result.success(body.data)
                } else {
                    Result.failure(Exception("Failed to get detail data"))
                }
            } else {
                Result.failure(Exception("Server error: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun submitAction(request: PrivateOfferActionRequest): Result<String> {
        return try {
            val response = apiService.submitPrivateOfferAction(request)
            if (response.isSuccessful) {
                val body = response.body()
                if (body?.status == "success") {
                    Result.success(body.message ?: "Action successful")
                } else {
                    Result.failure(Exception(body?.message ?: "Action failed"))
                }
            } else {
                Result.failure(Exception("Server error: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
