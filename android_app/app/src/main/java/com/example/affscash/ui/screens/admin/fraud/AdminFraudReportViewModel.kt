package com.example.affscash.ui.screens.admin.fraud

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.AdminFraudActionRequest
import com.example.affscash.data.model.AdminFraudConversion
import com.example.affscash.data.model.AdminFraudFilterItem
import com.example.affscash.data.model.AdminFraudStats
import com.example.affscash.data.repository.AdminFraudRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject
import java.time.LocalDate
import java.time.format.DateTimeFormatter

data class AdminFraudReportState(
    val isLoading: Boolean = false,
    val stats: AdminFraudStats? = null,
    val conversions: List<AdminFraudConversion> = emptyList(),
    val affiliates: List<AdminFraudFilterItem> = emptyList(),
    val offers: List<AdminFraudFilterItem> = emptyList(),
    val error: String? = null,
    val successMessage: String? = null,
    
    // Filters
    val from: String = LocalDate.now().withDayOfMonth(1).format(DateTimeFormatter.ISO_LOCAL_DATE),
    val to: String = LocalDate.now().format(DateTimeFormatter.ISO_LOCAL_DATE),
    val status: String = "all",
    val affiliateId: Int? = null,
    val offerId: Int? = null,
    val clickId: String = "",
    val scoreMin: Int = 0,
    val scoreMax: Int = 100,
    val sort: String = "converted_at",
    val dir: String = "desc"
)

@HiltViewModel
class AdminFraudReportViewModel @Inject constructor(
    private val repository: AdminFraudRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminFraudReportState())
    val uiState: StateFlow<AdminFraudReportState> = _uiState.asStateFlow()

    init {
        loadReport()
    }

    fun loadReport() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null, successMessage = null) }
            val state = _uiState.value
            val result = repository.getFraudScoreReport(
                from = state.from,
                to = state.to,
                status = if (state.status == "all") null else state.status,
                affiliateId = state.affiliateId,
                offerId = state.offerId,
                clickId = state.clickId.ifBlank { null },
                scoreMin = state.scoreMin,
                scoreMax = state.scoreMax,
                sort = state.sort,
                dir = state.dir
            )

            result.onSuccess { response ->
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        stats = response.stats,
                        conversions = response.conversions,
                        affiliates = response.affiliates,
                        offers = response.offers
                    )
                }
            }.onFailure { e ->
                _uiState.update { it.copy(isLoading = false, error = e.message ?: "Failed to load report") }
            }
        }
    }

    fun updateFilter(
        from: String? = null,
        to: String? = null,
        status: String? = null,
        affiliateId: Int? = null,
        offerId: Int? = null,
        clickId: String? = null,
        scoreMin: Int? = null,
        scoreMax: Int? = null,
        sort: String? = null,
        dir: String? = null,
        clearAffiliate: Boolean = false,
        clearOffer: Boolean = false
    ) {
        _uiState.update { state ->
            state.copy(
                from = from ?: state.from,
                to = to ?: state.to,
                status = status ?: state.status,
                affiliateId = if (clearAffiliate) null else (affiliateId ?: state.affiliateId),
                offerId = if (clearOffer) null else (offerId ?: state.offerId),
                clickId = clickId ?: state.clickId,
                scoreMin = scoreMin ?: state.scoreMin,
                scoreMax = scoreMax ?: state.scoreMax,
                sort = sort ?: state.sort,
                dir = dir ?: state.dir
            )
        }
        loadReport()
    }

    fun updateConversionStatus(conversionId: String, newStatus: String, rejectionReason: String = "") {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null, successMessage = null) }
            val request = AdminFraudActionRequest(
                action = "update_status",
                conversionId = conversionId,
                status = newStatus,
                rejectionReason = rejectionReason
            )
            val result = repository.submitAction(request)
            result.onSuccess { response ->
                _uiState.update { it.copy(successMessage = response.message ?: "Status updated") }
                loadReport()
            }.onFailure { e ->
                _uiState.update { it.copy(isLoading = false, error = e.message ?: "Update failed") }
            }
        }
    }

    fun bulkReject(conversionIds: List<String>, rejectionReason: String = "") {
        if (conversionIds.isEmpty()) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null, successMessage = null) }
            val request = AdminFraudActionRequest(
                action = "bulk_reject",
                conversionIds = conversionIds,
                rejectionReason = rejectionReason
            )
            val result = repository.submitAction(request)
            result.onSuccess { response ->
                _uiState.update { it.copy(successMessage = "Rejected ${response.rejectedCount} conversions") }
                loadReport()
            }.onFailure { e ->
                _uiState.update { it.copy(isLoading = false, error = e.message ?: "Bulk reject failed") }
            }
        }
    }

    fun triggerLiveCheck(batch: Int = 5) {
        viewModelScope.launch {
            val request = AdminFraudActionRequest(action = "tick", batch = batch)
            val result = repository.submitAction(request)
            result.onSuccess {
                // Silently reload report to reflect new scores
                loadReport()
            }
        }
    }
    
    fun clearMessages() {
        _uiState.update { it.copy(error = null, successMessage = null) }
    }
}
