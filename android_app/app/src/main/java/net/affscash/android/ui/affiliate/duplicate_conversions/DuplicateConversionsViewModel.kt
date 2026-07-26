package net.affscash.android.ui.affiliate.duplicate_conversions

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import net.affscash.android.data.model.AffiliateDuplicateConversionsData
import net.affscash.android.data.model.DateRangeOption
import net.affscash.android.data.model.DateRangeState
import net.affscash.android.data.repository.DuplicateConversionsRepository
import javax.inject.Inject

data class AffiliateDuplicateConversionsUiState(
    val isLoading: Boolean = false,
    val data: AffiliateDuplicateConversionsData? = null,
    val error: String? = null,
    val dateRangeState: DateRangeState = DateRangeState()
)

@HiltViewModel
class DuplicateConversionsViewModel @Inject constructor(
    private val repository: DuplicateConversionsRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AffiliateDuplicateConversionsUiState())
    val uiState: StateFlow<AffiliateDuplicateConversionsUiState> = _uiState.asStateFlow()

    private var loadJob: Job? = null

    init {
        loadData()
    }

    fun loadData() {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            val (from, to) = _uiState.value.dateRangeState.getFormattedDates()

            repository.getDuplicateConversions(from = from, to = to)
                .onSuccess { response ->
                    val returnData = response.data
                    if (returnData != null) {
                        _uiState.update { it.copy(isLoading = false, data = returnData) }
                    } else {
                        _uiState.update { it.copy(isLoading = false, error = "No data returned") }
                    }
                }
                .onFailure { exception ->
                    _uiState.update { it.copy(isLoading = false, error = exception.message ?: "Failed to load duplicate conversions") }
                }
        }
    }

    fun setDateRangeOption(option: DateRangeOption) {
        _uiState.update { current ->
            current.copy(dateRangeState = current.dateRangeState.copy(option = option))
        }
        loadData()
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
        loadData()
    }
}
