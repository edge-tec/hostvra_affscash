package net.affscash.android.ui.manager

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.*
import net.affscash.android.data.repository.ManagerAffiliateRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class ManagerAffiliatesUiState(
    val isLoading: Boolean = false,
    val isActionLoading: Boolean = false,
    val affiliates: List<ManagerAffiliateListModel> = emptyList(),
    val canApprove: Boolean = false,
    val searchQuery: String = "",
    val fraudScoreFilter: String = "all", // all, low, medium, high
    val statusFilter: String = "all", // all, active, pending, suspended, rejected
    val error: String? = null,
    val actionMessage: String? = null,
    
    // Details
    val isDetailsLoading: Boolean = false,
    val selectedAffiliateDetails: ManagerAffiliateDetailsData? = null
)

@HiltViewModel
class ManagerAffiliatesViewModel @Inject constructor(
    private val repository: ManagerAffiliateRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(ManagerAffiliatesUiState())
    val uiState: StateFlow<ManagerAffiliatesUiState> = _uiState.asStateFlow()

    init {
        loadAffiliates()
    }

    fun loadAffiliates() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null, actionMessage = null) }
            val query = _uiState.value.searchQuery.ifBlank { null }
            val filter = _uiState.value.fraudScoreFilter
            val status = _uiState.value.statusFilter
            
            val result = repository.getManagerAffiliates(query = query, fraudScoreFilter = filter, status = status)
            
            result.onSuccess { response ->
                _uiState.update { it.copy(
                    isLoading = false,
                    affiliates = response.data?.affiliates ?: emptyList(),
                    canApprove = response.data?.canApprove ?: false
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
        loadAffiliates()
    }

    fun setFraudScoreFilter(filter: String) {
        _uiState.update { it.copy(fraudScoreFilter = filter) }
        loadAffiliates()
    }

    fun setStatusFilter(status: String) {
        _uiState.update { it.copy(statusFilter = status) }
        loadAffiliates()
    }

    fun updateAffiliateStatus(affId: Int, status: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isActionLoading = true, error = null, actionMessage = null) }
            val result = repository.updateAffiliateStatus(affId, status)
            
            result.onSuccess {
                _uiState.update { it.copy(isActionLoading = false, actionMessage = "Affiliate status updated") }
                loadAffiliates()
            }.onFailure { error ->
                _uiState.update { it.copy(isActionLoading = false, error = error.message) }
            }
        }
    }

    fun loadAffiliateDetails(affId: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isDetailsLoading = true, selectedAffiliateDetails = null, error = null) }
            val result = repository.getAffiliateDetails(affId)
            
            result.onSuccess { response ->
                _uiState.update { it.copy(
                    isDetailsLoading = false,
                    selectedAffiliateDetails = response.data
                ) }
            }.onFailure { error ->
                _uiState.update { it.copy(
                    isDetailsLoading = false,
                    error = error.message
                ) }
            }
        }
    }

    fun createAffiliate(request: CreateAffiliateRequest, onSuccess: () -> Unit) {
        viewModelScope.launch {
            _uiState.update { it.copy(isActionLoading = true, error = null) }
            val result = repository.createAffiliate(request)
            result.onSuccess {
                _uiState.update { it.copy(isActionLoading = false, actionMessage = "Affiliate created successfully") }
                loadAffiliates()
                onSuccess()
            }.onFailure { error ->
                _uiState.update { it.copy(isActionLoading = false, error = error.message) }
            }
        }
    }

    fun editAffiliate(request: EditAffiliateRequest, onSuccess: () -> Unit) {
        viewModelScope.launch {
            _uiState.update { it.copy(isActionLoading = true, error = null) }
            val result = repository.editAffiliate(request)
            result.onSuccess {
                _uiState.update { it.copy(isActionLoading = false, actionMessage = "Affiliate updated successfully") }
                loadAffiliates()
                onSuccess()
            }.onFailure { error ->
                _uiState.update { it.copy(isActionLoading = false, error = error.message) }
            }
        }
    }

    fun impersonateAffiliate(affId: Int, onLoginSuccess: (String, User) -> Unit) {
        viewModelScope.launch {
            _uiState.update { it.copy(isActionLoading = true, error = null) }
            val result = repository.impersonateAffiliate(affId)
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
}
