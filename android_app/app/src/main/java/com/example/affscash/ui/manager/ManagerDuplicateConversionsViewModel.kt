package com.example.affscash.ui.manager

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.DuplicateConversionsResponse
import com.example.affscash.data.repository.ManagerReportRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale
import javax.inject.Inject

data class ManagerDuplicateConversionsUiState(
    val isLoading: Boolean = false,
    val response: DuplicateConversionsResponse? = null,
    val error: String? = null,
    val fromDate: String = "",
    val toDate: String = ""
)

@HiltViewModel
class ManagerDuplicateConversionsViewModel @Inject constructor(
    private val repository: ManagerReportRepository
) : ViewModel() {
    private val _uiState = MutableStateFlow(ManagerDuplicateConversionsUiState())
    val uiState: StateFlow<ManagerDuplicateConversionsUiState> = _uiState.asStateFlow()

    init {
        val dateFormat = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault())
        val cal = Calendar.getInstance()
        val toDateStr = dateFormat.format(cal.time)
        cal.set(Calendar.DAY_OF_MONTH, 1)
        val fromDateStr = dateFormat.format(cal.time)

        _uiState.update { it.copy(fromDate = fromDateStr, toDate = toDateStr) }
        loadReport()
    }

    fun loadReport() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            val state = _uiState.value
            repository.getManagerDuplicateConversions(
                from = state.fromDate,
                to = state.toDate
            ).collect { result ->
                result.onSuccess { response ->
                    _uiState.update { it.copy(isLoading = false, response = response) }
                }
                result.onFailure { exception ->
                    _uiState.update { it.copy(isLoading = false, error = exception.message) }
                }
            }
        }
    }

    fun setDateRange(from: String, to: String) {
        _uiState.update { it.copy(fromDate = from, toDate = to) }
        loadReport()
    }
}
