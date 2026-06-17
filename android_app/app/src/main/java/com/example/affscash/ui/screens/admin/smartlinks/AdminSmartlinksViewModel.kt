package com.example.affscash.ui.screens.admin.smartlinks

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.AdminSmartlink
import com.example.affscash.data.model.AdminSmartlinkActionRequest
import com.example.affscash.data.repository.AdminSmartlinkRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject

@HiltViewModel
class AdminSmartlinksViewModel @Inject constructor(
    private val repository: AdminSmartlinkRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminSmartlinksUiState>(AdminSmartlinksUiState.Loading)
    val uiState: StateFlow<AdminSmartlinksUiState> = _uiState

    init {
        loadSmartlinks()
    }

    fun loadSmartlinks() {
        viewModelScope.launch {
            _uiState.value = AdminSmartlinksUiState.Loading
            val result = repository.getAdminSmartlinksDashboard()
            result.onSuccess { wrapper ->
                val data = wrapper.data
                if (wrapper.status == "success" && data != null) {
                    _uiState.value = AdminSmartlinksUiState.Success(
                        smartlinks = data.smartlinks,
                        pendingRequestsCount = data.pendingRequestsCount
                    )
                } else {
                    _uiState.value = AdminSmartlinksUiState.Error("Failed to load smartlinks")
                }
            }.onFailure { e ->
                _uiState.value = AdminSmartlinksUiState.Error(e.message ?: "Unknown error")
            }
        }
    }

    fun toggleStatus(id: Int) {
        viewModelScope.launch {
            val result = repository.submitAdminSmartlinkAction(
                AdminSmartlinkActionRequest(action = "toggle_status", id = id)
            )
            result.onSuccess {
                loadSmartlinks()
            }.onFailure { e ->
                // Optionally handle error
            }
        }
    }

    fun deleteSmartlink(id: Int) {
        viewModelScope.launch {
            val result = repository.submitAdminSmartlinkAction(
                AdminSmartlinkActionRequest(action = "delete", id = id)
            )
            result.onSuccess {
                loadSmartlinks()
            }.onFailure { e ->
                // Optionally handle error
            }
        }
    }
}

sealed class AdminSmartlinksUiState {
    object Loading : AdminSmartlinksUiState()
    data class Success(
        val smartlinks: List<AdminSmartlink>,
        val pendingRequestsCount: Int
    ) : AdminSmartlinksUiState()
    data class Error(val message: String) : AdminSmartlinksUiState()
}
