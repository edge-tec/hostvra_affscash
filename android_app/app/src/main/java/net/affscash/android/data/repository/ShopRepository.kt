package net.affscash.android.data.repository

import net.affscash.android.data.model.PlaceOrderRequest
import net.affscash.android.data.model.ShopListResponse
import net.affscash.android.data.model.ShopOrderResponse
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import retrofit2.Response
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ShopRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getShopData(): Response<ShopListResponse> = withContext(Dispatchers.IO) {
        apiService.getShopData()
    }

    suspend fun placeOrder(req: PlaceOrderRequest): Response<ShopOrderResponse> = withContext(Dispatchers.IO) {
        apiService.placeShopOrder(req)
    }
}
