package net.affscash.android.ui.auth

import androidx.fragment.app.FragmentActivity
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import net.affscash.android.data.local.SecureStorageManager
import net.affscash.android.data.model.User
import net.affscash.android.data.repository.AuthRepository
import net.affscash.android.service.FcmTokenManager
import net.affscash.android.util.BiometricAuthManager
import net.affscash.android.util.BiometricCapability
import javax.inject.Inject

sealed class AuthState {
    object Idle : AuthState()
    object Loading : AuthState()
    data class Success(val user: User) : AuthState()
    data class Error(val message: String) : AuthState()
}

@HiltViewModel
class AuthViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val fcmTokenManager: FcmTokenManager,
    private val secureStorageManager: SecureStorageManager,
    private val biometricAuthManager: BiometricAuthManager
) : ViewModel() {

    private val _authState = MutableStateFlow<AuthState>(AuthState.Idle)
    val authState: StateFlow<AuthState> = _authState.asStateFlow()

    private val _rememberMe = MutableStateFlow(secureStorageManager.isRememberMeEnabled)
    val rememberMe: StateFlow<Boolean> = _rememberMe.asStateFlow()

    private val _biometricCapability = MutableStateFlow<BiometricCapability>(
        biometricAuthManager.checkBiometricCapability()
    )
    val biometricCapability: StateFlow<BiometricCapability> = _biometricCapability.asStateFlow()

    private val _isBiometricEnabled = MutableStateFlow(secureStorageManager.isBiometricEnabled)
    val isBiometricEnabled: StateFlow<Boolean> = _isBiometricEnabled.asStateFlow()

    private val _showBiometricPromptDialog = MutableStateFlow(false)
    val showBiometricPromptDialog: StateFlow<Boolean> = _showBiometricPromptDialog.asStateFlow()

    fun setRememberMe(enabled: Boolean) {
        _rememberMe.value = enabled
        secureStorageManager.isRememberMeEnabled = enabled
    }

    fun refreshBiometricState() {
        _biometricCapability.value = biometricAuthManager.checkBiometricCapability()
        _isBiometricEnabled.value = secureStorageManager.isBiometricEnabled
    }

    fun login(email: String, password: String) {
        if (email.isBlank() || password.isBlank()) {
            _authState.value = AuthState.Error("Please enter email and password")
            return
        }

        viewModelScope.launch {
            _authState.value = AuthState.Loading
            val remember = _rememberMe.value
            val result = authRepository.login(email, password, rememberMe = remember)
            result.onSuccess { response ->
                response.user?.let { user ->
                    _authState.value = AuthState.Success(user)
                    fcmTokenManager.markTokenDirty()
                    fcmTokenManager.ensureTokenRegistered()

                    val cap = biometricAuthManager.checkBiometricCapability()
                    if (remember && cap.isAvailable && !secureStorageManager.isBiometricEnabled && !secureStorageManager.isBiometricPromptAsked) {
                        _showBiometricPromptDialog.value = true
                    }
                } ?: run {
                    _authState.value = AuthState.Error("Invalid response from server")
                }
            }.onFailure {
                _authState.value = AuthState.Error(it.message ?: "An unknown error occurred")
            }
        }
    }

    fun loginWithBiometrics(activity: FragmentActivity) {
        val cap = biometricAuthManager.checkBiometricCapability()
        if (!cap.isAvailable) {
            _authState.value = AuthState.Error(cap.statusMessage)
            return
        }

        biometricAuthManager.authenticate(
            activity = activity,
            title = "Biometric Sign In",
            subtitle = "Log in securely using Biometrics",
            description = "Confirm your biometric credential to sign in",
            onSuccess = {
                val savedUser = authRepository.getSavedUser()
                if (savedUser != null) {
                    _authState.value = AuthState.Success(savedUser)
                    fcmTokenManager.markTokenDirty()
                    fcmTokenManager.ensureTokenRegistered()
                } else {
                    _authState.value = AuthState.Error("No saved session found. Please log in with email and password.")
                }
            },
            onError = { errorMsg ->
                _authState.value = AuthState.Error(errorMsg)
            },
            onCancel = {
                // Stay on login screen
            }
        )
    }

    fun enableBiometricAuth(activity: FragmentActivity, onResult: (Boolean) -> Unit = {}) {
        biometricAuthManager.authenticate(
            activity = activity,
            title = "Enable Biometric Login",
            subtitle = "Verify your identity to enable biometric sign-in",
            description = "Confirm fingerprint or face unlock for future logins",
            onSuccess = {
                secureStorageManager.isBiometricEnabled = true
                secureStorageManager.isBiometricPromptAsked = true
                _isBiometricEnabled.value = true
                _showBiometricPromptDialog.value = false
                onResult(true)
            },
            onError = { errorMsg ->
                _authState.value = AuthState.Error(errorMsg)
                onResult(false)
            },
            onCancel = {
                secureStorageManager.isBiometricPromptAsked = true
                _showBiometricPromptDialog.value = false
                onResult(false)
            }
        )
    }

    fun dismissBiometricSetupDialog() {
        secureStorageManager.isBiometricPromptAsked = true
        _showBiometricPromptDialog.value = false
    }

    fun logout() {
        viewModelScope.launch {
            fcmTokenManager.unregisterToken()
            fcmTokenManager.clearLocalData()
            authRepository.logout()
            _authState.value = AuthState.Idle
            _isBiometricEnabled.value = false
        }
    }

    fun clearError() {
        if (_authState.value is AuthState.Error) {
            _authState.value = AuthState.Idle
        }
    }
}
