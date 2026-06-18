package net.affscash.android.ui.manager

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.ManagerOffer
import net.affscash.android.data.model.ManagerOfferFilters
import net.affscash.android.data.repository.OfferRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class ManagerOffersUiState(
    val isLoading: Boolean = false,
    val isFiltersLoading: Boolean = false,
    val offers: List<ManagerOffer> = emptyList(),
    val trackingUrlBase: String = "",
    val filters: ManagerOfferFilters? = null,
    val error: String? = null,
    
    // Active Filters
    val tab: String = "regular",
    val searchQuery: String = "",
    val category: String? = null,
    val payoutType: String? = null,
    val offerType: String? = null,
    val statusFilter: String? = null,
    val country: String? = null,
    val device: String? = null,
    val offerId: String = "",
    val accessFilter: String? = null
)

@HiltViewModel
class ManagerOffersViewModel @Inject constructor(
    private val repository: OfferRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(ManagerOffersUiState())
    val uiState: StateFlow<ManagerOffersUiState> = _uiState.asStateFlow()

    init {
        loadFilters()
        loadOffers()
    }

    private fun loadFilters() {
        viewModelScope.launch {
            _uiState.update { it.copy(isFiltersLoading = true) }
            val result = repository.getManagerOfferFilters()
            result.onSuccess { response ->
                _uiState.update { it.copy(
                    isFiltersLoading = false,
                    filters = response.data
                ) }
            }.onFailure { error ->
                _uiState.update { it.copy(isFiltersLoading = false, error = error.message) }
            }
        }
    }

    fun loadOffers() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            val state = _uiState.value
            val result = repository.getManagerOffers(
                tab = state.tab,
                query = state.searchQuery.ifBlank { null },
                category = state.category.takeIf { !it.isNullOrBlank() },
                payoutType = state.payoutType.takeIf { !it.isNullOrBlank() },
                offerType = state.offerType.takeIf { !it.isNullOrBlank() },
                statusFilter = state.statusFilter.takeIf { !it.isNullOrBlank() },
                country = state.country.takeIf { !it.isNullOrBlank() },
                device = state.device.takeIf { !it.isNullOrBlank() },
                offerId = state.offerId.toIntOrNull(),
                accessFilter = state.accessFilter.takeIf { !it.isNullOrBlank() }
            )
            
            result.onSuccess { response ->
                _uiState.update { it.copy(
                    isLoading = false,
                    offers = response.data?.offers ?: emptyList(),
                    trackingUrlBase = response.data?.trackingUrlBase ?: ""
                ) }
            }.onFailure { error ->
                _uiState.update { it.copy(
                    isLoading = false,
                    error = error.message
                ) }
            }
        }
    }

    fun setTab(tab: String) {
        _uiState.update { it.copy(tab = tab) }
        loadOffers()
    }

    fun updateSearchQuery(query: String) {
        _uiState.update { it.copy(searchQuery = query) }
    }

    fun updateFilter(
        category: String? = _uiState.value.category,
        payoutType: String? = _uiState.value.payoutType,
        offerType: String? = _uiState.value.offerType,
        statusFilter: String? = _uiState.value.statusFilter,
        country: String? = _uiState.value.country,
        device: String? = _uiState.value.device,
        offerId: String = _uiState.value.offerId,
        accessFilter: String? = _uiState.value.accessFilter
    ) {
        _uiState.update { 
            it.copy(
                category = category,
                payoutType = payoutType,
                offerType = offerType,
                statusFilter = statusFilter,
                country = country,
                device = device,
                offerId = offerId,
                accessFilter = accessFilter
            ) 
        }
    }

    fun applyFilters() {
        loadOffers()
    }

    fun resetFilters() {
        _uiState.update { 
            it.copy(
                searchQuery = "",
                category = null,
                payoutType = null,
                offerType = null,
                statusFilter = null,
                country = null,
                device = null,
                offerId = "",
                accessFilter = null
            ) 
        }
        loadOffers()
    }
}
