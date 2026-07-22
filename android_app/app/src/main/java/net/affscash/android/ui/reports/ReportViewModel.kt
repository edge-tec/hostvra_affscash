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
import net.affscash.android.data.model.OfferItem
import net.affscash.android.data.model.ReportResponse
import net.affscash.android.data.repository.ReportRepository
import javax.inject.Inject

sealed class ReportState {
    object Loading : ReportState()
    data class Success(val response: ReportResponse) : ReportState()
    data class Error(val message: String) : ReportState()
}

@HiltViewModel
class ReportViewModel @Inject constructor(
    private val repository: ReportRepository,
    private val dateRangePrefManager: DateRangePreferenceManager
) : ViewModel() {

    private val _uiState = MutableStateFlow<ReportState>(ReportState.Loading)
    val uiState: StateFlow<ReportState> = _uiState

    val availableOffers = MutableStateFlow<List<OfferItem>>(emptyList())
    val availableCountries = MutableStateFlow<List<String>>(emptyList())
    val availableCities = MutableStateFlow<List<String>>(emptyList())

    val selectedTab = MutableStateFlow("day")

    private val _dateRangeState = MutableStateFlow(dateRangePrefManager.getDateRangeState("affiliate_reports"))
    val dateRangeState: StateFlow<DateRangeState> = _dateRangeState.asStateFlow()

    val selectedOfferId = MutableStateFlow<Int?>(null)
    val selectedCountry = MutableStateFlow<String?>(null)
    val selectedCity = MutableStateFlow<String?>(null)
    val sub1Filter = MutableStateFlow("")

    private var fetchJob: Job? = null

    init {
        loadFilters()
        loadReports()
    }

    private fun loadFilters() {
        viewModelScope.launch {
            val result = repository.getReportFilters()
            result.onSuccess { res ->
                availableOffers.value = res.offers
                availableCountries.value = res.countries
                availableCities.value = res.cities
            }
        }
    }

    fun loadReports() {
        fetchJob?.cancel()
        fetchJob = viewModelScope.launch {
            _uiState.value = ReportState.Loading
            val (from, to) = _dateRangeState.value.getFormattedDates()
            val result = repository.getReports(
                tab = selectedTab.value,
                from = from,
                to = to,
                offerId = selectedOfferId.value,
                country = selectedCountry.value,
                city = selectedCity.value,
                sub1 = sub1Filter.value.takeIf { it.isNotBlank() }
            )
            result.onSuccess {
                _uiState.value = ReportState.Success(it)
            }.onFailure {
                _uiState.value = ReportState.Error(it.message ?: "Failed to load reports")
            }
        }
    }

    fun setDateRangeOption(option: DateRangeOption) {
        val newState = _dateRangeState.value.copy(option = option)
        _dateRangeState.value = newState
        dateRangePrefManager.saveDateRangeState("affiliate_reports", newState)
        loadReports()
    }

    fun setCustomDateRange(startDate: String, endDate: String) {
        val newState = _dateRangeState.value.copy(
            option = DateRangeOption.CUSTOM,
            customStartDate = startDate,
            customEndDate = endDate
        )
        _dateRangeState.value = newState
        dateRangePrefManager.saveDateRangeState("affiliate_reports", newState)
        loadReports()
    }

    fun updateTab(tab: String) {
        if (selectedTab.value != tab) {
            selectedTab.value = tab
            loadReports()
        }
    }
}
