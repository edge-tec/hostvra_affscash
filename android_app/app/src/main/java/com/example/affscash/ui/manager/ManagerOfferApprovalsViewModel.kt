package com.example.affscash.ui.manager

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.OfferApprovalRequestItem
import com.example.affscash.data.model.OfferOption
import com.example.affscash.data.repository.ManagerOfferApprovalRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class ManagerOfferApprovalsUiState(
    val isLoading: Boolean = false,
    val isActionLoading: Boolean = false,
    val statusFilter: String = "pending", // pending, approved, rejected, all
    val offerFilter: OfferOption? = null,
    val searchQuery: String = "",
    val requests: List<OfferApprovalRequestItem> = emptyList(),
    val allOffers: List<OfferOption> = emptyList(),
    val pendingCount: Int = 0,
    val actionMessage: String? = null,
    val error: String? = null
)

@HiltViewModel
class ManagerOfferApprovalsViewModel @Inject constructor(
    private val repository: ManagerOfferApprovalRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(ManagerOfferApprovalsUiState())
    val uiState: StateFlow<ManagerOfferApprovalsUiState> = _uiState

    init {
        loadRequests()
    }

    fun loadRequests() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            val state = _uiState.value
            val result = repository.getOfferApprovals(state.statusFilter, state.offerFilter?.id, state.searchQuery.ifBlank { null })
            
            result.onSuccess { response ->
                _uiState.update { 
                    it.copy(
                        isLoading = false,
                        requests = response.requests,
                        allOffers = response.all_offers,
                        pendingCount = response.pending_count
                    )
                }
            }.onFailure { error ->
                _uiState.update { it.copy(isLoading = false, error = error.message) }
            }
        }
    }

    fun setStatusFilter(status: String) {
        _uiState.update { it.copy(statusFilter = status) }
        loadRequests()
    }

    fun setOfferFilter(offer: OfferOption?) {
        _uiState.update { it.copy(offerFilter = offer) }
        loadRequests()
    }

    fun updateSearchQuery(query: String) {
        _uiState.update { it.copy(searchQuery = query) }
    }

    fun reviewRequest(affiliateId: Int, offerId: Int, action: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isActionLoading = true, error = null) }
            val result = repository.reviewOfferApproval(affiliateId, offerId, action)
            result.onSuccess {
                _uiState.update { it.copy(isActionLoading = false, actionMessage = if(action == "approve") "Request approved" else "Request rejected") }
                loadRequests() // Refresh the list
            }.onFailure { error ->
                _uiState.update { it.copy(isActionLoading = false, error = error.message) }
            }
        }
    }

    fun clearActionMessage() {
        _uiState.update { it.copy(actionMessage = null, error = null) }
    }
}
