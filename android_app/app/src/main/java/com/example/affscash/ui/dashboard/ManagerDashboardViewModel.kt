package com.example.affscash.ui.dashboard

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.ManagerDashboardResponse
import com.example.affscash.data.repository.DashboardRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class ManagerDashboardUiState {
    object Loading : ManagerDashboardUiState()
    data class Success(val data: ManagerDashboardResponse) : ManagerDashboardUiState()
    data class Error(val message: String) : ManagerDashboardUiState()
}

@HiltViewModel
class ManagerDashboardViewModel @Inject constructor(
    private val repository: DashboardRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<ManagerDashboardUiState>(ManagerDashboardUiState.Loading)
    val uiState: StateFlow<ManagerDashboardUiState> = _uiState.asStateFlow()

    init {
        loadDashboardData()
    }

    fun loadDashboardData() {
        viewModelScope.launch {
            _uiState.value = ManagerDashboardUiState.Loading
            repository.getManagerDashboardData()
                .onSuccess { response ->
                    _uiState.value = ManagerDashboardUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = ManagerDashboardUiState.Error(exception.message ?: "Unknown error")
                }
        }
    }
}
