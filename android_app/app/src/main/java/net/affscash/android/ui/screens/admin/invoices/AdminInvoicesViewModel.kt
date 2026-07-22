package net.affscash.android.ui.screens.admin.invoices

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.AdminInvoiceDetail
import net.affscash.android.data.model.AdminInvoiceRequestRow
import net.affscash.android.data.model.AdminInvoiceRow
import net.affscash.android.data.repository.AdminInvoiceRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class AdminInvoicesUiState(
    val isLoading: Boolean = false,
    val selectedTab: Int = 0, // 0 = Invoices, 1 = Requests
    val invoices: List<AdminInvoiceRow> = emptyList(),
    val requests: List<AdminInvoiceRequestRow> = emptyList(),
    val error: String? = null,
    val successMessage: String? = null,
    val invoiceDetail: AdminInvoiceDetail? = null,
    val isDetailLoading: Boolean = false
)

@HiltViewModel
class AdminInvoicesViewModel @Inject constructor(private val repository: AdminInvoiceRepository) : ViewModel() {
    private val _uiState = MutableStateFlow(AdminInvoicesUiState())
    val uiState: StateFlow<AdminInvoicesUiState> = _uiState.asStateFlow()

    init {
        loadInvoices()
        loadRequests()
    }

    fun setTab(tab: Int) {
        _uiState.update { it.copy(selectedTab = tab) }
        if (tab == 0) loadInvoices() else loadRequests()
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

    fun loadRequests() {
        _uiState.update { it.copy(isLoading = true, error = null) }
        viewModelScope.launch {
            val result = repository.getInvoiceRequests()
            if (result.isSuccess) {
                _uiState.update { it.copy(isLoading = false, requests = result.getOrNull() ?: emptyList()) }
            } else {
                _uiState.update { it.copy(isLoading = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    fun approveRequest(requestId: Int, amount: Double? = null, adminNote: String? = null) {
        _uiState.update { it.copy(isLoading = true, error = null) }
        viewModelScope.launch {
            val result = repository.approveInvoiceRequest(requestId, amount, adminNote)
            if (result.isSuccess) {
                _uiState.update { it.copy(isLoading = false, successMessage = result.getOrNull()) }
                loadRequests()
                loadInvoices()
            } else {
                _uiState.update { it.copy(isLoading = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    fun rejectRequest(requestId: Int, adminNote: String? = null) {
        _uiState.update { it.copy(isLoading = true, error = null) }
        viewModelScope.launch {
            val result = repository.rejectInvoiceRequest(requestId, adminNote)
            if (result.isSuccess) {
                _uiState.update { it.copy(isLoading = false, successMessage = result.getOrNull()) }
                loadRequests()
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

    fun clearMessages() {
        _uiState.update { it.copy(error = null, successMessage = null) }
    }
}
