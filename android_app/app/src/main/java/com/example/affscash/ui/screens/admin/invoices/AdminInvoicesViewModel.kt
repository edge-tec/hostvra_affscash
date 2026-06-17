package com.example.affscash.ui.screens.admin.invoices

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.AdminInvoiceDetail
import com.example.affscash.data.model.AdminInvoiceRow
import com.example.affscash.data.repository.AdminInvoiceRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class AdminInvoicesUiState(
    val isLoading: Boolean = false,
    val invoices: List<AdminInvoiceRow> = emptyList(),
    val error: String? = null,
    val invoiceDetail: AdminInvoiceDetail? = null,
    val isDetailLoading: Boolean = false
)

class AdminInvoicesViewModel(private val repository: AdminInvoiceRepository) : ViewModel() {
    private val _uiState = MutableStateFlow(AdminInvoicesUiState())
    val uiState: StateFlow<AdminInvoicesUiState> = _uiState.asStateFlow()

    init {
        loadInvoices()
    }

    fun loadInvoices() {
        _uiState.update { it.copy(isLoading = true, error = null) }
        viewModelScope.launch {
            val result = repository.getInvoices()
            if (result.isSuccess) {
                _uiState.update { it.copy(isLoading = false, invoices = result.getOrNull() ?: emptyList()) }
            } else {
                _uiState.update { it.copy(isLoading = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    fun viewInvoiceDetails(id: Int) {
        _uiState.update { it.copy(isDetailLoading = true, error = null) }
        viewModelScope.launch {
            val result = repository.getInvoiceDetail(id)
            if (result.isSuccess) {
                _uiState.update { it.copy(isDetailLoading = false, invoiceDetail = result.getOrNull()) }
            } else {
                _uiState.update { it.copy(isDetailLoading = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    fun closeDetails() {
        _uiState.update { it.copy(invoiceDetail = null) }
    }

    fun updateInvoiceStatus(id: Int, newStatus: String) {
        _uiState.update { it.copy(isLoading = true) }
        viewModelScope.launch {
            val result = repository.updateInvoiceStatus(id, newStatus)
            if (result.isSuccess) {
                // Also update the detail view if it's currently open
                if (_uiState.value.invoiceDetail?.id == id) {
                    _uiState.update { it.copy(invoiceDetail = it.invoiceDetail?.copy(status = newStatus)) }
                }
                loadInvoices()
            } else {
                _uiState.update { it.copy(isLoading = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    fun deleteInvoice(id: Int) {
        _uiState.update { it.copy(isLoading = true) }
        viewModelScope.launch {
            val result = repository.deleteInvoice(id)
            if (result.isSuccess) {
                if (_uiState.value.invoiceDetail?.id == id) {
                    closeDetails()
                }
                loadInvoices()
            } else {
                _uiState.update { it.copy(isLoading = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }
}
