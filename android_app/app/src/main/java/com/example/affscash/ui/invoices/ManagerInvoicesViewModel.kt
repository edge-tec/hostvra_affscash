package com.example.affscash.ui.invoices

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.ManagerInvoicesResponse
import com.example.affscash.data.repository.InvoiceRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class ManagerInvoicesUiState {
    object Loading : ManagerInvoicesUiState()
    data class Success(val data: ManagerInvoicesResponse) : ManagerInvoicesUiState()
    data class Error(val message: String) : ManagerInvoicesUiState()
}

@HiltViewModel
class ManagerInvoicesViewModel @Inject constructor(
    private val repository: InvoiceRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<ManagerInvoicesUiState>(ManagerInvoicesUiState.Loading)
    val uiState: StateFlow<ManagerInvoicesUiState> = _uiState.asStateFlow()
    
    private val _selectedTab = MutableStateFlow(0) // 0: Affiliate Invoices, 1: My Invoices
    val selectedTab: StateFlow<Int> = _selectedTab.asStateFlow()
    
    private val _searchQuery = MutableStateFlow("")
    val searchQuery: StateFlow<String> = _searchQuery.asStateFlow()

    init {
        loadInvoices()
    }

    fun loadInvoices() {
        viewModelScope.launch {
            _uiState.value = ManagerInvoicesUiState.Loading
            val result = repository.getManagerInvoices()
            result.onSuccess {
                _uiState.value = ManagerInvoicesUiState.Success(it)
            }.onFailure {
                _uiState.value = ManagerInvoicesUiState.Error(it.message ?: "Failed to load invoices")
            }
        }
    }
    
    fun setTab(index: Int) {
        _selectedTab.value = index
    }
    
    fun setSearchQuery(query: String) {
        _searchQuery.value = query
    }
}
