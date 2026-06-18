package net.affscash.android.ui.dashboard

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.AdminDashboardResponse
import net.affscash.android.data.repository.DashboardRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

enum class DateFilter(val label: String) {
    TODAY("Today"),
    YESTERDAY("Yesterday"),
    SEVEN_DAYS("7D"),
    FIFTEEN_DAYS("Last 15D"),
    THIRTY_DAYS("30D"),
    NINETY_DAYS("90D"),
    THIS_MONTH("This Month"),
    LAST_MONTH("Last Month")
}

sealed class AdminDashboardUiState {
    object Loading : AdminDashboardUiState()
    data class Success(val data: AdminDashboardResponse) : AdminDashboardUiState()
    data class Error(val message: String) : AdminDashboardUiState()
}

@HiltViewModel
class AdminDashboardViewModel @Inject constructor(
    private val repository: DashboardRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminDashboardUiState>(AdminDashboardUiState.Loading)
    val uiState: StateFlow<AdminDashboardUiState> = _uiState.asStateFlow()

    private val _selectedFilter = MutableStateFlow(DateFilter.THIRTY_DAYS)
    val selectedFilter: StateFlow<DateFilter> = _selectedFilter.asStateFlow()

    init {
        loadDashboardData()
    }

    fun setFilter(filter: DateFilter) {
        _selectedFilter.value = filter
        loadDashboardData()
    }

    private fun getDatesForFilter(filter: DateFilter): Pair<String, String> {
        val format = java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.getDefault())
        val cal = java.util.Calendar.getInstance()
        var to = format.format(cal.time)
        var from = to

        when (filter) {
            DateFilter.TODAY -> {
                from = to
            }
            DateFilter.YESTERDAY -> {
                cal.add(java.util.Calendar.DAY_OF_YEAR, -1)
                from = format.format(cal.time)
                to = from
            }
            DateFilter.SEVEN_DAYS -> {
                cal.add(java.util.Calendar.DAY_OF_YEAR, -6)
                from = format.format(cal.time)
            }
            DateFilter.FIFTEEN_DAYS -> {
                cal.add(java.util.Calendar.DAY_OF_YEAR, -14)
                from = format.format(cal.time)
            }
            DateFilter.THIRTY_DAYS -> {
                cal.add(java.util.Calendar.DAY_OF_YEAR, -29)
                from = format.format(cal.time)
            }
            DateFilter.NINETY_DAYS -> {
                cal.add(java.util.Calendar.DAY_OF_YEAR, -89)
                from = format.format(cal.time)
            }
            DateFilter.THIS_MONTH -> {
                cal.set(java.util.Calendar.DAY_OF_MONTH, 1)
                from = format.format(cal.time)
            }
            DateFilter.LAST_MONTH -> {
                cal.add(java.util.Calendar.MONTH, -1)
                val lastMonthCal = cal.clone() as java.util.Calendar
                lastMonthCal.set(java.util.Calendar.DAY_OF_MONTH, lastMonthCal.getActualMaximum(java.util.Calendar.DAY_OF_MONTH))
                to = format.format(lastMonthCal.time)
                
                cal.set(java.util.Calendar.DAY_OF_MONTH, 1)
                from = format.format(cal.time)
            }
        }
        return Pair(from, to)
    }

    fun loadDashboardData() {
        viewModelScope.launch {
            _uiState.value = AdminDashboardUiState.Loading
            val (from, to) = getDatesForFilter(_selectedFilter.value)
            repository.getAdminDashboardData(from, to)
                .onSuccess { response ->
                    if (response.success && response.data != null) {
                        _uiState.value = AdminDashboardUiState.Success(response)
                    } else {
                        _uiState.value = AdminDashboardUiState.Error(response.error ?: "Failed to fetch dashboard")
                    }
                }
                .onFailure { exception ->
                    _uiState.value = AdminDashboardUiState.Error(exception.message ?: "Unknown error")
                }
        }
    }
}
