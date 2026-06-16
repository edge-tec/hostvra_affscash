package com.example.affscash.ui.offers

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.AdminOfferResponse
import com.example.affscash.data.repository.OfferRepository
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

@HiltViewModel
class AdminOffersViewModel @Inject constructor(
    private val repository: OfferRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminOffersUiState>(AdminOffersUiState.Loading)
    val uiState: StateFlow<AdminOffersUiState> = _uiState.asStateFlow()

    init {
        loadOffers()
    }

    fun loadOffers() {
        viewModelScope.launch {
            _uiState.value = AdminOffersUiState.Loading
            repository.getAdminOffers()
                .onSuccess { response ->
                    _uiState.value = AdminOffersUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = AdminOffersUiState.Error(exception.message ?: "Unknown error")
                }
        }
    }
}
