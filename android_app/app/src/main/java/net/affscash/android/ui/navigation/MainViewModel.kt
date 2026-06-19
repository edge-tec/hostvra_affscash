package net.affscash.android.ui.navigation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.local.UserManager
import net.affscash.android.data.repository.AffiliateRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import kotlinx.coroutines.tasks.await
import javax.inject.Inject
import com.google.firebase.messaging.FirebaseMessaging
import net.affscash.android.data.network.ApiService

@HiltViewModel
class MainViewModel @Inject constructor(
    private val userManager: UserManager,
    private val affiliateRepository: AffiliateRepository,
    private val apiService: ApiService
) : ViewModel() {

    private val _isImpersonating = MutableStateFlow(userManager.isImpersonating())
    val isImpersonating: StateFlow<Boolean> = _isImpersonating

    init {
        if (userManager.getRole() != null) {
            registerFcmToken()
        }
    }

    private fun registerFcmToken() {
        viewModelScope.launch {
            try {
                val token = FirebaseMessaging.getInstance().token.await()
                val request = mapOf("token" to token, "device_id" to "android_device")
                apiService.registerFcmToken(request)
            } catch (e: Exception) {
                // Ignore failures to register token
            }
        }
    }

    fun refreshImpersonatingState() {
        _isImpersonating.value = userManager.isImpersonating()
    }

    fun stopImpersonating(onSuccess: (String?) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            affiliateRepository.stopImpersonate()
                .onSuccess { response ->
                    response.role?.let {
                        userManager.saveUser(it, response.user?.email ?: "", response.user?.firstName + " " + response.user?.lastName)
                        userManager.saveIsImpersonating(false)
                        _isImpersonating.value = false
                    }
                    onSuccess(response.role)
                }
                .onFailure {
                    onError(it.message ?: "Failed to stop impersonating")
                }
        }
    }
}
