package com.example.affscash.data.repository

import com.example.affscash.data.model.InvoiceResponse
import com.example.affscash.data.model.PdfDownloadResponse
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class InvoiceRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getInvoices(): Result<InvoiceResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getInvoices()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch invoices"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getAdminInvoices(): Result<InvoiceResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getAdminInvoices()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch admin invoices"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerInvoices(): Result<com.example.affscash.data.model.ManagerInvoicesResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerInvoices()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to fetch manager invoices"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun downloadInvoicePdf(invoiceId: Int): Result<PdfDownloadResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.downloadInvoicePdf(invoiceId = invoiceId)
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success && it.url != null) return@withContext Result.success(it)
                    return@withContext Result.failure(Exception(it.error ?: "Failed to get PDF URL"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
