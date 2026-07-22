package net.affscash.android.ui.conversions

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import net.affscash.android.data.local.DateRangePreferenceManager
import net.affscash.android.data.model.ConversionResponse
import net.affscash.android.data.model.DateRangeOption
import net.affscash.android.data.model.DateRangeState
import net.affscash.android.data.repository.ConversionRepository
import javax.inject.Inject

sealed class AdminConversionsUiState {
    object Loading : AdminConversionsUiState()
    data class Success(val data: ConversionResponse) : AdminConversionsUiState()
    data class Error(val message: String) : AdminConversionsUiState()
}

@HiltViewModel
class AdminConversionsViewModel @Inject constructor(
    private val repository: ConversionRepository,
    private val dateRangePrefManager: DateRangePreferenceManager
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminConversionsUiState>(AdminConversionsUiState.Loading)
    val uiState: StateFlow<AdminConversionsUiState> = _uiState.asStateFlow()

    private val _dateRangeState = MutableStateFlow(dateRangePrefManager.getDateRangeState("admin_conversions"))
    val dateRangeState: StateFlow<DateRangeState> = _dateRangeState.asStateFlow()

    private var fetchJob: Job? = null

    init {
        loadConversions()
    }

    fun setDateRangeOption(option: DateRangeOption) {
        val newState = _dateRangeState.value.copy(option = option)
        _dateRangeState.value = newState
        dateRangePrefManager.saveDateRangeState("admin_conversions", newState)
        loadConversions()
    }

    fun setCustomDateRange(startDate: String, endDate: String) {
        val newState = _dateRangeState.value.copy(
            option = DateRangeOption.CUSTOM,
            customStartDate = startDate,
            customEndDate = endDate
        )
        _dateRangeState.value = newState
        dateRangePrefManager.saveDateRangeState("admin_conversions", newState)
        loadConversions()
    }

    fun loadConversions() {
        fetchJob?.cancel()
        fetchJob = viewModelScope.launch {
            _uiState.value = AdminConversionsUiState.Loading
            val (from, to) = _dateRangeState.value.getFormattedDates()
            repository.getAdminConversions(from = from, to = to)
                .onSuccess { response ->
                    _uiState.value = AdminConversionsUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = AdminConversionsUiState.Error(exception.message ?: "Unknown error")
                }
        }
    }
}
