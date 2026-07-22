package net.affscash.android.ui.manager

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
import net.affscash.android.data.model.DateRangeOption
import net.affscash.android.data.model.DateRangeState
import net.affscash.android.data.model.ManagerFraudReportResponse
import net.affscash.android.data.repository.ManagerReportRepository
import javax.inject.Inject

data class ManagerFraudReportsUiState(
    val isLoading: Boolean = false,
    val response: ManagerFraudReportResponse? = null,
    val error: String? = null,
    
    // Filters
    val dateRangeState: DateRangeState = DateRangeState(),
    val clickId: String = "",
    val statusFilter: String = "All Statuses",
    val affiliate: String = "All Affiliates",
    val affCode: String = "",
    val offer: String = "All Offers",
    val scoreMin: String = "",
    val scoreMax: String = "",
    val sortBy: String = "Date Desc"
)

@HiltViewModel
class ManagerFraudReportsViewModel @Inject constructor(
    private val repository: ManagerReportRepository,
    private val dateRangePrefManager: DateRangePreferenceManager
) : ViewModel() {

    private val initialDateRangeState = dateRangePrefManager.getDateRangeState("manager_fraud")
    private val _uiState = MutableStateFlow(ManagerFraudReportsUiState(dateRangeState = initialDateRangeState))
    val uiState: StateFlow<ManagerFraudReportsUiState> = _uiState.asStateFlow()

    private var fetchJob: Job? = null

    init {
        loadReport()
    }

    fun setDateRangeOption(option: DateRangeOption) {
        val newState = _uiState.value.dateRangeState.copy(option = option)
        _uiState.update { it.copy(dateRangeState = newState) }
        dateRangePrefManager.saveDateRangeState("manager_fraud", newState)
        loadReport()
    }

    fun setCustomDateRange(startDate: String, endDate: String) {
        val newState = _uiState.value.dateRangeState.copy(
            option = DateRangeOption.CUSTOM,
            customStartDate = startDate,
            customEndDate = endDate
        )
        _uiState.update { it.copy(dateRangeState = newState) }
        dateRangePrefManager.saveDateRangeState("manager_fraud", newState)
        loadReport()
    }

    fun loadReport() {
        fetchJob?.cancel()
        fetchJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            val (from, to) = _uiState.value.dateRangeState.getFormattedDates()
            repository.getManagerFraudReport(from = from, to = to).collect { result ->
                result.onSuccess { response ->
                    _uiState.update { it.copy(isLoading = false, response = response) }
                }
                result.onFailure { exception ->
                    _uiState.update { it.copy(isLoading = false, error = exception.message) }
                }
            }
        }
    }

    fun updateFilters(
        clickId: String = _uiState.value.clickId,
        statusFilter: String = _uiState.value.statusFilter,
        affiliate: String = _uiState.value.affiliate,
        affCode: String = _uiState.value.affCode,
        offer: String = _uiState.value.offer,
        scoreMin: String = _uiState.value.scoreMin,
        scoreMax: String = _uiState.value.scoreMax,
        sortBy: String = _uiState.value.sortBy
    ) {
        _uiState.update { 
            it.copy(
                clickId = clickId, 
                statusFilter = statusFilter, affiliate = affiliate, affCode = affCode, 
                offer = offer, scoreMin = scoreMin, scoreMax = scoreMax, sortBy = sortBy
            )
        }
        loadReport()
    }
}
