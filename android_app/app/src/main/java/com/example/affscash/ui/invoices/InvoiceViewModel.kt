package com.example.affscash.ui.invoices

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.Invoice
import com.example.affscash.data.repository.InvoiceRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class InvoiceState {
    object Loading : InvoiceState()
    data class Success(val invoices: List<Invoice>) : InvoiceState()
    data class Error(val message: String) : InvoiceState()
}

@HiltViewModel
class InvoiceViewModel @Inject constructor(
    private val repository: InvoiceRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<InvoiceState>(InvoiceState.Loading)
    val uiState: StateFlow<InvoiceState> = _uiState

    init {
        loadInvoices()
    }

    fun loadInvoices() {
        viewModelScope.launch {
            _uiState.value = InvoiceState.Loading
            val result = repository.getInvoices()
            result.onSuccess { res ->
                _uiState.value = InvoiceState.Success(res.data)
            }.onFailure {
                _uiState.value = InvoiceState.Error(it.message ?: "Failed to load invoices")
            }
        }
    }

    fun downloadPdf(invoiceId: Int, onSuccess: (String) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            val result = repository.downloadInvoicePdf(invoiceId)
            result.onSuccess { res ->
                res.url?.let { onSuccess(it) } ?: onError("PDF URL not found")
            }.onFailure {
                onError(it.message ?: "Failed to get PDF")
            }
        }
    }
}
