package net.affscash.android.ui.admin.traffic_source_override

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import net.affscash.android.data.model.DateRangeOption
import net.affscash.android.data.model.DateRangeState
import net.affscash.android.data.model.TrafficSourceOverrideLogsData
import net.affscash.android.data.repository.TrafficSourceOverrideRepository
import javax.inject.Inject

data class TrafficSourceOverrideLogsUiState(
    val isLoading: Boolean = false,
    val logsData: TrafficSourceOverrideLogsData? = null,
    val error: String? = null,
    val dateRangeState: DateRangeState = DateRangeState()
)

@HiltViewModel
class TrafficSourceOverrideLogsViewModel @Inject constructor(
    private val repository: TrafficSourceOverrideRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(TrafficSourceOverrideLogsUiState())
    val uiState: StateFlow<TrafficSourceOverrideLogsUiState> = _uiState.asStateFlow()

    private var loadJob: Job? = null

    fun loadLogs(isManager: Boolean = false) {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            val (from, to) = _uiState.value.dateRangeState.getFormattedDates()
            val result = if (isManager) repository.getManagerTrafficSourceOverrideLogs(from, to) else repository.getAdminTrafficSourceOverrideLogs(from, to)
            result.onSuccess { response ->
                _uiState.update { it.copy(isLoading = false, logsData = response.data, error = response.message) }
            }.onFailure { exception ->
                _uiState.update { it.copy(isLoading = false, error = exception.message ?: "Failed to load logs") }
            }
        }
    }

    fun setDateRangeOption(option: DateRangeOption, isManager: Boolean = false) {
        _uiState.update { current ->
            current.copy(dateRangeState = current.dateRangeState.copy(option = option))
        }
        loadLogs(isManager)
    }

    fun setCustomDateRange(startDate: String, endDate: String, isManager: Boolean = false) {
        _uiState.update { current ->
            current.copy(
                dateRangeState = current.dateRangeState.copy(
                    option = DateRangeOption.CUSTOM,
                    customStartDate = startDate,
                    customEndDate = endDate
                )
            )
        }
        loadLogs(isManager)
    }
}
