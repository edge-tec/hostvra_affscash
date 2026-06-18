package net.affscash.android.ui.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import net.affscash.android.data.repository.AuthRepository
import javax.inject.Inject

enum class ForgotPasswordStep {
    EMAIL_INPUT,
    OTP_INPUT,
    NEW_PASSWORD_INPUT,
    SUCCESS
}

@HiltViewModel
class ForgotPasswordViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {

    private val _currentStep = MutableStateFlow(ForgotPasswordStep.EMAIL_INPUT)
    val currentStep: StateFlow<ForgotPasswordStep> = _currentStep

    private val _isLoading = MutableStateFlow(false)
    val isLoading: StateFlow<Boolean> = _isLoading

    private val _error = MutableStateFlow<String?>(null)
    val error: StateFlow<String?> = _error

    private var currentEmail: String = ""
    private var resetToken: String = ""

    fun requestOtp(email: String) {
        if (email.isBlank()) {
            _error.value = "Email cannot be empty"
            return
        }
        _error.value = null
        _isLoading.value = true

        viewModelScope.launch {
            val result = authRepository.forgotPassword(email)
            _isLoading.value = false
            result.onSuccess { response ->
                if (response.success) {
                    currentEmail = email
                    _currentStep.value = ForgotPasswordStep.OTP_INPUT
                } else {
                    _error.value = response.error ?: "Failed to send OTP"
                }
            }.onFailure {
                _error.value = it.message ?: "An unknown error occurred"
            }
        }
    }

    fun verifyOtp(otp: String) {
        if (otp.isBlank()) {
            _error.value = "OTP cannot be empty"
            return
        }
        _error.value = null
        _isLoading.value = true

        viewModelScope.launch {
            val result = authRepository.verifyOtp(currentEmail, otp)
            _isLoading.value = false
            result.onSuccess { response ->
                if (response.success && response.token != null) {
                    resetToken = response.token
                    _currentStep.value = ForgotPasswordStep.NEW_PASSWORD_INPUT
                } else {
                    _error.value = response.error ?: "Invalid OTP"
                }
            }.onFailure {
                _error.value = it.message ?: "An unknown error occurred"
            }
        }
    }

    fun resetPassword(password: String, confirmPassword: String) {
        if (password.length < 8) {
            _error.value = "Password must be at least 8 characters"
            return
        }
        if (password != confirmPassword) {
            _error.value = "Passwords do not match"
            return
        }
        _error.value = null
        _isLoading.value = true

        viewModelScope.launch {
            val result = authRepository.resetPassword(currentEmail, resetToken, password)
            _isLoading.value = false
            result.onSuccess { response ->
                if (response.success) {
                    _currentStep.value = ForgotPasswordStep.SUCCESS
                } else {
                    _error.value = response.error ?: "Failed to reset password"
                }
            }.onFailure {
                _error.value = it.message ?: "An unknown error occurred"
            }
        }
    }

    fun clearError() {
        _error.value = null
    }
}
