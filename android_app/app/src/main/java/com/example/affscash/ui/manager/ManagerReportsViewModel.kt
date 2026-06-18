package com.example.affscash.ui.manager

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.ReportFiltersResponse
import com.example.affscash.data.model.ReportResponse
import com.example.affscash.data.repository.ManagerReportRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale
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
    val fromDate: String = "",
    val toDate: String = "",
    val selectedOfferId: Int = 0,
    val selectedAffiliateId: Int = 0,
    val selectedCountry: String = "",
    val sub1Query: String = ""
)

@HiltViewModel
class ManagerReportsViewModel @Inject constructor(private val repository: ManagerReportRepository) : ViewModel() {
    private val _uiState = MutableStateFlow(ManagerReportsUiState())
    val uiState: StateFlow<ManagerReportsUiState> = _uiState.asStateFlow()

    init {
        val dateFormat = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault())
        val cal = Calendar.getInstance()
        val toDateStr = dateFormat.format(cal.time)
        cal.set(Calendar.DAY_OF_MONTH, 1)
        val fromDateStr = dateFormat.format(cal.time)

        _uiState.update { it.copy(fromDate = fromDateStr, toDate = toDateStr) }
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
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingReport = true, reportError = null) }
            val state = _uiState.value
            repository.getManagerReports(
                tab = state.currentTab,
                from = state.fromDate,
                to = state.toDate,
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

    fun setTab(tab: String) {
        if (_uiState.value.currentTab != tab) {
            _uiState.update { it.copy(currentTab = tab) }
            loadReport()
        }
    }

    fun setDateRange(from: String, to: String) {
        _uiState.update { it.copy(fromDate = from, toDate = to) }
        loadReport()
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
