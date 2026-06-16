package com.example.affscash.data.repository

import com.example.affscash.data.model.InvoiceResponse
import com.example.affscash.data.network.ApiService
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class InvoiceRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getAdminInvoices(): Result<InvoiceResponse> {
        return try {
            val response = apiService.getAdminInvoices()
            if (response.isSuccessful) {
                val body = response.body()
                if (body != null && body.success) {
                    Result.success(body)
                } else {
                    Result.failure(Exception(body?.error ?: "Unknown error"))
                }
            } else {
                Result.failure(Exception("HTTP ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getManagerInvoices(): Result<InvoiceResponse> {
        return try {
            val response = apiService.getManagerInvoices()
            if (response.isSuccessful) {
                val body = response.body()
                if (body != null && body.success) {
                    Result.success(body)
                } else {
                    Result.failure(Exception(body?.error ?: "Unknown error"))
                }
            } else {
                Result.failure(Exception("HTTP ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
