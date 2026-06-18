package net.affscash.android.data.repository

import net.affscash.android.data.model.AdminShopDashboardResponse
import net.affscash.android.data.model.AdminShopOrdersResponse
import net.affscash.android.data.model.AdminShopProductRequest
import net.affscash.android.data.model.AdminShopOrderUpdateRequest
import net.affscash.android.data.model.AdminShopActionResponse
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AdminShopRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getShopDashboard(): Result<AdminShopDashboardResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminShopDashboard()
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.error ?: "Failed to load shop dashboard"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getShopOrders(status: String? = null, page: Int = 1): Result<AdminShopOrdersResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminShopOrders(status, page)
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.error ?: "Failed to load shop orders"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun saveShopProduct(request: AdminShopProductRequest): Result<AdminShopActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.saveAdminShopProduct(request)
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.error ?: "Failed to save product"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun deleteShopProduct(id: Int): Result<AdminShopActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.deleteAdminShopProduct(mapOf("id" to id))
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.error ?: "Failed to delete product"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateShopOrder(request: AdminShopOrderUpdateRequest): Result<AdminShopActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.updateAdminShopOrder(request)
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.error ?: "Failed to update order"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
