package com.example.affscash.data.repository

import com.example.affscash.data.model.OfferDetailsResponse
import com.example.affscash.data.model.OfferResponse
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class OfferRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getOffers(): Result<OfferResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getOffers()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch offers"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getOfferDetails(offerId: Int): Result<OfferDetailsResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getOfferDetails(offerId = offerId)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch offer details"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
