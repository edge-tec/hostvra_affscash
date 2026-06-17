package com.example.affscash.data.repository

import com.example.affscash.data.model.*
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MultipartBody
import okhttp3.RequestBody
import javax.inject.Inject

class ManagerProfileRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getManagerProfile(): Result<ManagerProfileResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerProfile()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) Result.success(it)
                    else Result.failure(Exception(it.error ?: "Failed to load profile"))
                } ?: Result.failure(Exception("Empty response body"))
            } else {
                Result.failure(Exception("HTTP error: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateManagerProfile(
        firstName: RequestBody,
        lastName: RequestBody,
        email: RequestBody,
        company: RequestBody?,
        phone: RequestBody?,
        skype: RequestBody?,
        telegram: RequestBody?,
        discord: RequestBody?,
        profilePic: MultipartBody.Part?
    ): Result<SimpleResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.updateManagerProfile(
                firstName, lastName, email, company, phone, skype, telegram, discord, profilePic
            )
            handleSimpleResponse(response)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateSecurity(request: Map<String, String>): Result<SimpleResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.updateManagerSecurity(request)
            handleSimpleResponse(response)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updatePayment(request: Map<String, String>): Result<SimpleResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.updateManagerPayment(request)
            handleSimpleResponse(response)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun start2fa(): Result<TwoFactorStartResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.startManager2fa()
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) Result.success(it)
                    else Result.failure(Exception(it.error ?: "Failed to start 2FA"))
                } ?: Result.failure(Exception("Empty response"))
            } else {
                Result.failure(Exception("HTTP error"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun verify2fa(request: Map<String, String>): Result<SimpleResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.verifyManager2fa(request)
            handleSimpleResponse(response)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun disable2fa(request: Map<String, String>): Result<SimpleResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.disableManager2fa(request)
            handleSimpleResponse(response)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    private fun handleSimpleResponse(response: retrofit2.Response<SimpleResponse>): Result<SimpleResponse> {
        return if (response.isSuccessful) {
            response.body()?.let {
                if (it.success) Result.success(it)
                else Result.failure(Exception(it.error ?: "Unknown API error"))
            } ?: Result.failure(Exception("Empty response body"))
        } else {
            Result.failure(Exception("HTTP error: ${response.code()}"))
        }
    }
}
