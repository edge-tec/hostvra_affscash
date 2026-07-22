package net.affscash.android.data.repository

import net.affscash.android.data.model.AuthRequest
import net.affscash.android.data.model.AuthResponse
import net.affscash.android.data.network.ApiService
import net.affscash.android.data.network.SessionCookieJar
import net.affscash.android.data.local.UserManager
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import net.affscash.android.data.local.SecureStorageManager
import net.affscash.android.data.model.User
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepository @Inject constructor(
    private val apiService: ApiService,
    private val sessionCookieJar: SessionCookieJar,
    private val userManager: UserManager,
    private val secureStorageManager: SecureStorageManager
) {
    suspend fun login(email: String, password: String, rememberMe: Boolean = false): Result<AuthResponse> = withContext(Dispatchers.IO) {
        try {
            // Clear any old session before logging in
            sessionCookieJar.clearSession()
            val response = apiService.login(AuthRequest(email, password))
            if (response.isSuccessful) {
                response.body()?.let {
                    if (it.success) {
                        it.user?.let { user -> 
                            val fullName = "${user.firstName} ${user.lastName}".trim()
                            userManager.saveUser(user.role, user.email, fullName)
                            
                            // Save remember me flag & secure session
                            secureStorageManager.isRememberMeEnabled = rememberMe
                            if (rememberMe) {
                                secureStorageManager.saveAuthSession(
                                    role = user.role,
                                    email = user.email,
                                    name = fullName,
                                    token = "session_active",
                                    userId = user.id?.toString()
                                )
                            }
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

    fun hasValidSavedSession(): Boolean {
        return secureStorageManager.hasValidSession()
    }

    fun getSavedUser(): User? {
        val role = secureStorageManager.getUserRole() ?: userManager.getRole() ?: return null
        val email = secureStorageManager.getUserEmail() ?: ""
        val name = secureStorageManager.getUserName() ?: ""
        val nameParts = name.split(" ")
        val firstName = nameParts.firstOrNull() ?: ""
        val lastName = nameParts.drop(1).joinToString(" ")
        return User(
            id = 1,
            firstName = firstName,
            lastName = lastName,
            email = email,
            role = role,
            status = "active"
        )
    }

    suspend fun logout(): Result<Unit> = withContext(Dispatchers.IO) {
        try {
            try {
                val token = net.affscash.android.AffscashApp.getBadgeManager()?.let {}
            } catch (e: Exception) {}

            apiService.logout()
            sessionCookieJar.clearSession()
            userManager.clearUser()
            secureStorageManager.clearSession()
            Result.success(Unit)
        } catch (e: Exception) {
            sessionCookieJar.clearSession()
            userManager.clearUser()
            secureStorageManager.clearSession()
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
