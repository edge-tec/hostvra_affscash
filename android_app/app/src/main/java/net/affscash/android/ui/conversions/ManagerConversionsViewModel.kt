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

sealed class ManagerConversionsUiState {
    object Loading : ManagerConversionsUiState()
    data class Success(val data: ConversionResponse) : ManagerConversionsUiState()
    data class Error(val message: String) : ManagerConversionsUiState()
}

@HiltViewModel
class ManagerConversionsViewModel @Inject constructor(
    private val repository: ConversionRepository,
    private val dateRangePrefManager: DateRangePreferenceManager
) : ViewModel() {

    private val _uiState = MutableStateFlow<ManagerConversionsUiState>(ManagerConversionsUiState.Loading)
    val uiState: StateFlow<ManagerConversionsUiState> = _uiState.asStateFlow()

    private val _dateRangeState = MutableStateFlow(dateRangePrefManager.getDateRangeState("manager_conversions"))
    val dateRangeState: StateFlow<DateRangeState> = _dateRangeState.asStateFlow()

    private val _statusFilter = MutableStateFlow("all")
    val statusFilter: StateFlow<String> = _statusFilter.asStateFlow()

    private val _searchQuery = MutableStateFlow("")
    val searchQuery: StateFlow<String> = _searchQuery.asStateFlow()

    private var fetchJob: Job? = null

    init {
        loadConversions()
    }

    fun setStatusFilter(status: String) {
        _statusFilter.value = status
    }

    fun setSearchQuery(query: String) {
        _searchQuery.value = query
    }

    fun setDateRangeOption(option: DateRangeOption) {
        val newState = _dateRangeState.value.copy(option = option)
        _dateRangeState.value = newState
        dateRangePrefManager.saveDateRangeState("manager_conversions", newState)
        loadConversions()
    }

    fun setCustomDateRange(startDate: String, endDate: String) {
        val newState = _dateRangeState.value.copy(
            option = DateRangeOption.CUSTOM,
            customStartDate = startDate,
            customEndDate = endDate
        )
        _dateRangeState.value = newState
        dateRangePrefManager.saveDateRangeState("manager_conversions", newState)
        loadConversions()
    }

    fun loadConversions() {
        fetchJob?.cancel()
        fetchJob = viewModelScope.launch {
            _uiState.value = ManagerConversionsUiState.Loading
            val (from, to) = _dateRangeState.value.getFormattedDates()
            repository.getManagerConversions(from = from, to = to)
                .onSuccess { response ->
                    _uiState.value = ManagerConversionsUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = ManagerConversionsUiState.Error(exception.message ?: "Unknown error")
                }
        }
    }
}
