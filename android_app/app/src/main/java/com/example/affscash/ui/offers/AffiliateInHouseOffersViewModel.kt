package com.example.affscash.ui.offers

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.Offer
import com.example.affscash.data.repository.OfferRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class AffiliateInHouseOffersState {
    object Loading : AffiliateInHouseOffersState()
    data class Success(val offers: List<Offer>) : AffiliateInHouseOffersState()
    data class Error(val message: String) : AffiliateInHouseOffersState()
}

@HiltViewModel
class AffiliateInHouseOffersViewModel @Inject constructor(
    private val offerRepository: OfferRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AffiliateInHouseOffersState>(AffiliateInHouseOffersState.Loading)
    val uiState: StateFlow<AffiliateInHouseOffersState> = _uiState

    val searchQuery = MutableStateFlow("")
    val category = MutableStateFlow("All Categories")
    val payoutType = MutableStateFlow("All Types")
    val offerType = MutableStateFlow("All")
    val country = MutableStateFlow("")
    val device = MutableStateFlow("All")
    val accessFilter = MutableStateFlow("All Offers")

    init {
        loadOffers()
    }

    fun loadOffers() {
        viewModelScope.launch {
            _uiState.value = AffiliateInHouseOffersState.Loading
            
            val q = searchQuery.value.takeIf { it.isNotBlank() }
            val cat = category.value.takeIf { it != "All Categories" }
            val pt = payoutType.value.takeIf { it != "All Types" }
            val ot = offerType.value.takeIf { it != "All" }
            val ctry = country.value.takeIf { it.isNotBlank() }
            val dev = device.value.takeIf { it != "All" }
            val access = when (accessFilter.value) {
                "Request Approval" -> "request"
                "Instantly Approved" -> "all_access"
                else -> ""
            }

            val result = offerRepository.getOffers(
                query = q,
                category = cat,
                payoutType = pt,
                offerType = ot,
                country = ctry,
                device = dev,
                accessFilter = access,
                inHouse = true
            )
            
            result.onSuccess {
                _uiState.value = AffiliateInHouseOffersState.Success(it.offers)
            }.onFailure {
                _uiState.value = AffiliateInHouseOffersState.Error(it.message ?: "Failed to load offers")
            }
        }
    }

    fun applyOffer(offerId: Int, promoDesc: String, onResult: (Boolean, String) -> Unit) {
        viewModelScope.launch {
            val result = offerRepository.applyOffer(offerId, promoDesc)
            result.onSuccess {
                onResult(true, it.message ?: "Applied successfully")
                loadOffers() // Reload to get updated status
            }.onFailure {
                onResult(false, it.message ?: "Failed to apply")
            }
        }
    }

    fun getOfferDetails(offerId: Int, onResult: (Boolean, String?) -> Unit) {
        viewModelScope.launch {
            val result = offerRepository.getOfferDetails(offerId)
            result.onSuccess {
                if (it.success && it.offer != null) {
                    onResult(true, it.offer.trackingLink)
                } else {
                    onResult(false, it.error)
                }
            }.onFailure {
                onResult(false, it.message)
            }
        }
    }
}
