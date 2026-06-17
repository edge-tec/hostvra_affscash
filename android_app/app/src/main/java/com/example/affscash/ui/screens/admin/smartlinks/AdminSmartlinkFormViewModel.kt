package com.example.affscash.ui.screens.admin.smartlinks

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.AdminAvailableOffer
import com.example.affscash.data.model.AdminSmartlinkOffer
import com.example.affscash.data.model.AdminSmartlinkSaveRequest
import com.example.affscash.data.repository.AdminSmartlinkRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject

@HiltViewModel
class AdminSmartlinkFormViewModel @Inject constructor(
    private val repository: AdminSmartlinkRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminSmartlinkFormUiState>(AdminSmartlinkFormUiState.Loading)
    val uiState: StateFlow<AdminSmartlinkFormUiState> = _uiState

    fun loadForm(smartlinkId: Int?) {
        viewModelScope.launch {
            if (smartlinkId == null || smartlinkId == 0) {
                // For new smartlink, we still need available offers
                // We can fetch the detail endpoint with id=0 to just get available_offers
                val result = repository.getAdminSmartlinkDetail(0)
                result.onSuccess { wrapper ->
                    val availableOffers = wrapper.data?.availableOffers ?: emptyList()
                    _uiState.value = AdminSmartlinkFormUiState.Success(
                        id = null,
                        name = "",
                        slug = "",
                        rotationType = "geo",
                        description = "",
                        status = "active",
                        requireApproval = false,
                        offers = listOf(AdminSmartlinkOffer(offerId = null)), // One empty row
                        availableOffers = availableOffers
                    )
                }.onFailure { e ->
                    _uiState.value = AdminSmartlinkFormUiState.Error(e.message ?: "Failed to load offers")
                }
            } else {
                _uiState.value = AdminSmartlinkFormUiState.Loading
                val result = repository.getAdminSmartlinkDetail(smartlinkId)
                result.onSuccess { wrapper ->
                    val data = wrapper.data
                    if (wrapper.status == "success" && data != null) {
                        _uiState.value = AdminSmartlinkFormUiState.Success(
                            id = data.smartlink.id,
                            name = data.smartlink.name,
                            slug = data.smartlink.slug,
                            rotationType = data.smartlink.rotationType,
                            description = data.smartlink.description ?: "",
                            status = data.smartlink.status,
                            requireApproval = data.smartlink.requireApproval == 1,
                            offers = data.offers.ifEmpty { listOf(AdminSmartlinkOffer(offerId = null)) },
                            availableOffers = data.availableOffers
                        )
                    } else {
                        _uiState.value = AdminSmartlinkFormUiState.Error("Failed to load details")
                    }
                }.onFailure { e ->
                    _uiState.value = AdminSmartlinkFormUiState.Error(e.message ?: "Unknown error")
                }
            }
        }
    }

    fun updateField(
        name: String? = null,
        slug: String? = null,
        rotationType: String? = null,
        description: String? = null,
        status: String? = null,
        requireApproval: Boolean? = null
    ) {
        val currentState = _uiState.value as? AdminSmartlinkFormUiState.Success ?: return
        _uiState.value = currentState.copy(
            name = name ?: currentState.name,
            slug = slug ?: currentState.slug,
            rotationType = rotationType ?: currentState.rotationType,
            description = description ?: currentState.description,
            status = status ?: currentState.status,
            requireApproval = requireApproval ?: currentState.requireApproval
        )
    }

    fun updateOffer(index: Int, offer: AdminSmartlinkOffer) {
        val currentState = _uiState.value as? AdminSmartlinkFormUiState.Success ?: return
        val newOffers = currentState.offers.toMutableList()
        if (index in newOffers.indices) {
            newOffers[index] = offer
            _uiState.value = currentState.copy(offers = newOffers)
        }
    }

    fun addOfferRow() {
        val currentState = _uiState.value as? AdminSmartlinkFormUiState.Success ?: return
        val newOffers = currentState.offers.toMutableList()
        newOffers.add(AdminSmartlinkOffer(offerId = null))
        _uiState.value = currentState.copy(offers = newOffers)
    }

    fun removeOfferRow(index: Int) {
        val currentState = _uiState.value as? AdminSmartlinkFormUiState.Success ?: return
        val newOffers = currentState.offers.toMutableList()
        if (index in newOffers.indices) {
            newOffers.removeAt(index)
            _uiState.value = currentState.copy(offers = newOffers)
        }
    }

    fun save(onSuccess: () -> Unit) {
        val currentState = _uiState.value as? AdminSmartlinkFormUiState.Success ?: return
        viewModelScope.launch {
            _uiState.value = currentState.copy(isSaving = true)
            val request = AdminSmartlinkSaveRequest(
                id = currentState.id,
                name = currentState.name,
                slug = currentState.slug,
                rotationType = currentState.rotationType,
                description = currentState.description,
                status = currentState.status,
                requireApproval = if (currentState.requireApproval) 1 else 0,
                offers = currentState.offers
            )
            val result = repository.saveAdminSmartlink(request)
            result.onSuccess {
                onSuccess()
            }.onFailure { e ->
                _uiState.value = currentState.copy(
                    isSaving = false,
                    saveError = e.message ?: "Failed to save"
                )
            }
        }
    }
}

sealed class AdminSmartlinkFormUiState {
    object Loading : AdminSmartlinkFormUiState()
    data class Success(
        val id: Int?,
        val name: String,
        val slug: String,
        val rotationType: String,
        val description: String,
        val status: String,
        val requireApproval: Boolean,
        val offers: List<AdminSmartlinkOffer>,
        val availableOffers: List<AdminAvailableOffer>,
        val isSaving: Boolean = false,
        val saveError: String? = null
    ) : AdminSmartlinkFormUiState()
    data class Error(val message: String) : AdminSmartlinkFormUiState()
}
