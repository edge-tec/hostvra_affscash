package net.affscash.android.ui.admin.shop

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.AdminShopOrder
import net.affscash.android.data.model.AdminShopOrderUpdateRequest
import net.affscash.android.data.repository.AdminShopRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class AdminShopOrdersUiState {
    object Loading : AdminShopOrdersUiState()
    data class Success(val orders: List<AdminShopOrder>) : AdminShopOrdersUiState()
    data class Error(val message: String) : AdminShopOrdersUiState()
}

@HiltViewModel
class AdminShopOrdersViewModel @Inject constructor(
    private val repository: AdminShopRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminShopOrdersUiState>(AdminShopOrdersUiState.Loading)
    val uiState: StateFlow<AdminShopOrdersUiState> = _uiState.asStateFlow()

    private val _actionMessage = MutableStateFlow<String?>(null)
    val actionMessage: StateFlow<String?> = _actionMessage.asStateFlow()

    init {
        loadOrders()
    }

    fun loadOrders(status: String? = null) {
        viewModelScope.launch {
            _uiState.value = AdminShopOrdersUiState.Loading
            repository.getShopOrders(status)
                .onSuccess { response ->
                    _uiState.value = AdminShopOrdersUiState.Success(response.data?.orders ?: emptyList())
                }
                .onFailure {
                    _uiState.value = AdminShopOrdersUiState.Error(it.message ?: "Failed to load orders")
                }
        }
    }

    fun updateOrderStatus(orderId: Int, status: String, trackingCode: String?, adminNote: String?) {
        viewModelScope.launch {
            val request = AdminShopOrderUpdateRequest(
                orderId = orderId,
                status = status,
                trackingCode = trackingCode,
                adminNote = adminNote
            )
            repository.updateShopOrder(request)
                .onSuccess {
                    _actionMessage.value = "Order updated successfully"
                    loadOrders()
                }
                .onFailure {
                    _actionMessage.value = "Failed to update order: ${it.message}"
                }
        }
    }

    fun clearActionMessage() {
        _actionMessage.value = null
    }
}
