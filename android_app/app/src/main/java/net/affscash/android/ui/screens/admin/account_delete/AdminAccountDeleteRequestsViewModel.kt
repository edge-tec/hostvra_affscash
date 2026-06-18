package net.affscash.android.ui.screens.admin.account_delete

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import net.affscash.android.data.model.AdminAccountDeleteActionRequest
import net.affscash.android.data.model.AdminAccountDeleteRequestItem
import net.affscash.android.data.model.AdminAccountDeleteStats
import net.affscash.android.data.repository.AdminAccountDeleteRepository
import javax.inject.Inject

data class AdminAccountDeleteRequestsUiState(
    val isLoading: Boolean = false,
    val requests: List<AdminAccountDeleteRequestItem> = emptyList(),
    val stats: AdminAccountDeleteStats? = null,
    val error: String? = null,
    val currentTab: String = "pending"
)

@HiltViewModel
class AdminAccountDeleteRequestsViewModel @Inject constructor(
    private val repository: AdminAccountDeleteRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminAccountDeleteRequestsUiState())
    val uiState: StateFlow<AdminAccountDeleteRequestsUiState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun setTab(tab: String) {
        _uiState.value = _uiState.value.copy(currentTab = tab)
        loadData()
    }

    fun loadData() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, error = null)
            val statusParam = if (_uiState.value.currentTab == "all") "all" else _uiState.value.currentTab
            repository.getRequests(status = statusParam)
                .onSuccess { response ->
                    if (response.success) {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            requests = response.requests,
                            stats = response.stats
                        )
                    } else {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            error = response.error ?: "Failed to load requests"
                        )
                    }
                }
                .onFailure {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        error = it.message ?: "An error occurred"
                    )
                }
        }
    }

    fun submitAction(requestId: Int, decision: String, adminNote: String, onSuccess: (String) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true)
            val request = AdminAccountDeleteActionRequest(
                requestId = requestId,
                decision = decision,
                adminNote = adminNote
            )
            repository.submitAction(request)
                .onSuccess { response ->
                    if (response.success) {
                        onSuccess(response.message ?: "Action completed successfully")
                        loadData()
                    } else {
                        _uiState.value = _uiState.value.copy(isLoading = false)
                        onError(response.error ?: "Failed to submit action")
                    }
                }
                .onFailure {
                    _uiState.value = _uiState.value.copy(isLoading = false)
                    onError(it.message ?: "An error occurred")
                }
        }
    }
}
