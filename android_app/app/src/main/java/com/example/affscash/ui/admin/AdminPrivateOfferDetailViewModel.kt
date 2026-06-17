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

sealed class AdminPrivateOfferDetailUiState {
    object Loading : AdminPrivateOfferDetailUiState()
    object Idle : AdminPrivateOfferDetailUiState()
    data class Success(val data: PrivateOfferDetailResponse) : AdminPrivateOfferDetailUiState()
    data class Error(val message: String) : AdminPrivateOfferDetailUiState()
}

@HiltViewModel
class AdminPrivateOfferDetailViewModel @Inject constructor(
    private val repository: PrivateOfferRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminPrivateOfferDetailUiState>(AdminPrivateOfferDetailUiState.Idle)
    val uiState: StateFlow<AdminPrivateOfferDetailUiState> = _uiState.asStateFlow()

    private val _actionMessage = MutableStateFlow<String?>(null)
    val actionMessage: StateFlow<String?> = _actionMessage.asStateFlow()

    private var currentOfferId: Int = -1

    fun loadDetail(offerId: Int) {
        currentOfferId = offerId
        viewModelScope.launch {
            _uiState.value = AdminPrivateOfferDetailUiState.Loading
            repository.getDetail(offerId).fold(
                onSuccess = {
                    _uiState.value = AdminPrivateOfferDetailUiState.Success(it)
                },
                onFailure = {
                    _uiState.value = AdminPrivateOfferDetailUiState.Error(it.message ?: "Failed to load detail")
                }
            )
        }
    }

    fun grantAccess(identifier: String, notes: String) {
        if (currentOfferId <= 0) return
        viewModelScope.launch {
            repository.submitAction(
                PrivateOfferActionRequest(
                    action = "grant_access",
                    offer_id = currentOfferId,
                    affiliate_identifier = identifier,
                    notes = notes.takeIf { it.isNotBlank() }
                )
            ).fold(
                onSuccess = {
                    _actionMessage.value = it
                    loadDetail(currentOfferId) // refresh
                },
                onFailure = {
                    _actionMessage.value = it.message ?: "Failed to grant access"
                }
            )
        }
    }

    fun revokeAccess(affiliateId: Int) {
        if (currentOfferId <= 0) return
        viewModelScope.launch {
            repository.submitAction(
                PrivateOfferActionRequest(
                    action = "revoke_access",
                    offer_id = currentOfferId,
                    affiliate_id = affiliateId
                )
            ).fold(
                onSuccess = {
                    _actionMessage.value = it
                    loadDetail(currentOfferId) // refresh
                },
                onFailure = {
                    _actionMessage.value = it.message ?: "Failed to revoke access"
                }
            )
        }
    }

    fun clearActionMessage() {
        _actionMessage.value = null
    }
}
