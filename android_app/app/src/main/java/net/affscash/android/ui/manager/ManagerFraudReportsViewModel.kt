package net.affscash.android.ui.manager

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.ManagerFraudReportResponse
import net.affscash.android.data.repository.ManagerReportRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class ManagerFraudReportsUiState(
    val isLoading: Boolean = false,
    val response: ManagerFraudReportResponse? = null,
    val error: String? = null,
    val searchQuery: String = "",
    val statusFilter: String = "All Statuses"
)

@HiltViewModel
class ManagerFraudReportsViewModel @Inject constructor(
    private val repository: ManagerReportRepository
) : ViewModel() {
    private val _uiState = MutableStateFlow(ManagerFraudReportsUiState())
    val uiState: StateFlow<ManagerFraudReportsUiState> = _uiState.asStateFlow()

    init {
        loadReport()
    }

    fun loadReport() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            repository.getManagerFraudReport().collect { result ->
                result.onSuccess { response ->
                    _uiState.update { it.copy(isLoading = false, response = response) }
                }
                result.onFailure { exception ->
                    _uiState.update { it.copy(isLoading = false, error = exception.message) }
                }
            }
        }
    }

    fun setSearchQuery(query: String) {
        _uiState.update { it.copy(searchQuery = query) }
    }

    fun setStatusFilter(status: String) {
        _uiState.update { it.copy(statusFilter = status) }
    }
}
