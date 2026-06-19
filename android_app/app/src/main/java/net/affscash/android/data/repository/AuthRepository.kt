package net.affscash.android.data.repository

import net.affscash.android.data.model.AuthRequest
import net.affscash.android.data.model.AuthResponse
import net.affscash.android.data.network.ApiService
import net.affscash.android.data.network.SessionCookieJar
import net.affscash.android.data.local.UserManager
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
            } else if (response.code() == 401) {
                val errorBody = response.errorBody()?.string()
                if (errorBody != null) {
                    try {
                        val jsonObject = org.json.JSONObject(errorBody)
                        if (jsonObject.has("error")) {
                            return@withContext Result.failure(Exception(jsonObject.getString("error")))
                        }
                    } catch (e: Exception) {
                        // Fallback below
                    }
                }
                return@withContext Result.failure(Exception("Invalid email or password."))
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(Exception("Connection failed. Please check your internet connection and try again."))
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

    suspend fun forgotPassword(email: String): Result<net.affscash.android.data.model.BasicResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.forgotPassword(net.affscash.android.data.model.ForgotPasswordRequest(email))
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun verifyOtp(email: String, otp: String): Result<net.affscash.android.data.model.VerifyOtpResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.verifyOtp(net.affscash.android.data.model.VerifyOtpRequest(email, otp))
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun resetPassword(email: String, token: String, password: String): Result<net.affscash.android.data.model.BasicResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.resetPassword(net.affscash.android.data.model.ResetPasswordRequest(email, token, password))
            if (response.isSuccessful) {
                response.body()?.let { return@withContext Result.success(it) }
            }
            Result.failure(Exception("Network error: ${response.code()}"))
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
