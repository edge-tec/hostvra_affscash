package com.example.affscash.ui.dashboard

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.*
import com.example.affscash.data.repository.DashboardRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.async
import kotlinx.coroutines.awaitAll
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale
import javax.inject.Inject

data class DashboardAnalyticsData(
    val dashboardResponse: DashboardResponse,
    val stats: DashboardAnalyticsStatsResponse,
    val trend: DashboardTrendChartResponse,
    val devices: DashboardPieChartResponse,
    val browsers: DashboardPieChartResponse,
    val sources: DashboardPieChartResponse,
    val hourly: DashboardHourlyResponse,
    val countries: DashboardCountriesResponse,
    val offers: DashboardOffersResponse
)

sealed class DashboardState {
    object Loading : DashboardState()
    data class Success(val data: DashboardAnalyticsData, val currentFilter: String) : DashboardState()
    data class Error(val message: String) : DashboardState()
}

@HiltViewModel
class DashboardViewModel @Inject constructor(
    private val dashboardRepository: DashboardRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<DashboardState>(DashboardState.Loading)
    val uiState: StateFlow<DashboardState> = _uiState

    private val _filterOption = MutableStateFlow("30D")
    val filterOption: StateFlow<String> = _filterOption

    private var pollingJob: kotlinx.coroutines.Job? = null

    init {
        loadDashboardData()
        startPolling()
    }

    private fun startPolling() {
        pollingJob = viewModelScope.launch {
            while (true) {
                kotlinx.coroutines.delay(60000) // Poll every 60 seconds
                loadDashboardData(isRefresh = true)
            }
        }
    }

    fun setFilterOption(option: String) {
        if (_filterOption.value != option) {
            _filterOption.value = option
            loadDashboardData()
        }
    }

    private fun getDateRange(option: String): Pair<String, String> {
        val format = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault())
        val cal = Calendar.getInstance()
        val to = format.format(cal.time)
        when (option) {
            "Today" -> {
                // from is same as to
            }
            "Yesterday" -> {
                cal.add(Calendar.DAY_OF_YEAR, -1)
                return Pair(format.format(cal.time), format.format(cal.time))
            }
            "7D" -> cal.add(Calendar.DAY_OF_YEAR, -6)
            "30D" -> cal.add(Calendar.DAY_OF_YEAR, -29)
            "This Month" -> cal.set(Calendar.DAY_OF_MONTH, 1)
            "Last Month" -> {
                cal.add(Calendar.MONTH, -1)
                val lastMonthCal = cal.clone() as Calendar
                lastMonthCal.set(Calendar.DAY_OF_MONTH, 1)
                val fromStr = format.format(lastMonthCal.time)
                lastMonthCal.set(Calendar.DAY_OF_MONTH, lastMonthCal.getActualMaximum(Calendar.DAY_OF_MONTH))
                return Pair(fromStr, format.format(lastMonthCal.time))
            }
            else -> cal.add(Calendar.DAY_OF_YEAR, -29)
        }
        return Pair(format.format(cal.time), to)
    }

    fun loadDashboardData(isRefresh: Boolean = false) {
        viewModelScope.launch {
            if (!isRefresh) _uiState.value = DashboardState.Loading

            val (from, to) = getDateRange(_filterOption.value)

            val baseJob = async { dashboardRepository.getDashboardData() }
            val statsJob = async { dashboardRepository.getAffiliateStats(from, to) }
            val trendJob = async { dashboardRepository.getAffiliateTrend(from, to) }
            val devicesJob = async { dashboardRepository.getAffiliateDevices(from, to) }
            val browsersJob = async { dashboardRepository.getAffiliateBrowsers(from, to) }
            val sourcesJob = async { dashboardRepository.getAffiliateSources(from, to) }
            val hourlyJob = async { dashboardRepository.getAffiliateHourly(from, to) }
            val countriesJob = async { dashboardRepository.getAffiliateCountries(from, to) }
            val offersJob = async { dashboardRepository.getAffiliateOffers(from, to) }

            val baseRes = baseJob.await()
            val statsRes = statsJob.await()
            val trendRes = trendJob.await()
            val devicesRes = devicesJob.await()
            val browsersRes = browsersJob.await()
            val sourcesRes = sourcesJob.await()
            val hourlyRes = hourlyJob.await()
            val countriesRes = countriesJob.await()
            val offersRes = offersJob.await()

            if (baseRes.isSuccess && statsRes.isSuccess) {
                val analyticsData = DashboardAnalyticsData(
                    dashboardResponse = baseRes.getOrNull()!!,
                    stats = statsRes.getOrNull()!!,
                    trend = trendRes.getOrNull() ?: DashboardTrendChartResponse(),
                    devices = devicesRes.getOrNull() ?: DashboardPieChartResponse(),
                    browsers = browsersRes.getOrNull() ?: DashboardPieChartResponse(),
                    sources = sourcesRes.getOrNull() ?: DashboardPieChartResponse(),
                    hourly = hourlyRes.getOrNull() ?: DashboardHourlyResponse(),
                    countries = countriesRes.getOrNull() ?: DashboardCountriesResponse(),
                    offers = offersRes.getOrNull() ?: DashboardOffersResponse()
                )
                _uiState.value = DashboardState.Success(analyticsData, _filterOption.value)
            } else {
                if (!isRefresh) {
                    val errorMsg = baseRes.exceptionOrNull()?.message ?: statsRes.exceptionOrNull()?.message ?: "Failed to load dashboard"
                    _uiState.value = DashboardState.Error(errorMsg)
                }
            }
        }
    }
}
