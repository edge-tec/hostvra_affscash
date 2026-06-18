package net.affscash.android.ui.admin.shop

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.AdminShopDashboardData
import net.affscash.android.data.repository.AdminShopRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class AdminShopUiState {
    object Loading : AdminShopUiState()
    data class Success(val data: AdminShopDashboardData) : AdminShopUiState()
    data class Error(val message: String) : AdminShopUiState()
}

@HiltViewModel
class AdminShopViewModel @Inject constructor(
    private val repository: AdminShopRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminShopUiState>(AdminShopUiState.Loading)
    val uiState: StateFlow<AdminShopUiState> = _uiState.asStateFlow()

    private val _actionMessage = MutableStateFlow<String?>(null)
    val actionMessage: StateFlow<String?> = _actionMessage.asStateFlow()

    fun loadDashboard() {
        viewModelScope.launch {
            _uiState.value = AdminShopUiState.Loading
            repository.getShopDashboard()
                .onSuccess { response ->
                    response.data?.let {
                        _uiState.value = AdminShopUiState.Success(it)
                    } ?: run {
                        _uiState.value = AdminShopUiState.Error("No data found")
                    }
                }
                .onFailure {
                    _uiState.value = AdminShopUiState.Error(it.message ?: "Failed to load dashboard")
                }
        }
    }

    fun deleteProduct(id: Int) {
        viewModelScope.launch {
            repository.deleteShopProduct(id)
                .onSuccess {
                    _actionMessage.value = "Product deleted successfully"
                    loadDashboard()
                }
                .onFailure {
                    _actionMessage.value = "Failed to delete product: ${it.message}"
                }
        }
    }

    fun clearActionMessage() {
        _actionMessage.value = null
    }
}
