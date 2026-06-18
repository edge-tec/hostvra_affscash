package net.affscash.android.data.repository

import net.affscash.android.data.model.AdminPointsActionRequest
import net.affscash.android.data.model.AdminPointsResponse
import net.affscash.android.data.model.AdminPointsSyncResponse
import net.affscash.android.data.network.ApiService
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AdminPointsRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getPointsData(): Result<AdminPointsResponse> = runCatching {
        apiService.getAdminPoints()
    }

    suspend fun submitAction(request: AdminPointsActionRequest): Result<AdminPointsSyncResponse> = runCatching {
        apiService.submitAdminPointsAction(request = request)
    }
}
