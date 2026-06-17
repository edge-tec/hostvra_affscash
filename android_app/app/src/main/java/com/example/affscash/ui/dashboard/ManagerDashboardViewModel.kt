package com.example.affscash.ui.dashboard

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.ManagerDashboardData
import com.example.affscash.data.model.ManagerFiltersData
import com.example.affscash.data.model.ManagerTrendData
import com.example.affscash.data.repository.DashboardRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale
import javax.inject.Inject

data class ManagerDashboardState(
    val isLoadingStats: Boolean = false,
    val isLoadingTrend: Boolean = false,
    val stats: ManagerDashboardData? = null,
    val trend: ManagerTrendData? = null,
    val filtersData: ManagerFiltersData? = null,
    val error: String? = null,
    
    // Active Filters
    val selectedPeriod: String = "30d",
    val fromDate: String = "",
    val toDate: String = "",
    val offerId: Int? = null,
    val affiliateId: Int? = null,
    val country: String? = null,
    val device: String? = null
)

@HiltViewModel
class ManagerDashboardViewModel @Inject constructor(
    private val dashboardRepository: DashboardRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(ManagerDashboardState())
    val uiState: StateFlow<ManagerDashboardState> = _uiState.asStateFlow()

    init {
        setPeriod("30d") // Will trigger loadStats and loadTrend
        loadFilters()
    }

    private fun loadFilters() {
        viewModelScope.launch {
            val result = dashboardRepository.getManagerFilters()
            if (result.isSuccess) {
                _uiState.update { it.copy(filtersData = result.getOrNull()?.data) }
            }
        }
    }

    private fun loadStats() {
        val state = _uiState.value
        _uiState.update { it.copy(isLoadingStats = true, error = null) }
        viewModelScope.launch {
            val result = dashboardRepository.getManagerStats(
                from = state.fromDate,
                to = state.toDate,
                offerId = state.offerId,
                affiliateId = state.affiliateId,
                country = state.country,
                device = state.device
            )
            if (result.isSuccess) {
                _uiState.update { it.copy(isLoadingStats = false, stats = result.getOrNull()?.data) }
            } else {
                _uiState.update { it.copy(isLoadingStats = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    private fun loadTrend() {
        val state = _uiState.value
        _uiState.update { it.copy(isLoadingTrend = true, error = null) }
        viewModelScope.launch {
            val result = dashboardRepository.getManagerTrend(
                from = state.fromDate,
                to = state.toDate,
                offerId = state.offerId,
                affiliateId = state.affiliateId,
                country = state.country,
                device = state.device
            )
            if (result.isSuccess) {
                _uiState.update { it.copy(isLoadingTrend = false, trend = result.getOrNull()?.data) }
            } else {
                _uiState.update { it.copy(isLoadingTrend = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    fun setPeriod(period: String) {
        val cal = Calendar.getInstance()
        val format = SimpleDateFormat("yyyy-MM-dd", Locale.US)
        val today = format.format(cal.time)
        
        var from = today
        var to = today

        when (period) {
            "today" -> {}
            "yesterday" -> {
                cal.add(Calendar.DAY_OF_YEAR, -1)
                from = format.format(cal.time)
                to = format.format(cal.time)
            }
            "7d" -> {
                cal.add(Calendar.DAY_OF_YEAR, -6)
                from = format.format(cal.time)
            }
            "15d" -> {
                cal.add(Calendar.DAY_OF_YEAR, -14)
                from = format.format(cal.time)
            }
            "30d" -> {
                cal.add(Calendar.DAY_OF_YEAR, -29)
                from = format.format(cal.time)
            }
            "90d" -> {
                cal.add(Calendar.DAY_OF_YEAR, -89)
                from = format.format(cal.time)
            }
            "mtd" -> {
                cal.set(Calendar.DAY_OF_MONTH, 1)
                from = format.format(cal.time)
            }
            "lastmonth" -> {
                cal.add(Calendar.MONTH, -1)
                cal.set(Calendar.DAY_OF_MONTH, 1)
                from = format.format(cal.time)
                cal.set(Calendar.DAY_OF_MONTH, cal.getActualMaximum(Calendar.DAY_OF_MONTH))
                to = format.format(cal.time)
            }
        }

        _uiState.update { 
            it.copy(
                selectedPeriod = period,
                fromDate = from,
                toDate = to
            ) 
        }
        loadStats()
        loadTrend()
    }

    fun applyFilters(offerId: Int?, affiliateId: Int?, country: String?, device: String?) {
        _uiState.update { 
            it.copy(
                offerId = offerId,
                affiliateId = affiliateId,
                country = country,
                device = device
            ) 
        }
        loadStats()
        loadTrend()
    }

    fun resetFilters() {
        _uiState.update { 
            it.copy(
                offerId = null,
                affiliateId = null,
                country = null,
                device = null
            ) 
        }
        setPeriod("30d")
    }
}
