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

sealed class OfferState {
    object Loading : OfferState()
    data class Success(val offers: List<Offer>) : OfferState()
    data class Error(val message: String) : OfferState()
}

@HiltViewModel
class OfferViewModel @Inject constructor(
    private val offerRepository: OfferRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<OfferState>(OfferState.Loading)
    val uiState: StateFlow<OfferState> = _uiState

    init {
        loadOffers()
    }

    fun loadOffers() {
        viewModelScope.launch {
            _uiState.value = OfferState.Loading
            val result = offerRepository.getOffers()
            result.onSuccess {
                _uiState.value = OfferState.Success(it.offers)
            }.onFailure {
                _uiState.value = OfferState.Error(it.message ?: "Failed to load offers")
            }
        }
    }
}
