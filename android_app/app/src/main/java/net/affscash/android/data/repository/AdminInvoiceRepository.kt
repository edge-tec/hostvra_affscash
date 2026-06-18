package net.affscash.android.data.repository

import net.affscash.android.data.model.*
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AdminInvoiceRepository @Inject constructor(private val apiService: ApiService) {

    suspend fun getInvoices(): Result<List<AdminInvoiceRow>> {
        return withContext(Dispatchers.IO) {
            try {
                val response = apiService.getAdminInvoicesList()
                if (response.status == "success") {
                    Result.success(response.data ?: emptyList())
                } else {
                    Result.failure(Exception(response.message ?: "Failed to fetch invoices"))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }

    suspend fun getInvoiceDetail(id: Int): Result<AdminInvoiceDetail> {
        return withContext(Dispatchers.IO) {
            try {
                val response = apiService.getAdminInvoiceDetail(id)
                if (response.status == "success" && response.data != null) {
                    Result.success(response.data)
                } else {
                    Result.failure(Exception(response.message ?: "Failed to fetch invoice details"))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }

    suspend fun updateInvoiceStatus(id: Int, status: String): Result<String> {
        return withContext(Dispatchers.IO) {
            try {
                val request = AdminInvoiceStatusRequest(invoice_id = id, status = status)
                val response = apiService.updateAdminInvoiceStatus(request)
                if (response.status == "success") {
                    Result.success(response.message ?: "Status updated")
                } else {
                    Result.failure(Exception(response.message ?: "Failed to update status"))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }

    suspend fun deleteInvoice(id: Int): Result<String> {
        return withContext(Dispatchers.IO) {
            try {
                val request = AdminInvoiceDeleteRequest(invoice_id = id)
                val response = apiService.deleteAdminInvoice(request)
                if (response.status == "success") {
                    Result.success(response.message ?: "Invoice deleted")
                } else {
                    Result.failure(Exception(response.message ?: "Failed to delete invoice"))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }

    suspend fun getFormData(): Result<AdminInvoiceFormData> {
        return withContext(Dispatchers.IO) {
            try {
                val response = apiService.getAdminInvoiceFormData()
                if (response.status == "success" && response.data != null) {
                    Result.success(response.data)
                } else {
                    Result.failure(Exception(response.message ?: "Failed to fetch form data"))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }

    suspend fun getAffiliateInfo(affiliateId: Int): Result<AdminInvoiceAffiliateInfo> {
        return withContext(Dispatchers.IO) {
            try {
                val response = apiService.getAdminInvoiceAffiliateInfo(affiliateId)
                if (response.status == "success" && response.data != null) {
                    Result.success(response.data)
                } else {
                    Result.failure(Exception(response.message ?: "Failed to fetch affiliate info"))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }

    suspend fun getManagerInfo(managerId: Int): Result<AdminInvoiceManagerInfo> {
        return withContext(Dispatchers.IO) {
            try {
                val response = apiService.getAdminInvoiceManagerInfo(managerId)
                if (response.status == "success" && response.data != null) {
                    Result.success(response.data)
                } else {
                    Result.failure(Exception(response.message ?: "Failed to fetch manager info"))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }

    suspend fun loadOffers(affiliateId: Int, from: String, to: String): Result<List<AdminInvoiceOfferItem>> {
        return withContext(Dispatchers.IO) {
            try {
                val response = apiService.loadAdminInvoiceOffers(affiliateId, from, to)
                if (response.status == "success" && response.data != null) {
                    Result.success(response.data.offers)
                } else {
                    Result.failure(Exception(response.message ?: "Failed to load offers"))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }

    suspend fun createInvoice(request: AdminCreateInvoiceRequest): Result<String> {
        return withContext(Dispatchers.IO) {
            try {
                val response = apiService.createAdminInvoice(request)
                if (response.status == "success") {
                    Result.success(response.message ?: "Invoice created successfully")
                } else {
                    Result.failure(Exception(response.message ?: "Failed to create invoice"))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    }
}
