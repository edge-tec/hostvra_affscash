package com.example.affscash.ui.dashboard

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.DashboardResponse
import com.example.affscash.data.repository.DashboardRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class DashboardState {
    object Loading : DashboardState()
    data class Success(val data: DashboardResponse) : DashboardState()
    data class Error(val message: String) : DashboardState()
}

@HiltViewModel
class DashboardViewModel @Inject constructor(
    private val dashboardRepository: DashboardRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<DashboardState>(DashboardState.Loading)
    val uiState: StateFlow<DashboardState> = _uiState

    private var pollingJob: kotlinx.coroutines.Job? = null

    init {
        loadDashboardData()
        startPolling()
    }

    private fun startPolling() {
        pollingJob = viewModelScope.launch {
            while (true) {
                kotlinx.coroutines.delay(10000) // Poll every 10 seconds
                loadDashboardData(isRefresh = true)
            }
        }
    }

    fun loadDashboardData(isRefresh: Boolean = false) {
        viewModelScope.launch {
            if (!isRefresh) _uiState.value = DashboardState.Loading
            val result = dashboardRepository.getDashboardData()
            result.onSuccess {
                _uiState.value = DashboardState.Success(it)
            }.onFailure {
                if (!isRefresh) _uiState.value = DashboardState.Error(it.message ?: "Failed to load dashboard")
            }
        }
    }
}
