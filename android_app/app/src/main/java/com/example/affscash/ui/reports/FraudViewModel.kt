package com.example.affscash.ui.reports

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.FraudReportResponse
import com.example.affscash.data.repository.FraudRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class FraudReportState {
    object Loading : FraudReportState()
    data class Success(val data: FraudReportResponse) : FraudReportState()
    data class Error(val message: String) : FraudReportState()
}

@HiltViewModel
class FraudViewModel @Inject constructor(
    private val repository: FraudRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<FraudReportState>(FraudReportState.Loading)
    val uiState: StateFlow<FraudReportState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun loadData() {
        viewModelScope.launch {
            _uiState.value = FraudReportState.Loading
            repository.getFraudReport().fold(
                onSuccess = { response ->
                    if (response.success) {
                        _uiState.value = FraudReportState.Success(response)
                    } else {
                        _uiState.value = FraudReportState.Error(response.error ?: "Failed to load fraud report")
                    }
                },
                onFailure = { error ->
                    _uiState.value = FraudReportState.Error(error.message ?: "Unknown error")
                }
            )
        }
    }
}
