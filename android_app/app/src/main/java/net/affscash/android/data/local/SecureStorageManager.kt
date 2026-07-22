package net.affscash.android.data.local

import android.content.Context
import android.content.SharedPreferences
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class SecureStorageManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val masterKey: MasterKey by lazy {
        MasterKey.Builder(context)
            .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
            .build()
    }

    private val encryptedPrefs: SharedPreferences by lazy {
        try {
            EncryptedSharedPreferences.create(
                context,
                "secure_user_prefs",
                masterKey,
                EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
                EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
            )
        } catch (e: Exception) {
            context.deleteSharedPreferences("secure_user_prefs")
            EncryptedSharedPreferences.create(
                context,
                "secure_user_prefs",
                masterKey,
                EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
                EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
            )
        }
    }

    companion object {
        private const val KEY_REMEMBER_ME = "key_remember_me"
        private const val KEY_BIOMETRIC_ENABLED = "key_biometric_enabled"
        private const val KEY_AUTO_LOGIN_ENABLED = "key_auto_login_enabled"
        private const val KEY_AUTH_TOKEN = "key_auth_token"
        private const val KEY_REFRESH_TOKEN = "key_refresh_token"
        private const val KEY_USER_ID = "key_user_id"
        private const val KEY_USER_ROLE = "key_user_role"
        private const val KEY_USER_EMAIL = "key_user_email"
        private const val KEY_USER_NAME = "key_user_name"
        private const val KEY_LOGIN_STATE = "key_login_state"
        private const val KEY_BIOMETRIC_ASKED = "key_biometric_asked"
    }

    var isRememberMeEnabled: Boolean
        get() = encryptedPrefs.getBoolean(KEY_REMEMBER_ME, false)
        set(value) = encryptedPrefs.edit().putBoolean(KEY_REMEMBER_ME, value).apply()

    var isBiometricEnabled: Boolean
        get() = encryptedPrefs.getBoolean(KEY_BIOMETRIC_ENABLED, false)
        set(value) = encryptedPrefs.edit().putBoolean(KEY_BIOMETRIC_ENABLED, value).apply()

    var isAutoLoginEnabled: Boolean
        get() = encryptedPrefs.getBoolean(KEY_AUTO_LOGIN_ENABLED, true)
        set(value) = encryptedPrefs.edit().putBoolean(KEY_AUTO_LOGIN_ENABLED, value).apply()

    var isBiometricPromptAsked: Boolean
        get() = encryptedPrefs.getBoolean(KEY_BIOMETRIC_ASKED, false)
        set(value) = encryptedPrefs.edit().putBoolean(KEY_BIOMETRIC_ASKED, value).apply()

    fun saveAuthSession(
        role: String,
        email: String,
        name: String,
        token: String? = null,
        refreshToken: String? = null,
        userId: String? = null
    ) {
        encryptedPrefs.edit()
            .putString(KEY_USER_ROLE, role)
            .putString(KEY_USER_EMAIL, email)
            .putString(KEY_USER_NAME, name)
            .putString(KEY_AUTH_TOKEN, token ?: "")
            .putString(KEY_REFRESH_TOKEN, refreshToken ?: "")
            .putString(KEY_USER_ID, userId ?: "")
            .putBoolean(KEY_LOGIN_STATE, true)
            .apply()
    }

    fun getAuthToken(): String? = encryptedPrefs.getString(KEY_AUTH_TOKEN, null)?.takeIf { it.isNotBlank() }
    fun getRefreshToken(): String? = encryptedPrefs.getString(KEY_REFRESH_TOKEN, null)?.takeIf { it.isNotBlank() }
    fun getUserRole(): String? = encryptedPrefs.getString(KEY_USER_ROLE, null)
    fun getUserEmail(): String? = encryptedPrefs.getString(KEY_USER_EMAIL, null)
    fun getUserName(): String? = encryptedPrefs.getString(KEY_USER_NAME, null)
    fun isLoggedIn(): Boolean = encryptedPrefs.getBoolean(KEY_LOGIN_STATE, false)

    fun hasValidSession(): Boolean {
        return isLoggedIn() && isRememberMeEnabled && !getUserRole().isNullOrEmpty()
    }

    fun clearBiometricData() {
        encryptedPrefs.edit()
            .remove(KEY_BIOMETRIC_ENABLED)
            .remove(KEY_BIOMETRIC_ASKED)
            .apply()
    }

    fun clearSession() {
        encryptedPrefs.edit().clear().apply()
    }
}
