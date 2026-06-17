package com.example.affscash.data.repository

import com.example.affscash.data.model.PlaceOrderRequest
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ShopRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getShopData() = withContext(Dispatchers.IO) {
        apiService.getShopData()
    }

    suspend fun placeOrder(req: PlaceOrderRequest) = withContext(Dispatchers.IO) {
        apiService.placeShopOrder(req)
    }
}
