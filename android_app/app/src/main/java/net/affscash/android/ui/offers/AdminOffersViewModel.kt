package net.affscash.android.ui.offers

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.AdminOfferResponse
import net.affscash.android.data.repository.OfferRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class AdminOffersUiState {
    object Loading : AdminOffersUiState()
    data class Success(val data: AdminOfferResponse) : AdminOffersUiState()
    data class Error(val message: String) : AdminOffersUiState()
}

data class AdminOfferFilters(
    val query: String = "",
    val category: String = "",
    val payoutType: String = "",
    val status: String = "",
    val offerType: String = "",
    val country: String = "",
    val device: String = "",
    val offerId: String = "",
    val access: String = ""
)

@HiltViewModel
class AdminOffersViewModel @Inject constructor(
    private val repository: OfferRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminOffersUiState>(AdminOffersUiState.Loading)
    val uiState: StateFlow<AdminOffersUiState> = _uiState.asStateFlow()

    private val _filters = MutableStateFlow(AdminOfferFilters())
    val filters: StateFlow<AdminOfferFilters> = _filters.asStateFlow()

    private val _actionMessage = MutableStateFlow<String?>(null)
    val actionMessage: StateFlow<String?> = _actionMessage.asStateFlow()

    init {
        loadOffers()
    }

    fun updateFilter(update: AdminOfferFilters.() -> AdminOfferFilters) {
        _filters.value = _filters.value.update()
    }

    fun applyFilters() {
        loadOffers()
    }

    fun clearFilters() {
        _filters.value = AdminOfferFilters()
        loadOffers()
    }

    fun loadOffers() {
        viewModelScope.launch {
            _uiState.value = AdminOffersUiState.Loading
            val f = _filters.value
            repository.getAdminOffers(
                query = f.query.takeIf { it.isNotEmpty() },
                category = f.category.takeIf { it.isNotEmpty() },
                payoutType = f.payoutType.takeIf { it.isNotEmpty() },
                status = f.status.takeIf { it.isNotEmpty() },
                offerType = f.offerType.takeIf { it.isNotEmpty() },
                country = f.country.takeIf { it.isNotEmpty() },
                device = f.device.takeIf { it.isNotEmpty() },
                offerId = f.offerId.toIntOrNull(),
                access = f.access.takeIf { it.isNotEmpty() },
                inHouse = false
            )
                .onSuccess { response ->
                    _uiState.value = AdminOffersUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = AdminOffersUiState.Error(exception.message ?: "Unknown error")
                }
        }
    }

    fun updateOfferStatus(id: Int, status: String) {
        viewModelScope.launch {
            repository.updateAdminOfferStatus(id, status)
                .onSuccess {
                    _actionMessage.value = "Status updated to $status"
                    loadOffers()
                }
                .onFailure {
                    _actionMessage.value = "Failed to update status: ${it.message}"
                }
        }
    }

    fun deleteOffer(id: Int) {
        viewModelScope.launch {
            repository.deleteAdminOffer(id)
                .onSuccess {
                    _actionMessage.value = "Offer deleted"
                    loadOffers()
                }
                .onFailure {
                    _actionMessage.value = "Failed to delete offer: ${it.message}"
                }
        }
    }

    fun clearActionMessage() {
        _actionMessage.value = null
    }
}
