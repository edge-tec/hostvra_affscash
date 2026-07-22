package net.affscash.android.ui.settings

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.local.UserManager
import net.affscash.android.data.model.*
import net.affscash.android.data.repository.AffiliateRepository
import net.affscash.android.data.repository.SettingsRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

import androidx.fragment.app.FragmentActivity
import kotlinx.coroutines.flow.asStateFlow
import net.affscash.android.data.local.SecureStorageManager
import net.affscash.android.util.BiometricAuthManager
import net.affscash.android.util.BiometricCapability

sealed class SettingsUiState {
    object Loading : SettingsUiState()
    data class Success(
        val profile: ProfileInfo?,
        val payment: PaymentInfo?,
        val manager: ManagerInfo?,
        val paymentMethods: List<String>,
        val twoFactorEnabled: Boolean,
        val deleteRequest: DeleteRequestInfo?,
        val globalPostback: GlobalPostbackInfo?,
        val isImpersonating: Boolean = false
    ) : SettingsUiState()
    data class Error(val message: String) : SettingsUiState()
}

@HiltViewModel
class SettingsViewModel @Inject constructor(
    private val affiliateRepository: AffiliateRepository,
    private val settingsRepository: SettingsRepository,
    private val userManager: UserManager,
    private val secureStorageManager: SecureStorageManager,
    private val biometricAuthManager: BiometricAuthManager
) : ViewModel() {

    private val _uiState = MutableStateFlow<SettingsUiState>(SettingsUiState.Loading)
    val uiState: StateFlow<SettingsUiState> = _uiState

    private val _rememberMe = MutableStateFlow(secureStorageManager.isRememberMeEnabled)
    val rememberMe: StateFlow<Boolean> = _rememberMe.asStateFlow()

    private val _biometricEnabled = MutableStateFlow(secureStorageManager.isBiometricEnabled)
    val biometricEnabled: StateFlow<Boolean> = _biometricEnabled.asStateFlow()

    private val _autoLoginEnabled = MutableStateFlow(secureStorageManager.isAutoLoginEnabled)
    val autoLoginEnabled: StateFlow<Boolean> = _autoLoginEnabled.asStateFlow()

    val biometricCap: BiometricCapability get() = biometricAuthManager.checkBiometricCapability()

    fun toggleRememberMe(enabled: Boolean) {
        secureStorageManager.isRememberMeEnabled = enabled
        _rememberMe.value = enabled
    }

    fun toggleAutoLogin(enabled: Boolean) {
        secureStorageManager.isAutoLoginEnabled = enabled
        _autoLoginEnabled.value = enabled
    }

    fun toggleBiometric(activity: FragmentActivity, enable: Boolean, onComplete: (String) -> Unit) {
        if (enable) {
            biometricAuthManager.authenticate(
                activity = activity,
                title = "Enable Biometrics",
                subtitle = "Authenticate to enable biometric login",
                onSuccess = {
                    secureStorageManager.isBiometricEnabled = true
                    _biometricEnabled.value = true
                    onComplete("Biometric login enabled successfully.")
                },
                onError = { err -> onComplete("Failed: $err") }
            )
        } else {
            secureStorageManager.clearBiometricData()
            _biometricEnabled.value = false
            onComplete("Biometric login disabled and secure keys removed.")
        }
    }

    fun disableBiometrics(onComplete: (String) -> Unit) {
        secureStorageManager.clearBiometricData()
        _biometricEnabled.value = false
        onComplete("Biometric login disabled.")
    }

    fun removeSavedSession(onComplete: (String) -> Unit) {
        secureStorageManager.clearSession()
        _rememberMe.value = false
        _biometricEnabled.value = false
        _autoLoginEnabled.value = false
        onComplete("Saved session and security credentials removed.")
    }

    fun logoutAllDevices(onComplete: (String) -> Unit) {
        viewModelScope.launch {
            removeSavedSession { }
            userManager.triggerUnauth()
            onComplete("Logged out from all devices.")
        }
    }

    init {
        loadSettings()
    }

    fun loadSettings() {

        viewModelScope.launch {
            _uiState.value = SettingsUiState.Loading
            settingsRepository.getSettings()
                .onSuccess { res ->
                    _uiState.value = SettingsUiState.Success(
                        profile = res.profile,
                        payment = res.payment,
                        manager = res.manager,
                        paymentMethods = res.paymentMethods,
                        twoFactorEnabled = res.twoFactorEnabled,
                        deleteRequest = res.deleteRequest,
                        globalPostback = res.globalPostback,
                        isImpersonating = userManager.isImpersonating()
                    )
                }
                .onFailure {
                    _uiState.value = SettingsUiState.Error(it.message ?: "Failed to load settings")
                }
        }
    }

    fun updateProfile(request: UpdateProfileRequest, onSuccess: (String) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            settingsRepository.updateProfile(request)
                .onSuccess { onSuccess(it.message ?: "Profile updated") }
                .onFailure { onError(it.message ?: "Update failed") }
        }
    }

    fun updateSecurity(request: UpdateSecurityRequest, onSuccess: (String) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            settingsRepository.updateSecurity(request)
                .onSuccess { onSuccess(it.message ?: "Password updated") }
                .onFailure { onError(it.message ?: "Update failed") }
        }
    }

    fun updatePayment(request: UpdatePaymentRequest, onSuccess: (String) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            settingsRepository.updatePayment(request)
                .onSuccess { onSuccess(it.message ?: "Payment updated") }
                .onFailure { onError(it.message ?: "Update failed") }
        }
    }

    fun start2fa(onSuccess: (String, String) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            settingsRepository.start2fa()
                .onSuccess { res ->
                    if (res.secret != null && res.qrUrl != null) {
                        onSuccess(res.secret, res.qrUrl)
                    } else {
                        onError("Invalid 2FA start response")
                    }
                }
                .onFailure { onError(it.message ?: "Failed to start 2FA") }
        }
    }

    fun verify2fa(request: TwoFactorVerifyRequest, onSuccess: (String) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            settingsRepository.verify2fa(request)
                .onSuccess {
                    onSuccess(it.message ?: "2FA enabled")
                    loadSettings() // refresh state
                }
                .onFailure { onError(it.message ?: "Verification failed") }
        }
    }

    fun disable2fa(request: TwoFactorDisableRequest, onSuccess: (String) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            settingsRepository.disable2fa(request)
                .onSuccess {
                    onSuccess(it.message ?: "2FA disabled")
                    loadSettings() // refresh state
                }
                .onFailure { onError(it.message ?: "Disable failed") }
        }
    }

    fun stopImpersonating(onSuccess: (String?) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            affiliateRepository.stopImpersonate()
                .onSuccess { response ->
                    response.role?.let {
                        userManager.saveUser(it, response.user?.email ?: "", response.user?.firstName + " " + response.user?.lastName)
                        userManager.saveIsImpersonating(false)
                    }
                    onSuccess(response.role)
                }
                .onFailure {
                    onError(it.message ?: "Failed to stop impersonating")
                }
        }
    }

    fun requestAccountDelete(request: DeleteAccountRequest, onSuccess: (String) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            settingsRepository.requestAccountDelete(request)
                .onSuccess {
                    onSuccess(it.message ?: "Account deletion requested")
                    loadSettings() // refresh state to show pending status
                }
                .onFailure { onError(it.message ?: "Request failed") }
        }
    }

    fun updateGlobalPostback(request: UpdateGlobalPostbackRequest, onSuccess: (String) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            settingsRepository.updateGlobalPostback(request)
                .onSuccess {
                    onSuccess(it.message ?: "Global postback updated")
                    loadSettings() // refresh state
                }
                .onFailure { onError(it.message ?: "Update failed") }
        }
    }
}
