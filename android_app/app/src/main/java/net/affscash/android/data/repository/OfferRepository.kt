package net.affscash.android.data.repository

import net.affscash.android.data.model.OfferDetailsResponse
import net.affscash.android.data.model.OfferResponse
import net.affscash.android.data.model.AdminOfferResponse
import net.affscash.android.data.model.ManagerOfferResponse
import net.affscash.android.data.network.ApiService
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
        accessFilter: String? = null,
        inHouse: Boolean? = null
    ): Result<OfferResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getOffers(
                query = query,
                category = category,
                payoutType = payoutType,
                offerType = offerType,
                country = country,
                device = device,
                accessFilter = accessFilter,
                inHouse = inHouse
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

    suspend fun applyOffer(offerId: Int, promoDesc: String?): Result<net.affscash.android.data.model.ApplyOfferResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.applyOffer(net.affscash.android.data.model.ApplyOfferRequest(offerId, promoDesc))
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

    suspend fun getAdminOffers(
        query: String? = null,
        category: String? = null,
        payoutType: String? = null,
        status: String? = null,
        offerType: String? = null,
        country: String? = null,
        device: String? = null,
        offerId: Int? = null,
        access: String? = null,
        inHouse: Boolean? = null
    ): Result<AdminOfferResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminOffers(query, category, payoutType, status, offerType, country, device, offerId, access, inHouse)
            if (response.isSuccessful && response.body()?.success == true) {
                return@withContext Result.success(response.body()!!)
            } else {
                response.body()?.let {
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch admin offers"))
                }
                return@withContext Result.failure(Exception("Error fetching admin offers: ${response.code()}"))
            }
        } catch (e: Exception) {
            return@withContext Result.failure(e)
        }
    }

    suspend fun createAdminOffer(request: net.affscash.android.data.model.AdminOfferCreateRequest): Result<net.affscash.android.data.model.AdminOfferActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.createAdminOffer(request)
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.error ?: "Failed to create offer"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun editAdminOffer(request: net.affscash.android.data.model.AdminOfferEditRequest): Result<net.affscash.android.data.model.AdminOfferActionResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.editAdminOffer(request)
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.error ?: "Failed to update offer"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateAdminOfferStatus(id: Int, status: String): Result<Boolean> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.updateAdminOfferStatus(mapOf("id" to id.toString(), "status" to status))
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(true)
            } else {
                Result.failure(Exception(response.body()?.error ?: "Failed to update status"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun deleteAdminOffer(id: Int): Result<Boolean> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.deleteAdminOffer(mapOf("id" to id))
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(true)
            } else {
                Result.failure(Exception(response.body()?.error ?: "Failed to delete offer"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerOffers(
        tab: String = "regular",
        query: String? = null,
        category: String? = null,
        payoutType: String? = null,
        offerType: String? = null,
        statusFilter: String? = null,
        country: String? = null,
        device: String? = null,
        offerId: Int? = null,
        accessFilter: String? = null
    ): Result<ManagerOfferResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerOffers(
                tab = tab,
                query = query,
                category = category,
                payoutType = payoutType,
                offerType = offerType,
                statusFilter = statusFilter,
                country = country,
                device = device,
                offerId = offerId,
                accessFilter = accessFilter
            )
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

    suspend fun getManagerOfferFilters(): Result<net.affscash.android.data.model.ManagerOfferFiltersResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerOfferFilters()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch manager offer filters"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
