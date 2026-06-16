package com.example.affscash.ui.dashboard

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.AdminDashboardResponse
import com.example.affscash.data.repository.DashboardRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

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

    init {
        loadDashboardData()
    }

    fun loadDashboardData() {
        viewModelScope.launch {
            _uiState.value = AdminDashboardUiState.Loading
            repository.getAdminDashboardData()
                .onSuccess { response ->
                    _uiState.value = AdminDashboardUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = AdminDashboardUiState.Error(exception.message ?: "Unknown error")
                }
        }
    }
}
