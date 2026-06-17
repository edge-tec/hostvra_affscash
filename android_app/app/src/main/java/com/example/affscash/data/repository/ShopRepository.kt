package com.example.affscash.data.repository

import com.example.affscash.data.model.PlaceOrderRequest
import com.example.affscash.data.model.ShopListResponse
import com.example.affscash.data.model.ShopOrderResponse
import com.example.affscash.data.network.ApiService
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
