package com.example.affscash.ui.invoices

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.InvoiceResponse
import com.example.affscash.data.repository.InvoiceRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class AdminInvoicesUiState {
    object Loading : AdminInvoicesUiState()
    data class Success(val data: InvoiceResponse) : AdminInvoicesUiState()
    data class Error(val message: String) : AdminInvoicesUiState()
}

@HiltViewModel
class AdminInvoicesViewModel @Inject constructor(
    private val repository: InvoiceRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminInvoicesUiState>(AdminInvoicesUiState.Loading)
    val uiState: StateFlow<AdminInvoicesUiState> = _uiState.asStateFlow()

    init {
        loadInvoices()
    }

    fun loadInvoices() {
        viewModelScope.launch {
            _uiState.value = AdminInvoicesUiState.Loading
            val result = repository.getAdminInvoices()
            result.onSuccess {
                _uiState.value = AdminInvoicesUiState.Success(it)
            }.onFailure {
                _uiState.value = AdminInvoicesUiState.Error(it.message ?: "Failed to load invoices")
            }
        }
    }
}
