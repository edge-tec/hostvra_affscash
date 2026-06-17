package com.example.affscash.data.repository

import com.example.affscash.data.model.DefaultResponse
import com.example.affscash.data.model.ManagerOfferApprovalListResponse
import com.example.affscash.data.model.ReviewOfferApprovalRequest
import com.example.affscash.data.network.ApiService
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
