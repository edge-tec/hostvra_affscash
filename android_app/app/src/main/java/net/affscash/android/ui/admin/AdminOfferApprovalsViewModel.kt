package net.affscash.android.ui.admin

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.ManagerOfferApprovalRequest
import net.affscash.android.data.model.OfferSimple
import net.affscash.android.data.repository.AdminOfferApprovalRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class AdminOfferApprovalsUiState {
    object Loading : AdminOfferApprovalsUiState()
    data class Success(
        val requests: List<ManagerOfferApprovalRequest>,
        val allOffers: List<OfferSimple>,
        val pendingCount: Int,
        val filterStatus: String,
        val filterOfferId: Int?,
        val filterAff: String
    ) : AdminOfferApprovalsUiState()
    data class Error(val message: String) : AdminOfferApprovalsUiState()
}

@HiltViewModel
class AdminOfferApprovalsViewModel @Inject constructor(
    private val repository: AdminOfferApprovalRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminOfferApprovalsUiState>(AdminOfferApprovalsUiState.Loading)
    val uiState: StateFlow<AdminOfferApprovalsUiState> = _uiState.asStateFlow()

    private var currentFilterStatus = "pending"
    private var currentFilterOfferId: Int? = null
    private var currentFilterAff = ""

    init {
        loadData()
    }

    fun loadData(
        status: String = currentFilterStatus,
        offerId: Int? = currentFilterOfferId,
        aff: String = currentFilterAff
    ) {
        currentFilterStatus = status
        currentFilterOfferId = offerId
        currentFilterAff = aff

        viewModelScope.launch {
            _uiState.value = AdminOfferApprovalsUiState.Loading
            repository.getOfferApprovals(status, offerId, aff.takeIf { it.isNotBlank() })
                .onSuccess { response ->
                    _uiState.value = AdminOfferApprovalsUiState.Success(
                        requests = response.requests ?: emptyList(),
                        allOffers = response.allOffers ?: emptyList(),
                        pendingCount = response.pendingCount ?: 0,
                        filterStatus = status,
                        filterOfferId = offerId,
                        filterAff = aff
                    )
                }
                .onFailure {
                    _uiState.value = AdminOfferApprovalsUiState.Error(it.message ?: "An error occurred")
                }
        }
    }

    fun reviewRequest(affiliateId: Int, offerId: Int, action: String) {
        viewModelScope.launch {
            _uiState.value = AdminOfferApprovalsUiState.Loading
            repository.reviewOfferApproval(affiliateId, offerId, action)
                .onSuccess {
                    loadData()
                }
                .onFailure {
                    _uiState.value = AdminOfferApprovalsUiState.Error(it.message ?: "Failed to review request")
                }
        }
    }
}
