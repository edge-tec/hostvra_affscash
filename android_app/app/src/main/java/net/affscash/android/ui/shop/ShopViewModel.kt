package net.affscash.android.ui.shop

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.PlaceOrderRequest
import net.affscash.android.data.model.PointsBalance
import net.affscash.android.data.model.ShopOrder
import net.affscash.android.data.model.ShopProduct
import net.affscash.android.data.repository.ShopRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import java.util.UUID
import javax.inject.Inject

data class ShopUiState(
    val isLoading: Boolean = false,
    val balance: PointsBalance? = null,
    val products: List<ShopProduct> = emptyList(),
    val orders: List<ShopOrder> = emptyList(),
    val error: String? = null,
    val actionMessage: String? = null,
    val orderPlaced: Boolean = false
)

@HiltViewModel
class ShopViewModel @Inject constructor(
    private val shopRepository: ShopRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(ShopUiState())
    val uiState: StateFlow<ShopUiState> = _uiState.asStateFlow()

    init {
        loadShopData()
    }

    fun loadShopData() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, error = null, actionMessage = null)
            try {
                val response = shopRepository.getShopData()
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body?.success == true) {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            balance = body.balance,
                            products = body.products,
                            orders = body.orders
                        )
                    } else {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            error = body?.error ?: "Failed to load shop data"
                        )
                    }
                } else {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        error = "Server error: ${response.code()}"
                    )
                }
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = e.localizedMessage ?: "Network error"
                )
            }
        }
    }

    fun placeOrder(productId: Int, recipientName: String, phone: String, email: String, address: String, notes: String) {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, actionMessage = null, error = null)
            val req = PlaceOrderRequest(
                productId = productId,
                idempotencyKey = UUID.randomUUID().toString().replace("-", ""),
                recipientName = recipientName,
                phone = phone,
                email = email,
                address = address,
                notes = notes
            )
            try {
                val response = shopRepository.placeOrder(req)
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body?.success == true) {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            actionMessage = body.message ?: "Order placed successfully!",
                            orderPlaced = true
                        )
                        // Reload data to reflect new balance and order
                        loadShopData()
                    } else {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            error = body?.error ?: "Failed to place order"
                        )
                    }
                } else {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        error = "Server error: ${response.code()}"
                    )
                }
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = e.localizedMessage ?: "Network error"
                )
            }
        }
    }

    fun clearActionMessage() {
        _uiState.value = _uiState.value.copy(actionMessage = null, orderPlaced = false)
    }
}
