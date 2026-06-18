package net.affscash.android.ui.manager

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.ManagedAffiliate
import net.affscash.android.data.model.ManagerSmartlink
import net.affscash.android.data.model.ManagerSmartlinkRequest
import net.affscash.android.data.repository.SmartlinkRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class ManagerSmartlinksUiState(
    val isLoading: Boolean = false,
    val isRequestsLoading: Boolean = false,
    val smartlinks: List<ManagerSmartlink> = emptyList(),
    val requests: List<ManagerSmartlinkRequest> = emptyList(),
    val managedAffiliates: List<ManagedAffiliate> = emptyList(),
    val trackingUrlBase: String = "",
    val pendingRequestsCount: Int = 0,
    val error: String? = null,
    val requestError: String? = null,
    val searchQuery: String = "",
    val requestFilter: String = "all", // pending, approved, rejected, all
    val isReviewing: Boolean = false
)

@HiltViewModel
class ManagerSmartlinksViewModel @Inject constructor(
    private val repository: SmartlinkRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(ManagerSmartlinksUiState())
    val uiState: StateFlow<ManagerSmartlinksUiState> = _uiState.asStateFlow()

    init {
        loadSmartlinks()
    }

    fun loadSmartlinks() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            val query = _uiState.value.searchQuery.ifBlank { null }
            val result = repository.getManagerSmartlinks(query = query)
            
            result.onSuccess { response ->
                _uiState.update { it.copy(
                    isLoading = false,
                    smartlinks = response.data?.smartlinks ?: emptyList(),
                    pendingRequestsCount = response.data?.pendingRequestsCount ?: 0,
                    managedAffiliates = response.data?.managedAffiliates ?: emptyList(),
                    trackingUrlBase = response.data?.trackingUrlBase ?: ""
                ) }
            }.onFailure { error ->
                _uiState.update { it.copy(
                    isLoading = false,
                    error = error.message
                ) }
            }
        }
    }

    fun loadRequests() {
        viewModelScope.launch {
            _uiState.update { it.copy(isRequestsLoading = true, requestError = null) }
            val result = repository.getManagerSmartlinkRequests(status = _uiState.value.requestFilter)
            
            result.onSuccess { response ->
                _uiState.update { it.copy(
                    isRequestsLoading = false,
                    requests = response.data?.requests ?: emptyList()
                ) }
            }.onFailure { error ->
                _uiState.update { it.copy(
                    isRequestsLoading = false,
                    requestError = error.message
                ) }
            }
        }
    }

    fun updateSearchQuery(query: String) {
        _uiState.update { it.copy(searchQuery = query) }
        loadSmartlinks()
    }

    fun setRequestFilter(filter: String) {
        _uiState.update { it.copy(requestFilter = filter) }
        loadRequests()
    }

    fun reviewRequest(requestId: Int, decision: String, note: String? = null) {
        viewModelScope.launch {
            _uiState.update { it.copy(isReviewing = true, requestError = null) }
            val result = repository.reviewManagerSmartlinkRequest(requestId, decision, note)
            
            result.onSuccess {
                _uiState.update { it.copy(isReviewing = false) }
                loadRequests() // Refresh list
                loadSmartlinks() // Refresh pending count
            }.onFailure { error ->
                _uiState.update { it.copy(isReviewing = false, requestError = error.message) }
            }
        }
    }
}
