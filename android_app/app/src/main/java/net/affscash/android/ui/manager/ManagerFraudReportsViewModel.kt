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
    
    // Filters
    val fromDate: String = "",
    val toDate: String = "",
    val clickId: String = "",
    val statusFilter: String = "All Statuses",
    val affiliate: String = "All Affiliates",
    val affCode: String = "",
    val offer: String = "All Offers",
    val scoreMin: String = "",
    val scoreMax: String = "",
    val sortBy: String = "Date Desc"
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

    fun updateFilters(
        fromDate: String = _uiState.value.fromDate,
        toDate: String = _uiState.value.toDate,
        clickId: String = _uiState.value.clickId,
        statusFilter: String = _uiState.value.statusFilter,
        affiliate: String = _uiState.value.affiliate,
        affCode: String = _uiState.value.affCode,
        offer: String = _uiState.value.offer,
        scoreMin: String = _uiState.value.scoreMin,
        scoreMax: String = _uiState.value.scoreMax,
        sortBy: String = _uiState.value.sortBy
    ) {
        _uiState.update { 
            it.copy(
                fromDate = fromDate, toDate = toDate, clickId = clickId, 
                statusFilter = statusFilter, affiliate = affiliate, affCode = affCode, 
                offer = offer, scoreMin = scoreMin, scoreMax = scoreMax, sortBy = sortBy
            )
        }
    }
}
