package com.example.affscash.ui.reports

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.ReportRow
import com.example.affscash.data.model.ReportTotals
import com.example.affscash.data.repository.ReportRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale
import javax.inject.Inject

sealed class ReportState {
    object Loading : ReportState()
    data class Success(val rows: List<ReportRow>, val totals: ReportTotals?) : ReportState()
    data class Error(val message: String) : ReportState()
}

@HiltViewModel
class ReportViewModel @Inject constructor(
    private val reportRepository: ReportRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<ReportState>(ReportState.Loading)
    val uiState: StateFlow<ReportState> = _uiState

    init {
        loadReports()
    }

    fun loadReports(tab: String = "day") {
        viewModelScope.launch {
            _uiState.value = ReportState.Loading
            
            // Default to current month for fromDate and today for toDate
            val dateFormat = SimpleDateFormat("yyyy-MM-dd", Locale.US)
            val toDate = dateFormat.format(Date())
            
            val calendar = Calendar.getInstance()
            calendar.set(Calendar.DAY_OF_MONTH, 1)
            val fromDate = dateFormat.format(calendar.time)

            val result = reportRepository.getReports(tab, fromDate, toDate)
            result.onSuccess { response ->
                _uiState.value = ReportState.Success(response.rows, response.totals)
            }.onFailure {
                _uiState.value = ReportState.Error(it.message ?: "Failed to load reports")
            }
        }
    }
}
