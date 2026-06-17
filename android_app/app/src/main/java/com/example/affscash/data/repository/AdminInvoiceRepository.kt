package com.example.affscash.data.repository

import com.example.affscash.data.model.*
import com.example.affscash.data.network.ApiService
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
}
