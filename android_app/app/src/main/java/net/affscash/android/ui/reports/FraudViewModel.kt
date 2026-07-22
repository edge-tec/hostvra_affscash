package net.affscash.android.ui.reports

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import net.affscash.android.data.local.DateRangePreferenceManager
import net.affscash.android.data.model.DateRangeOption
import net.affscash.android.data.model.DateRangeState
import net.affscash.android.data.model.FraudReportResponse
import net.affscash.android.data.repository.FraudRepository
import javax.inject.Inject

sealed class FraudReportState {
    object Loading : FraudReportState()
    data class Success(val data: FraudReportResponse) : FraudReportState()
    data class Error(val message: String) : FraudReportState()
}

@HiltViewModel
class FraudViewModel @Inject constructor(
    private val repository: FraudRepository,
    private val dateRangePrefManager: DateRangePreferenceManager
) : ViewModel() {

    private val _uiState = MutableStateFlow<FraudReportState>(FraudReportState.Loading)
    val uiState: StateFlow<FraudReportState> = _uiState.asStateFlow()

    private val _dateRangeState = MutableStateFlow(dateRangePrefManager.getDateRangeState("affiliate_fraud"))
    val dateRangeState: StateFlow<DateRangeState> = _dateRangeState.asStateFlow()

    private var fetchJob: Job? = null

    init {
        loadData()
    }

    fun setDateRangeOption(option: DateRangeOption) {
        val newState = _dateRangeState.value.copy(option = option)
        _dateRangeState.value = newState
        dateRangePrefManager.saveDateRangeState("affiliate_fraud", newState)
        loadData()
    }

    fun setCustomDateRange(startDate: String, endDate: String) {
        val newState = _dateRangeState.value.copy(
            option = DateRangeOption.CUSTOM,
            customStartDate = startDate,
            customEndDate = endDate
        )
        _dateRangeState.value = newState
        dateRangePrefManager.saveDateRangeState("affiliate_fraud", newState)
        loadData()
    }

    fun loadData() {
        fetchJob?.cancel()
        fetchJob = viewModelScope.launch {
            _uiState.value = FraudReportState.Loading
            val (from, to) = _dateRangeState.value.getFormattedDates()
            repository.getFraudReport(from = from, to = to).fold(
                onSuccess = { response ->
                    if (response.success) {
                        _uiState.value = FraudReportState.Success(response)
                    } else {
                        _uiState.value = FraudReportState.Error(response.error ?: "Failed to load fraud report")
                    }
                },
                onFailure = { error ->
                    _uiState.value = FraudReportState.Error(error.message ?: "Unknown error")
                }
            )
        }
    }
}
