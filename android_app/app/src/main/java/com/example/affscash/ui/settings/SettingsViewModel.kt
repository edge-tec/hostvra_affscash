package com.example.affscash.ui.settings

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.local.UserManager
import com.example.affscash.data.model.*
import com.example.affscash.data.repository.AffiliateRepository
import com.example.affscash.data.repository.SettingsRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class SettingsUiState {
    object Loading : SettingsUiState()
    data class Success(
        val profile: ProfileInfo?,
        val payment: PaymentInfo?,
        val manager: ManagerInfo?,
        val paymentMethods: List<String>,
        val twoFactorEnabled: Boolean
    ) : SettingsUiState()
    data class Error(val message: String) : SettingsUiState()
}

@HiltViewModel
class SettingsViewModel @Inject constructor(
    private val affiliateRepository: AffiliateRepository,
    private val settingsRepository: SettingsRepository,
    private val userManager: UserManager
) : ViewModel() {

    private val _uiState = MutableStateFlow<SettingsUiState>(SettingsUiState.Loading)
    val uiState: StateFlow<SettingsUiState> = _uiState

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
                        twoFactorEnabled = res.twoFactorEnabled
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
                    }
                    onSuccess(response.role)
                }
                .onFailure {
                    onError(it.message ?: "Failed to stop impersonating")
                }
        }
    }
}
