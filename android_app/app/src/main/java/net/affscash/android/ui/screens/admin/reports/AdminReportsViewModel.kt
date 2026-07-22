package net.affscash.android.ui.screens.admin.reports

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.serialization.json.JsonObject
import net.affscash.android.data.local.DateRangePreferenceManager
import net.affscash.android.data.model.AdminReportFilterOption
import net.affscash.android.data.model.AdminReportTotals
import net.affscash.android.data.model.DateRangeOption
import net.affscash.android.data.model.DateRangeState
import net.affscash.android.data.repository.AdminReportRepository
import javax.inject.Inject

data class AdminReportsState(
    val isLoading: Boolean = false,
    val tab: String = "performance",
    
    // Filters state
    val dateRangeState: DateRangeState = DateRangeState(),
    val groupBy: String = "date",
    val offerId: Int? = null,
    val affiliateId: Int? = null,
    val country: String? = null,
    val sub1: String = "",
    val slId: Int? = null,
    
    // Filter Options
    val offers: List<AdminReportFilterOption> = emptyList(),
    val affiliates: List<AdminReportFilterOption> = emptyList(),
    val countries: List<String> = emptyList(),
    
    // Data
    val totals: AdminReportTotals? = null,
    val rows: List<JsonObject> = emptyList(),
    val error: String? = null
)

@HiltViewModel
class AdminReportsViewModel @Inject constructor(
    private val repository: AdminReportRepository,
    private val dateRangePrefManager: DateRangePreferenceManager
) : ViewModel() {

    private val initialDateRangeState = dateRangePrefManager.getDateRangeState("admin_reports")
    private val _uiState = MutableStateFlow(AdminReportsState(dateRangeState = initialDateRangeState))
    val uiState: StateFlow<AdminReportsState> = _uiState.asStateFlow()

    private var fetchJob: Job? = null

    init {
        loadFilters()
        loadReport()
    }

    private fun loadFilters() {
        viewModelScope.launch {
            val result = repository.getFilters()
            result.onSuccess { response ->
                _uiState.update { 
                    it.copy(
                        offers = response.offers,
                        affiliates = response.affiliates,
                        countries = response.countries
                    ) 
                }
            }
        }
    }

    fun loadReport() {
        fetchJob?.cancel()
        fetchJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            val state = _uiState.value
            val (from, to) = state.dateRangeState.getFormattedDates()
            val result = repository.getReports(
                tab = state.tab,
                from = from,
                to = to,
                groupBy = state.groupBy,
                offerId = state.offerId,
                affiliateId = state.affiliateId,
                country = state.country,
                sub1 = state.sub1.ifBlank { null },
                slId = state.slId
            )
            result.onSuccess { response ->
                _uiState.update { 
                    it.copy(
                        isLoading = false,
                        totals = response.totals,
                        rows = response.rows
                    ) 
                }
            }.onFailure { e ->
                _uiState.update { it.copy(isLoading = false, error = e.message ?: "Failed to load report") }
            }
        }
    }

    fun setDateRangeOption(option: DateRangeOption) {
        val newState = _uiState.value.dateRangeState.copy(option = option)
        _uiState.update { it.copy(dateRangeState = newState) }
        dateRangePrefManager.saveDateRangeState("admin_reports", newState)
        loadReport()
    }

    fun setCustomDateRange(startDate: String, endDate: String) {
        val newState = _uiState.value.dateRangeState.copy(
            option = DateRangeOption.CUSTOM,
            customStartDate = startDate,
            customEndDate = endDate
        )
        _uiState.update { it.copy(dateRangeState = newState) }
        dateRangePrefManager.saveDateRangeState("admin_reports", newState)
        loadReport()
    }

    fun updateTab(tab: String) {
        _uiState.update { it.copy(tab = tab) }
        loadReport()
    }

    fun updateFilter(
        groupBy: String? = null,
        offerId: Int? = null,
        affiliateId: Int? = null,
        country: String? = null,
        sub1: String? = null,
        slId: Int? = null,
        clearOffer: Boolean = false,
        clearAffiliate: Boolean = false,
        clearCountry: Boolean = false,
        clearSlId: Boolean = false
    ) {
        _uiState.update { state ->
            state.copy(
                groupBy = groupBy ?: state.groupBy,
                offerId = if (clearOffer) null else (offerId ?: state.offerId),
                affiliateId = if (clearAffiliate) null else (affiliateId ?: state.affiliateId),
                country = if (clearCountry) null else (country ?: state.country),
                sub1 = sub1 ?: state.sub1,
                slId = if (clearSlId) null else (slId ?: state.slId)
            )
        }
        loadReport()
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }
}
