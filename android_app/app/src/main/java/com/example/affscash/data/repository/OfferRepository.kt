package com.example.affscash.data.repository

import com.example.affscash.data.model.OfferDetailsResponse
import com.example.affscash.data.model.OfferResponse
import com.example.affscash.data.model.AdminOfferResponse
import com.example.affscash.data.model.ManagerOfferResponse
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class OfferRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getOffers(
        query: String? = null,
        category: String? = null,
        payoutType: String? = null,
        offerType: String? = null,
        country: String? = null,
        device: String? = null,
        accessFilter: String? = null
    ): Result<OfferResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getOffers(
                query = query,
                category = category,
                payoutType = payoutType,
                offerType = offerType,
                country = country,
                device = device,
                accessFilter = accessFilter
            )
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

    suspend fun applyOffer(offerId: Int, promoDesc: String?): Result<com.example.affscash.data.model.ApplyOfferResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.applyOffer(com.example.affscash.data.model.ApplyOfferRequest(offerId, promoDesc))
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to apply for offer"))
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

    suspend fun getAdminOffers(): Result<AdminOfferResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminOffers()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch admin offers"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerOffers(): Result<ManagerOfferResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerOffers()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch manager offers"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
