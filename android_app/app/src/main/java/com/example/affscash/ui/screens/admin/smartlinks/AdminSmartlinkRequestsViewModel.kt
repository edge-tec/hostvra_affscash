package com.example.affscash.ui.screens.admin.smartlinks

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.AdminSmartlinkActionRequest
import com.example.affscash.data.model.AdminSmartlinkRequest
import com.example.affscash.data.repository.AdminSmartlinkRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject

@HiltViewModel
class AdminSmartlinkRequestsViewModel @Inject constructor(
    private val repository: AdminSmartlinkRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminSmartlinkRequestsUiState>(AdminSmartlinkRequestsUiState.Loading)
    val uiState: StateFlow<AdminSmartlinkRequestsUiState> = _uiState

    init {
        loadRequests()
    }

    fun loadRequests() {
        viewModelScope.launch {
            _uiState.value = AdminSmartlinkRequestsUiState.Loading
            val result = repository.getAdminSmartlinkRequests()
            result.onSuccess { wrapper ->
                if (wrapper.status == "success" && wrapper.data != null) {
                    _uiState.value = AdminSmartlinkRequestsUiState.Success(wrapper.data.requests)
                } else {
                    _uiState.value = AdminSmartlinkRequestsUiState.Error("Failed to load requests")
                }
            }.onFailure { e ->
                _uiState.value = AdminSmartlinkRequestsUiState.Error(e.message ?: "Unknown error")
            }
        }
    }

    fun reviewRequest(requestId: Int, decision: String, note: String = "") {
        viewModelScope.launch {
            val result = repository.submitAdminSmartlinkAction(
                AdminSmartlinkActionRequest(
                    action = "review_request",
                    requestId = requestId,
                    decision = decision,
                    adminNote = note
                )
            )
            result.onSuccess {
                loadRequests()
            }
        }
    }
}

sealed class AdminSmartlinkRequestsUiState {
    object Loading : AdminSmartlinkRequestsUiState()
    data class Success(val requests: List<AdminSmartlinkRequest>) : AdminSmartlinkRequestsUiState()
    data class Error(val message: String) : AdminSmartlinkRequestsUiState()
}
