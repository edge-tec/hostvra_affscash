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
import net.affscash.android.data.model.ReportFiltersResponse
import net.affscash.android.data.model.ReportResponse
import net.affscash.android.data.repository.ManagerReportRepository
import javax.inject.Inject

data class ManagerReportsUiState(
    val isLoadingFilters: Boolean = false,
    val filtersResponse: ReportFiltersResponse? = null,
    val filtersError: String? = null,

    val isLoadingReport: Boolean = false,
    val reportResponse: ReportResponse? = null,
    val reportError: String? = null,

    // Current filters applied
    val currentTab: String = "day",
    val selectedMetric: String = "Clicks",
    val selectedView: String = "Chart View",
    val dateRangeState: DateRangeState = DateRangeState(),
    val selectedOfferId: Int = 0,
    val selectedAffiliateId: Int = 0,
    val selectedCountry: String = "",
    val sub1Query: String = ""
)

@HiltViewModel
class ManagerReportsViewModel @Inject constructor(
    private val repository: ManagerReportRepository,
    private val dateRangePrefManager: DateRangePreferenceManager
) : ViewModel() {

    private val initialDateRangeState = dateRangePrefManager.getDateRangeState("manager_reports")
    private val _uiState = MutableStateFlow(ManagerReportsUiState(dateRangeState = initialDateRangeState))
    val uiState: StateFlow<ManagerReportsUiState> = _uiState.asStateFlow()

    private var fetchJob: Job? = null

    init {
        loadFilters()
    }

    private fun loadFilters() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingFilters = true, filtersError = null) }
            repository.getManagerReportFilters().collect { result ->
                result.onSuccess { response ->
                    _uiState.update { it.copy(isLoadingFilters = false, filtersResponse = response) }
                    loadReport() // Load report after filters are loaded
                }
                result.onFailure { exception ->
                    _uiState.update { it.copy(isLoadingFilters = false, filtersError = exception.message) }
                }
            }
        }
    }

    fun loadReport() {
        fetchJob?.cancel()
        fetchJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoadingReport = true, reportError = null) }
            val state = _uiState.value
            val (from, to) = state.dateRangeState.getFormattedDates()
            repository.getManagerReports(
                tab = state.currentTab,
                from = from,
                to = to,
                offerId = if (state.selectedOfferId > 0) state.selectedOfferId else null,
                affiliateId = if (state.selectedAffiliateId > 0) state.selectedAffiliateId else null,
                country = if (state.selectedCountry.isNotEmpty()) state.selectedCountry else null,
                sub1 = if (state.sub1Query.isNotEmpty()) state.sub1Query else null
            ).collect { result ->
                result.onSuccess { response ->
                    _uiState.update { it.copy(isLoadingReport = false, reportResponse = response) }
                }
                result.onFailure { exception ->
                    _uiState.update { it.copy(isLoadingReport = false, reportError = exception.message) }
                }
            }
        }
    }

    fun setDateRangeOption(option: DateRangeOption) {
        val newState = _uiState.value.dateRangeState.copy(option = option)
        _uiState.update { it.copy(dateRangeState = newState) }
        dateRangePrefManager.saveDateRangeState("manager_reports", newState)
        loadReport()
    }

    fun setCustomDateRange(startDate: String, endDate: String) {
        val newState = _uiState.value.dateRangeState.copy(
            option = DateRangeOption.CUSTOM,
            customStartDate = startDate,
            customEndDate = endDate
        )
        _uiState.update { it.copy(dateRangeState = newState) }
        dateRangePrefManager.saveDateRangeState("manager_reports", newState)
        loadReport()
    }

    fun setTab(tab: String) {
        if (_uiState.value.currentTab != tab) {
            _uiState.update { it.copy(currentTab = tab) }
            loadReport()
        }
    }

    fun setFilter(offerId: Int, affiliateId: Int, country: String, sub1: String) {
        _uiState.update { it.copy(
            selectedOfferId = offerId,
            selectedAffiliateId = affiliateId,
            selectedCountry = country,
            sub1Query = sub1
        ) }
        loadReport()
    }

    fun setMetric(metric: String) {
        if (_uiState.value.selectedMetric != metric) {
            _uiState.update { it.copy(selectedMetric = metric) }
        }
    }

    fun setView(view: String) {
        if (_uiState.value.selectedView != view) {
            _uiState.update { it.copy(selectedView = view) }
        }
    }
}
