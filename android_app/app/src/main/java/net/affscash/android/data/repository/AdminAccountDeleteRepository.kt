package net.affscash.android.data.repository

import net.affscash.android.data.model.AdminAccountDeleteActionRequest
import net.affscash.android.data.model.AdminAccountDeleteResponse
import net.affscash.android.data.model.BasicResponse
import net.affscash.android.data.network.ApiService
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AdminAccountDeleteRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getRequests(status: String?): Result<AdminAccountDeleteResponse> = runCatching {
        apiService.getAdminAccountDeleteRequests(status = status)
    }

    suspend fun submitAction(request: AdminAccountDeleteActionRequest): Result<BasicResponse> = runCatching {
        apiService.submitAdminAccountDeleteAction(request = request)
    }
}
