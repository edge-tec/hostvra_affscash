package net.affscash.android.ui.navigation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.local.UserManager
import net.affscash.android.data.repository.AffiliateRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class MainViewModel @Inject constructor(
    private val userManager: UserManager,
    private val affiliateRepository: AffiliateRepository
) : ViewModel() {

    private val _isImpersonating = MutableStateFlow(userManager.isImpersonating())
    val isImpersonating: StateFlow<Boolean> = _isImpersonating

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
