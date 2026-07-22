package net.affscash.android.ui.screens.admin.fraud

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import net.affscash.android.data.local.DateRangePreferenceManager
import net.affscash.android.data.model.AdminFraudActionRequest
import net.affscash.android.data.model.AdminFraudConversion
import net.affscash.android.data.model.AdminFraudFilterItem
import net.affscash.android.data.model.AdminFraudStats
import net.affscash.android.data.model.DateRangeOption
import net.affscash.android.data.model.DateRangeState
import net.affscash.android.data.repository.AdminFraudRepository
import javax.inject.Inject

data class AdminFraudReportState(
    val isLoading: Boolean = false,
    val stats: AdminFraudStats? = null,
    val conversions: List<AdminFraudConversion> = emptyList(),
    val affiliates: List<AdminFraudFilterItem> = emptyList(),
    val offers: List<AdminFraudFilterItem> = emptyList(),
    val error: String? = null,
    val successMessage: String? = null,
    
    // Filters
    val dateRangeState: DateRangeState = DateRangeState(),
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
    private val repository: AdminFraudRepository,
    private val dateRangePrefManager: DateRangePreferenceManager
) : ViewModel() {

    private val initialDateRangeState = dateRangePrefManager.getDateRangeState("admin_fraud")
    private val _uiState = MutableStateFlow(AdminFraudReportState(dateRangeState = initialDateRangeState))
    val uiState: StateFlow<AdminFraudReportState> = _uiState.asStateFlow()

    private var fetchJob: Job? = null

    init {
        loadReport()
    }

    fun setDateRangeOption(option: DateRangeOption) {
        val newState = _uiState.value.dateRangeState.copy(option = option)
        _uiState.update { it.copy(dateRangeState = newState) }
        dateRangePrefManager.saveDateRangeState("admin_fraud", newState)
        loadReport()
    }

    fun setCustomDateRange(startDate: String, endDate: String) {
        val newState = _uiState.value.dateRangeState.copy(
            option = DateRangeOption.CUSTOM,
            customStartDate = startDate,
            customEndDate = endDate
        )
        _uiState.update { it.copy(dateRangeState = newState) }
        dateRangePrefManager.saveDateRangeState("admin_fraud", newState)
        loadReport()
    }

    fun loadReport() {
        fetchJob?.cancel()
        fetchJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null, successMessage = null) }
            val state = _uiState.value
            val (from, to) = state.dateRangeState.getFormattedDates()
            val result = repository.getFraudScoreReport(
                from = from,
                to = to,
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
