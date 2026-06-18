package net.affscash.android.ui.admin

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.*
import net.affscash.android.data.repository.AdminAdvertiserRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class AdminAdvertisersUiState(
    val isLoading: Boolean = false,
    val isActionLoading: Boolean = false,
    val advertisers: List<AdminAdvertiserListModel> = emptyList(),
    val searchQuery: String = "",
    val statusFilter: String = "all",
    val error: String? = null,
    val actionMessage: String? = null,
    val isDetailsLoading: Boolean = false,
    val selectedAdvertiserDetails: AdminAdvertiserDetailsData? = null
)

@HiltViewModel
class AdminAdvertisersViewModel @Inject constructor(
    private val repository: AdminAdvertiserRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminAdvertisersUiState())
    val uiState: StateFlow<AdminAdvertisersUiState> = _uiState.asStateFlow()

    init {
        loadAdvertisers()
    }

    fun loadAdvertisers() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null, actionMessage = null) }
            val query = _uiState.value.searchQuery.ifBlank { "" }
            val status = _uiState.value.statusFilter
            
            val result = repository.getAdvertisers(status = status, search = query)
            
            result.onSuccess { response ->
                _uiState.update { it.copy(
                    isLoading = false,
                    advertisers = response.data?.advertisers ?: emptyList()
                ) }
            }.onFailure { error ->
                _uiState.update { it.copy(
                    isLoading = false,
                    error = error.message
                ) }
            }
        }
    }

    fun updateSearchQuery(query: String) {
        _uiState.update { it.copy(searchQuery = query) }
        loadAdvertisers()
    }

    fun setStatusFilter(status: String) {
        _uiState.update { it.copy(statusFilter = status) }
        loadAdvertisers()
    }

    fun deleteAdvertiser(id: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isActionLoading = true, error = null) }
            val result = repository.deleteAdvertiser(id)
            result.onSuccess {
                _uiState.update { it.copy(isActionLoading = false, actionMessage = "Advertiser deleted") }
                loadAdvertisers()
            }.onFailure { error ->
                _uiState.update { it.copy(isActionLoading = false, error = error.message) }
            }
        }
    }

    fun updateStatus(id: Int, status: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isActionLoading = true, error = null) }
            val result = repository.updateAdvertiserStatus(id, status)
            result.onSuccess {
                _uiState.update { it.copy(isActionLoading = false, actionMessage = "Status updated") }
                loadAdvertisers()
            }.onFailure { error ->
                _uiState.update { it.copy(isActionLoading = false, error = error.message) }
            }
        }
    }

    fun impersonateAdvertiser(id: Int, onLoginSuccess: (String, User) -> Unit) {
        viewModelScope.launch {
            _uiState.update { it.copy(isActionLoading = true, error = null) }
            val result = repository.impersonateAdvertiser(id)
            result.onSuccess { response ->
                _uiState.update { it.copy(isActionLoading = false) }
                if (response.role != null && response.user != null) {
                    onLoginSuccess(response.role, response.user)
                }
            }.onFailure { error ->
                _uiState.update { it.copy(isActionLoading = false, error = error.message) }
            }
        }
    }

    fun clearActionMessage() {
        _uiState.update { it.copy(actionMessage = null, error = null) }
    }

    fun loadAdvertiserDetails(id: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isDetailsLoading = true, selectedAdvertiserDetails = null, error = null) }
            val result = repository.getAdvertiserDetails(id)
            result.onSuccess { response ->
                _uiState.update { it.copy(
                    isDetailsLoading = false,
                    selectedAdvertiserDetails = response.data
                ) }
            }.onFailure { exception ->
                _uiState.update { it.copy(
                    isDetailsLoading = false,
                    error = exception.message ?: "Failed to load advertiser details"
                ) }
            }
        }
    }
}
