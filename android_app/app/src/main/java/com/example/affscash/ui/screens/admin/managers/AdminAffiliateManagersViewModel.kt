package com.example.affscash.ui.screens.admin.managers

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.AdminAffiliateManagerRow
import com.example.affscash.data.repository.AdminAffiliateManagerRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class AdminAffiliateManagersUiState(
    val isLoading: Boolean = false,
    val managers: List<AdminAffiliateManagerRow> = emptyList(),
    val error: String? = null,
    val isActionLoading: Boolean = false
)

@HiltViewModel
class AdminAffiliateManagersViewModel @Inject constructor(private val repository: AdminAffiliateManagerRepository) : ViewModel() {
    private val _uiState = MutableStateFlow(AdminAffiliateManagersUiState())
    val uiState: StateFlow<AdminAffiliateManagersUiState> = _uiState.asStateFlow()

    init {
        loadManagers()
    }

    fun loadManagers() {
        _uiState.update { it.copy(isLoading = true, error = null) }
        viewModelScope.launch {
            val result = repository.getManagers()
            if (result.isSuccess) {
                _uiState.update { it.copy(isLoading = false, managers = result.getOrNull() ?: emptyList()) }
            } else {
                _uiState.update { it.copy(isLoading = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    fun deleteManager(mgrId: Int) {
        _uiState.update { it.copy(isActionLoading = true) }
        viewModelScope.launch {
            val result = repository.deleteManager(mgrId)
            if (result.isSuccess) {
                _uiState.update { it.copy(isActionLoading = false) }
                loadManagers()
            } else {
                _uiState.update { it.copy(isActionLoading = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    fun impersonateManager(userId: Int, onLoginSuccess: (String, com.example.affscash.data.model.User) -> Unit) {
        viewModelScope.launch {
            _uiState.update { it.copy(isActionLoading = true, error = null) }
            val result = repository.impersonateManager(userId)
            result.onSuccess { response ->
                _uiState.update { it.copy(isActionLoading = false) }
                if (response.user != null) {
                    onLoginSuccess(response.user.role, response.user)
                }
            }.onFailure { error ->
                _uiState.update { it.copy(isActionLoading = false, error = error.message) }
            }
        }
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }
}
