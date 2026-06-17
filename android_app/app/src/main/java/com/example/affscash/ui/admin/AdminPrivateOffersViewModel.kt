package com.example.affscash.ui.admin

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.*
import com.example.affscash.data.repository.PrivateOfferRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class AdminPrivateOffersUiState {
    object Loading : AdminPrivateOffersUiState()
    object Idle : AdminPrivateOffersUiState()
    data class Success(val data: PrivateOfferDashboardResponse) : AdminPrivateOffersUiState()
    data class Error(val message: String) : AdminPrivateOffersUiState()
}

@HiltViewModel
class AdminPrivateOffersViewModel @Inject constructor(
    private val repository: PrivateOfferRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminPrivateOffersUiState>(AdminPrivateOffersUiState.Idle)
    val uiState: StateFlow<AdminPrivateOffersUiState> = _uiState.asStateFlow()

    private val _actionMessage = MutableStateFlow<String?>(null)
    val actionMessage: StateFlow<String?> = _actionMessage.asStateFlow()

    fun loadDashboard() {
        viewModelScope.launch {
            _uiState.value = AdminPrivateOffersUiState.Loading
            repository.getDashboard().fold(
                onSuccess = {
                    _uiState.value = AdminPrivateOffersUiState.Success(it)
                },
                onFailure = {
                    _uiState.value = AdminPrivateOffersUiState.Error(it.message ?: "Failed to load")
                }
            )
        }
    }

    fun markOfferPrivate(offerId: Int) {
        viewModelScope.launch {
            repository.submitAction(
                PrivateOfferActionRequest(
                    action = "set_private",
                    offer_id = offerId,
                    private = 1
                )
            ).fold(
                onSuccess = {
                    _actionMessage.value = it
                    loadDashboard() // refresh
                },
                onFailure = {
                    _actionMessage.value = it.message ?: "Failed to mark private"
                }
            )
        }
    }

    fun makeOfferPublic(offerId: Int) {
        viewModelScope.launch {
            repository.submitAction(
                PrivateOfferActionRequest(
                    action = "set_private",
                    offer_id = offerId,
                    private = 0
                )
            ).fold(
                onSuccess = {
                    _actionMessage.value = it
                    loadDashboard() // refresh
                },
                onFailure = {
                    _actionMessage.value = it.message ?: "Failed to make public"
                }
            )
        }
    }

    fun clearActionMessage() {
        _actionMessage.value = null
    }
}
