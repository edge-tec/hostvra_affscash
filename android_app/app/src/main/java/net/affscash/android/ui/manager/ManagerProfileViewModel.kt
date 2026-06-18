package net.affscash.android.ui.manager

import android.content.Context
import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.ManagerProfileResponse
import net.affscash.android.data.repository.ManagerProfileRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import java.io.FileOutputStream
import javax.inject.Inject

sealed class ManagerProfileUiState {
    object Loading : ManagerProfileUiState()
    data class Success(val data: ManagerProfileResponse) : ManagerProfileUiState()
    data class Error(val message: String) : ManagerProfileUiState()
}

@HiltViewModel
class ManagerProfileViewModel @Inject constructor(
    private val repository: ManagerProfileRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<ManagerProfileUiState>(ManagerProfileUiState.Loading)
    val uiState: StateFlow<ManagerProfileUiState> = _uiState.asStateFlow()

    private val _selectedTab = MutableStateFlow(0)
    val selectedTab: StateFlow<Int> = _selectedTab.asStateFlow()

    private val _isSubmitting = MutableStateFlow(false)
    val isSubmitting: StateFlow<Boolean> = _isSubmitting.asStateFlow()

    private val _actionMessage = MutableStateFlow<String?>(null)
    val actionMessage: StateFlow<String?> = _actionMessage.asStateFlow()
    
    private val _qrUrl = MutableStateFlow<String?>(null)
    val qrUrl: StateFlow<String?> = _qrUrl.asStateFlow()
    
    private val _twoFaSecret = MutableStateFlow<String?>(null)
    val twoFaSecret: StateFlow<String?> = _twoFaSecret.asStateFlow()

    init {
        loadProfile()
    }

    fun loadProfile() {
        viewModelScope.launch {
            _uiState.value = ManagerProfileUiState.Loading
            val result = repository.getManagerProfile()
            result.onSuccess {
                _uiState.value = ManagerProfileUiState.Success(it)
            }.onFailure {
                _uiState.value = ManagerProfileUiState.Error(it.message ?: "Failed to load profile")
            }
        }
    }

    fun setTab(index: Int) {
        _selectedTab.value = index
    }

    fun clearActionMessage() {
        _actionMessage.value = null
    }

    fun updateProfile(
        context: Context,
        firstName: String, lastName: String, email: String, company: String, phone: String,
        skype: String, telegram: String, discord: String,
        profilePicUri: Uri?
    ) {
        viewModelScope.launch {
            _isSubmitting.value = true
            try {
                var imagePart: MultipartBody.Part? = null
                profilePicUri?.let { uri ->
                    val file = uriToFile(context, uri)
                    if (file != null) {
                        val reqFile = file.asRequestBody("image/*".toMediaTypeOrNull())
                        imagePart = MultipartBody.Part.createFormData("profile_pic", file.name, reqFile)
                    }
                }

                val fName = firstName.toRequestBody("text/plain".toMediaTypeOrNull())
                val lName = lastName.toRequestBody("text/plain".toMediaTypeOrNull())
                val mail = email.toRequestBody("text/plain".toMediaTypeOrNull())
                val comp = company.toRequestBody("text/plain".toMediaTypeOrNull())
                val ph = phone.toRequestBody("text/plain".toMediaTypeOrNull())
                val sk = skype.toRequestBody("text/plain".toMediaTypeOrNull())
                val tg = telegram.toRequestBody("text/plain".toMediaTypeOrNull())
                val dc = discord.toRequestBody("text/plain".toMediaTypeOrNull())

                val result = repository.updateManagerProfile(fName, lName, mail, comp, ph, sk, tg, dc, imagePart)
                result.onSuccess {
                    _actionMessage.value = it.message ?: "Profile updated"
                    loadProfile()
                }.onFailure {
                    _actionMessage.value = "Error: ${it.message}"
                }
            } catch (e: Exception) {
                _actionMessage.value = "Error: ${e.message}"
            } finally {
                _isSubmitting.value = false
            }
        }
    }

    fun updateSecurity(currentPass: String, newPass: String, confirmPass: String) {
        if (newPass != confirmPass) {
            _actionMessage.value = "Passwords do not match"
            return
        }
        viewModelScope.launch {
            _isSubmitting.value = true
            val req = mapOf("current_password" to currentPass, "new_password" to newPass, "confirm_password" to confirmPass)
            val result = repository.updateSecurity(req)
            result.onSuccess { _actionMessage.value = it.message ?: "Password updated" }
                .onFailure { _actionMessage.value = "Error: ${it.message}" }
            _isSubmitting.value = false
        }
    }

    fun updatePayment(method: String, details: String) {
        viewModelScope.launch {
            _isSubmitting.value = true
            val req = mapOf("payment_method" to method, "payment_details" to details)
            val result = repository.updatePayment(req)
            result.onSuccess { _actionMessage.value = it.message ?: "Payment updated" }
                .onFailure { _actionMessage.value = "Error: ${it.message}" }
            _isSubmitting.value = false
        }
    }

    fun start2fa() {
        viewModelScope.launch {
            _isSubmitting.value = true
            val result = repository.start2fa()
            result.onSuccess {
                _qrUrl.value = it.qrUrl
                _twoFaSecret.value = it.secret
            }.onFailure {
                _actionMessage.value = "Error: ${it.message}"
            }
            _isSubmitting.value = false
        }
    }

    fun verify2fa(code: String) {
        val secret = _twoFaSecret.value ?: return
        viewModelScope.launch {
            _isSubmitting.value = true
            val req = mapOf("code" to code, "secret" to secret)
            val result = repository.verify2fa(req)
            result.onSuccess {
                _actionMessage.value = it.message ?: "2FA Enabled"
                _qrUrl.value = null
                _twoFaSecret.value = null
                loadProfile()
            }.onFailure {
                _actionMessage.value = "Error: ${it.message}"
            }
            _isSubmitting.value = false
        }
    }

    fun disable2fa(password: String, code: String) {
        viewModelScope.launch {
            _isSubmitting.value = true
            val req = mapOf("password" to password, "code" to code)
            val result = repository.disable2fa(req)
            result.onSuccess {
                _actionMessage.value = it.message ?: "2FA Disabled"
                loadProfile()
            }.onFailure {
                _actionMessage.value = "Error: ${it.message}"
            }
            _isSubmitting.value = false
        }
    }

    private fun uriToFile(context: Context, uri: Uri): File? {
        return try {
            val inputStream = context.contentResolver.openInputStream(uri) ?: return null
            val tempFile = File.createTempFile("upload", ".jpg", context.cacheDir)
            val outputStream = FileOutputStream(tempFile)
            inputStream.copyTo(outputStream)
            inputStream.close()
            outputStream.close()
            tempFile
        } catch (e: Exception) {
            e.printStackTrace()
            null
        }
    }
}
