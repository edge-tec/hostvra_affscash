package net.affscash.android.ui.navigation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.local.UserManager
import net.affscash.android.data.repository.AffiliateRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import net.affscash.android.service.FcmTokenManager
import javax.inject.Inject

@HiltViewModel
class MainViewModel @Inject constructor(
    private val userManager: UserManager,
    private val affiliateRepository: AffiliateRepository,
    private val fcmTokenManager: FcmTokenManager
) : ViewModel() {

    private val _isImpersonating = MutableStateFlow(userManager.isImpersonating())
    val isImpersonating: StateFlow<Boolean> = _isImpersonating

    init {
        if (userManager.getRole() != null) {
            // Ensure token is registered when app starts
            fcmTokenManager.ensureTokenRegistered()
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
                        // Re-register token with the real account
                        fcmTokenManager.markTokenDirty()
                        fcmTokenManager.ensureTokenRegistered()
                    }
                    onSuccess(response.role)
                }
                .onFailure {
                    onError(it.message ?: "Failed to stop impersonating")
                }
        }
    }
}

