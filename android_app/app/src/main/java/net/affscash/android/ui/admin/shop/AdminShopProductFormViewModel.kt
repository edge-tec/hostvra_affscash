package net.affscash.android.ui.admin.shop

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.AdminShopProductRequest
import net.affscash.android.data.repository.AdminShopRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class AdminShopProductFormUiState {
    object Idle : AdminShopProductFormUiState()
    object Loading : AdminShopProductFormUiState()
    data class Success(val message: String) : AdminShopProductFormUiState()
    data class Error(val message: String) : AdminShopProductFormUiState()
}

data class AdminShopProductFormData(
    val name: String = "",
    val description: String = "",
    val pricePoints: String = "",
    val stock: String = "",
    val status: String = "active",
    val imageBase64: String? = null,
    val existingImagePath: String? = null
)

@HiltViewModel
class AdminShopProductFormViewModel @Inject constructor(
    private val repository: AdminShopRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminShopProductFormUiState>(AdminShopProductFormUiState.Idle)
    val uiState: StateFlow<AdminShopProductFormUiState> = _uiState.asStateFlow()

    private val _formData = MutableStateFlow(AdminShopProductFormData())
    val formData: StateFlow<AdminShopProductFormData> = _formData.asStateFlow()

    fun loadInitialData(productId: Int?) {
        if (productId == null) return
        viewModelScope.launch {
            _uiState.value = AdminShopProductFormUiState.Loading
            repository.getShopDashboard()
                .onSuccess { response ->
                    val product = response.data?.products?.find { it.id == productId }
                    if (product != null) {
                        _formData.value = AdminShopProductFormData(
                            name = product.name,
                            description = product.description ?: "",
                            pricePoints = product.pricePoints.toString(),
                            stock = product.stock ?: "",
                            status = product.status,
                            existingImagePath = product.imagePath
                        )
                        _uiState.value = AdminShopProductFormUiState.Idle
                    } else {
                        _uiState.value = AdminShopProductFormUiState.Error("Product not found")
                    }
                }
                .onFailure {
                    _uiState.value = AdminShopProductFormUiState.Error(it.message ?: "Failed to load product")
                }
        }
    }

    fun updateFormData(update: AdminShopProductFormData.() -> AdminShopProductFormData) {
        _formData.value = _formData.value.update()
    }

    fun submit(productId: Int?) {
        val data = _formData.value
        
        if (data.name.isBlank()) {
            _uiState.value = AdminShopProductFormUiState.Error("Product name is required")
            return
        }
        val points = data.pricePoints.toIntOrNull() ?: 0
        if (points <= 0) {
            _uiState.value = AdminShopProductFormUiState.Error("Valid price (points) is required")
            return
        }

        viewModelScope.launch {
            _uiState.value = AdminShopProductFormUiState.Loading

            val request = AdminShopProductRequest(
                id = productId,
                name = data.name,
                description = data.description,
                pricePoints = points,
                stock = data.stock,
                status = data.status,
                imageBase64 = data.imageBase64,
                imagePath = data.existingImagePath
            )

            repository.saveShopProduct(request)
                .onSuccess {
                    _uiState.value = AdminShopProductFormUiState.Success(it.message ?: "Saved successfully")
                }
                .onFailure {
                    _uiState.value = AdminShopProductFormUiState.Error(it.message ?: "Failed to save")
                }
        }
    }
}
