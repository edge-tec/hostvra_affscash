package net.affscash.android.data.repository

import net.affscash.android.data.model.DefaultResponse
import net.affscash.android.data.model.ManagerOfferApprovalListResponse
import net.affscash.android.data.model.ReviewOfferApprovalRequest
import net.affscash.android.data.network.ApiService
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ManagerOfferApprovalRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getOfferApprovals(status: String, offerId: Int?, aff: String?): Result<ManagerOfferApprovalListResponse> {
        return try {
            val response = apiService.getOfferApprovals(status, offerId, aff)
            if (response.success) {
                Result.success(response)
            } else {
                Result.failure(Exception(response.message ?: "Failed to fetch approvals"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun reviewOfferApproval(affiliateId: Int, offerId: Int, action: String): Result<DefaultResponse> {
        return try {
            val request = ReviewOfferApprovalRequest(affiliateId, offerId, action)
            val response = apiService.reviewOfferApproval(request)
            if (response.success) {
                Result.success(response)
            } else {
                Result.failure(Exception(response.message ?: "Failed to review request"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
