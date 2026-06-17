package com.example.affscash.ui.admin

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.AdminOfferResponse
import com.example.affscash.data.repository.OfferRepository
import com.example.affscash.ui.offers.AdminOfferFilters
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class AdminInHouseOffersUiState {
    object Loading : AdminInHouseOffersUiState()
    data class Success(val data: AdminOfferResponse) : AdminInHouseOffersUiState()
    data class Error(val message: String) : AdminInHouseOffersUiState()
}

@HiltViewModel
class AdminInHouseOffersViewModel @Inject constructor(
    private val repository: OfferRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminInHouseOffersUiState>(AdminInHouseOffersUiState.Loading)
    val uiState: StateFlow<AdminInHouseOffersUiState> = _uiState.asStateFlow()

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
            _uiState.value = AdminInHouseOffersUiState.Loading
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
                inHouse = true
            )
                .onSuccess { response ->
                    _uiState.value = AdminInHouseOffersUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = AdminInHouseOffersUiState.Error(exception.message ?: "Unknown error")
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
