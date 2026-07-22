package net.affscash.android.ui.admin.duplicate_conversions

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
import net.affscash.android.data.model.DuplicateConversionsResponse
import net.affscash.android.data.repository.ConversionRepository
import javax.inject.Inject

data class AdminDuplicateConversionsUiState(
    val isLoading: Boolean = false,
    val response: DuplicateConversionsResponse? = null,
    val error: String? = null,
    val dateRangeState: DateRangeState = DateRangeState()
)

@HiltViewModel
class AdminDuplicateConversionsViewModel @Inject constructor(
    private val repository: ConversionRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminDuplicateConversionsUiState())
    val uiState: StateFlow<AdminDuplicateConversionsUiState> = _uiState.asStateFlow()

    private var loadJob: Job? = null

    init {
        loadReport()
    }

    fun loadReport() {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            val (from, to) = _uiState.value.dateRangeState.getFormattedDates()

            val result = repository.getAdminDuplicateConversions(from = from, to = to)
            result.onSuccess { response ->
                _uiState.update { it.copy(isLoading = false, response = response) }
            }.onFailure { exception ->
                _uiState.update { it.copy(isLoading = false, error = exception.message ?: "Failed to load duplicate conversions") }
            }
        }
    }

    fun setDateRangeOption(option: DateRangeOption) {
        _uiState.update { current ->
            current.copy(dateRangeState = current.dateRangeState.copy(option = option))
        }
        loadReport()
    }

    fun setCustomDateRange(startDate: String, endDate: String) {
        _uiState.update { current ->
            current.copy(
                dateRangeState = current.dateRangeState.copy(
                    option = DateRangeOption.CUSTOM,
                    customStartDate = startDate,
                    customEndDate = endDate
                )
            )
        }
        loadReport()
    }
}
