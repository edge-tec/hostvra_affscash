package com.example.affscash.ui.offers

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.ManagerOfferResponse
import com.example.affscash.data.repository.OfferRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class ManagerOffersUiState {
    object Loading : ManagerOffersUiState()
    data class Success(val data: ManagerOfferResponse) : ManagerOffersUiState()
    data class Error(val message: String) : ManagerOffersUiState()
}

@HiltViewModel
class ManagerOffersViewModel @Inject constructor(
    private val repository: OfferRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<ManagerOffersUiState>(ManagerOffersUiState.Loading)
    val uiState: StateFlow<ManagerOffersUiState> = _uiState.asStateFlow()

    init {
        loadOffers()
    }

    fun loadOffers() {
        viewModelScope.launch {
            _uiState.value = ManagerOffersUiState.Loading
            repository.getManagerOffers()
                .onSuccess { response ->
                    _uiState.value = ManagerOffersUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = ManagerOffersUiState.Error(exception.message ?: "Unknown error")
                }
        }
    }
}
