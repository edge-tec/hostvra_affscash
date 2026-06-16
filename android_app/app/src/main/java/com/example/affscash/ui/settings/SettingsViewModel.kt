package com.example.affscash.ui.settings

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.local.UserManager
import com.example.affscash.data.repository.AffiliateRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class SettingsViewModel @Inject constructor(
    private val affiliateRepository: AffiliateRepository,
    private val userManager: UserManager
) : ViewModel() {

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
