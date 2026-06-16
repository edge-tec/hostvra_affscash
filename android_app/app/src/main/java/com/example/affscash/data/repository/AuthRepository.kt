package com.example.affscash.data.repository

import com.example.affscash.data.model.AuthRequest
import com.example.affscash.data.model.AuthResponse
import com.example.affscash.data.network.ApiService
import com.example.affscash.data.network.SessionCookieJar
import com.example.affscash.data.local.UserManager
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepository @Inject constructor(
    private val apiService: ApiService,
    private val sessionCookieJar: SessionCookieJar,
    private val userManager: UserManager
) {
    suspend fun login(email: String, password: String): Result<AuthResponse> = withContext(Dispatchers.IO) {
        try {
            // Clear any old session before logging in
            sessionCookieJar.clearSession()
            val response = apiService.login(AuthRequest(email, password))
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) {
                        it.user?.let { user -> 
                            userManager.saveUser(user.role, user.email, "${user.firstName} ${user.lastName}")
                        }
                        return@withContext Result.success(it)
                    }
                    return@withContext Result.failure(Exception(it.error ?: "Login failed"))
                }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun logout(): Result<Unit> = withContext(Dispatchers.IO) {
        try {
            apiService.logout()
            sessionCookieJar.clearSession()
            userManager.clearUser()
            Result.success(Unit)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
