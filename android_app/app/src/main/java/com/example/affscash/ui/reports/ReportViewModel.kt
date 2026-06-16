package com.example.affscash.ui.reports

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.OfferItem
import com.example.affscash.data.model.ReportResponse
import com.example.affscash.data.repository.ReportRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale
import javax.inject.Inject

sealed class ReportState {
    object Loading : ReportState()
    data class Success(val response: ReportResponse) : ReportState()
    data class Error(val message: String) : ReportState()
}

@HiltViewModel
class ReportViewModel @Inject constructor(
    private val repository: ReportRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<ReportState>(ReportState.Loading)
    val uiState: StateFlow<ReportState> = _uiState

    val availableOffers = MutableStateFlow<List<OfferItem>>(emptyList())
    val availableCountries = MutableStateFlow<List<String>>(emptyList())

    val selectedTab = MutableStateFlow("day")
    val fromDate = MutableStateFlow(getFirstDayOfMonth())
    val toDate = MutableStateFlow(getCurrentDate())
    val selectedOfferId = MutableStateFlow<Int?>(null)
    val selectedCountry = MutableStateFlow<String?>(null)
    val sub1Filter = MutableStateFlow("")

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
            }
        }
    }

    fun loadReports() {
        viewModelScope.launch {
            _uiState.value = ReportState.Loading
            val result = repository.getReports(
                tab = selectedTab.value,
                from = fromDate.value,
                to = toDate.value,
                offerId = selectedOfferId.value,
                country = selectedCountry.value,
                sub1 = sub1Filter.value.takeIf { it.isNotBlank() }
            )
            result.onSuccess {
                _uiState.value = ReportState.Success(it)
            }.onFailure {
                _uiState.value = ReportState.Error(it.message ?: "Failed to load reports")
            }
        }
    }

    fun updateTab(tab: String) {
        if (selectedTab.value != tab) {
            selectedTab.value = tab
            loadReports()
        }
    }

    fun setDateRange(from: String, to: String) {
        fromDate.value = from
        toDate.value = to
        loadReports()
    }

    private fun getCurrentDate(): String {
        return SimpleDateFormat("yyyy-MM-dd", Locale.US).format(Calendar.getInstance().time)
    }

    private fun getFirstDayOfMonth(): String {
        val cal = Calendar.getInstance()
        cal.set(Calendar.DAY_OF_MONTH, 1)
        return SimpleDateFormat("yyyy-MM-dd", Locale.US).format(cal.time)
    }
}
