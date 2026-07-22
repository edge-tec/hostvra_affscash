package net.affscash.android.ui.admin.login_activity

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import net.affscash.android.data.model.DateRangeOption
import net.affscash.android.data.model.DateRangeState
import net.affscash.android.data.model.LiveUsersData
import net.affscash.android.data.model.LoginLogsData
import net.affscash.android.data.repository.LoginActivityRepository
import javax.inject.Inject

enum class LoginActivityTab {
    LIVE_USERS,
    LOGIN_LOGS
}

data class LoginActivityUiState(
    val activeTab: LoginActivityTab = LoginActivityTab.LIVE_USERS,
    val isLoading: Boolean = false,
    val liveUsersData: LiveUsersData? = null,
    val loginLogsData: LoginLogsData? = null,
    val error: String? = null,
    val dateRangeState: DateRangeState = DateRangeState(),
    val searchQuery: String = ""
)

@HiltViewModel
class LoginActivityViewModel @Inject constructor(
    private val repository: LoginActivityRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(LoginActivityUiState())
    val uiState: StateFlow<LoginActivityUiState> = _uiState.asStateFlow()

    private var loadJob: Job? = null

    init {
        loadData()
    }

    fun setTab(tab: LoginActivityTab) {
        _uiState.update { it.copy(activeTab = tab) }
        loadData()
    }

    fun loadData() {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            if (_uiState.value.activeTab == LoginActivityTab.LIVE_USERS) {
                val result = repository.getLiveUsers()
                result.onSuccess { response ->
                    _uiState.update { it.copy(isLoading = false, liveUsersData = response.data, error = response.message) }
                }.onFailure { exception ->
                    _uiState.update { it.copy(isLoading = false, error = exception.message ?: "Failed to load live users") }
                }
            } else {
                val (from, to) = _uiState.value.dateRangeState.getFormattedDates()
                val result = repository.getLoginLogs(from, to, _uiState.value.searchQuery)
                result.onSuccess { response ->
                    _uiState.update { it.copy(isLoading = false, loginLogsData = response.data, error = response.message) }
                }.onFailure { exception ->
                    _uiState.update { it.copy(isLoading = false, error = exception.message ?: "Failed to load login activity logs") }
                }
            }
        }
    }

    fun setDateRangeOption(option: DateRangeOption) {
        _uiState.update { current ->
            current.copy(dateRangeState = current.dateRangeState.copy(option = option))
        }
        loadData()
    }

    fun setCustomDateRange(startDate: String, endDate: String) {
        _uiState.update { current ->
            current.copy(
                dateRangeState = current.dateRangeState.copy(
                    option = DateRangeOption.CUSTOM,
                    customStartDate = startDate,
                    customEndDate = endDate
                )
            )
        }
        loadData()
    }

    fun setSearchQuery(query: String) {
        _uiState.update { it.copy(searchQuery = query) }
        loadData()
    }

    fun forceLogoutUser(sessionId: String, userId: Int) {
        viewModelScope.launch {
            repository.forceLogoutUser(sessionId, userId)
            loadData()
        }
    }
}
