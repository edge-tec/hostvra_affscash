package net.affscash.android.service

import android.content.Context
import android.provider.Settings
import android.util.Log
import com.google.firebase.messaging.FirebaseMessaging
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import kotlinx.coroutines.tasks.await
import net.affscash.android.data.network.ApiService
import javax.inject.Inject
import javax.inject.Singleton

/**
 * Manages the FCM token lifecycle:
 * - Retrieves and caches the current FCM token
 * - Registers token with the backend server
 * - Handles token refresh
 * - Unregisters token on logout
 * - Persists token locally for retry on failure
 *
 * Uses SharedPreferences for token persistence so that on next
 * app launch, if the previous registration failed, it can be retried.
 */
@Singleton
class FcmTokenManager @Inject constructor(
    @ApplicationContext private val context: Context,
    private val apiService: ApiService
) {
    private val prefs = context.getSharedPreferences("fcm_token_prefs", Context.MODE_PRIVATE)
    private val scope = CoroutineScope(Dispatchers.IO + SupervisorJob())

    companion object {
        private const val TAG = "FcmTokenManager"
        private const val KEY_TOKEN = "fcm_token"
        private const val KEY_TOKEN_REGISTERED = "fcm_token_registered"
        private const val KEY_LAST_REGISTRATION_ATTEMPT = "fcm_last_reg_attempt"
        private const val REGISTRATION_COOLDOWN_MS = 30_000L // 30 seconds minimum between retries
    }

    /**
     * Gets the currently cached token, or null if none exists.
     */
    fun getCachedToken(): String? {
        return prefs.getString(KEY_TOKEN, null)
    }

    /**
     * Saves a new token locally and marks it as unregistered.
     * Called by MyFirebaseMessagingService.onNewToken().
     */
    fun onTokenRefreshed(token: String) {
        prefs.edit()
            .putString(KEY_TOKEN, token)
            .putBoolean(KEY_TOKEN_REGISTERED, false)
            .apply()
        Log.d(TAG, "Token refreshed and saved locally")
    }

    /**
     * Retrieves the current FCM token (from cache or Firebase)
     * and registers it with the server.
     *
     * This is the primary entry point — called after login,
     * on app startup when logged in, and after token refresh.
     */
    fun ensureTokenRegistered() {
        scope.launch {
            try {
                // Check cooldown to avoid hammering the server
                val lastAttempt = prefs.getLong(KEY_LAST_REGISTRATION_ATTEMPT, 0)
                val isRegistered = prefs.getBoolean(KEY_TOKEN_REGISTERED, false)
                if (isRegistered && System.currentTimeMillis() - lastAttempt < REGISTRATION_COOLDOWN_MS) {
                    Log.d(TAG, "Token already registered recently, skipping")
                    return@launch
                }

                // Get token — try cache first, then Firebase
                var token = getCachedToken()
                if (token.isNullOrEmpty()) {
                    token = FirebaseMessaging.getInstance().token.await()
                    prefs.edit().putString(KEY_TOKEN, token).apply()
                }

                if (token.isNullOrEmpty()) {
                    Log.e(TAG, "Failed to get FCM token")
                    return@launch
                }

                registerTokenWithServer(token)
            } catch (e: Exception) {
                Log.e(TAG, "ensureTokenRegistered failed: ${e.message}")
                // Schedule WorkManager retry
                FcmTokenRegistrationWorker.enqueue(context)
            }
        }
    }

    /**
     * Registers the given token with the backend server.
     * On success, marks the token as registered.
     * On failure, schedules a WorkManager retry.
     */
    suspend fun registerTokenWithServer(token: String): Boolean {
        return try {
            prefs.edit().putLong(KEY_LAST_REGISTRATION_ATTEMPT, System.currentTimeMillis()).apply()

            val androidId = Settings.Secure.getString(
                context.contentResolver,
                Settings.Secure.ANDROID_ID
            ) ?: "unknown"

            val request = mapOf(
                "token" to token,
                "platform" to "android",
                "device_id" to androidId
            )

            val response = apiService.registerFcmToken(request)
            if (response.isSuccessful) {
                prefs.edit().putBoolean(KEY_TOKEN_REGISTERED, true).apply()
                Log.d(TAG, "Token registered with server successfully")
                true
            } else {
                Log.e(TAG, "Token registration failed: HTTP ${response.code()}")
                // Schedule retry via WorkManager
                FcmTokenRegistrationWorker.enqueue(context)
                false
            }
        } catch (e: Exception) {
            Log.e(TAG, "Token registration error: ${e.message}")
            // Schedule retry via WorkManager
            FcmTokenRegistrationWorker.enqueue(context)
            false
        }
    }

    /**
     * Unregisters the FCM token from the backend on logout.
     * This prevents the old user from receiving notifications
     * for the logged-out account.
     */
    suspend fun unregisterToken(): Boolean {
        return try {
            val token = getCachedToken()
            if (token.isNullOrEmpty()) {
                Log.d(TAG, "No token to unregister")
                return true
            }

            val androidId = Settings.Secure.getString(
                context.contentResolver,
                Settings.Secure.ANDROID_ID
            ) ?: "unknown"

            val request = mapOf(
                "token" to token,
                "platform" to "android",
                "device_id" to androidId
            )

            val response = apiService.unregisterFcmToken(request)
            if (response.isSuccessful) {
                // Mark as unregistered but don't clear the token
                // (it's still valid, just not mapped to a user)
                prefs.edit()
                    .putBoolean(KEY_TOKEN_REGISTERED, false)
                    .apply()
                Log.d(TAG, "Token unregistered from server")
                true
            } else {
                Log.e(TAG, "Token unregistration failed: HTTP ${response.code()}")
                false
            }
        } catch (e: Exception) {
            Log.e(TAG, "Token unregistration error: ${e.message}")
            false
        }
    }

    /**
     * Clears all local token data. Called on full logout/account switch.
     */
    fun clearLocalData() {
        prefs.edit().clear().apply()
    }

    /**
     * Marks token as needing re-registration.
     * Useful after account switching.
     */
    fun markTokenDirty() {
        prefs.edit().putBoolean(KEY_TOKEN_REGISTERED, false).apply()
    }

    /**
     * Whether the token has been successfully registered with the server.
     */
    fun isTokenRegistered(): Boolean {
        return prefs.getBoolean(KEY_TOKEN_REGISTERED, false)
    }
}
