package com.example.affscash.ui.admin

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.AdminOffer
import com.example.affscash.data.model.AdminOfferCreateRequest
import com.example.affscash.data.model.AdminOfferEditRequest
import com.example.affscash.data.model.AdvertiserOption
import com.example.affscash.data.repository.OfferRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class AdminOfferFormUiState {
    object Loading : AdminOfferFormUiState()
    object Idle : AdminOfferFormUiState()
    data class Success(val message: String) : AdminOfferFormUiState()
    data class Error(val message: String) : AdminOfferFormUiState()
}

data class AdminOfferFormData(
    val name: String = "",
    val advertiserId: String = "",
    val offerUrl: String = "",
    val payoutType: String = "CPA",
    val payout: String = "",
    val revenue: String = "",
    val status: String = "active",
    val category: String = "",
    val offerType: String = "",
    val visibility: String = "public",
    val requireApproval: Boolean = false,
    val dailyCap: String = "",
    val totalCap: String = "",
    val geoTargeting: String = "", // Comma separated for simplicity in mobile
    val deviceTargeting: String = "", // Comma separated
    val description: String = "",
    val isInHouse: Boolean = false
)

@HiltViewModel
class AdminOfferFormViewModel @Inject constructor(
    private val repository: OfferRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminOfferFormUiState>(AdminOfferFormUiState.Idle)
    val uiState: StateFlow<AdminOfferFormUiState> = _uiState.asStateFlow()

    private val _formData = MutableStateFlow(AdminOfferFormData())
    val formData: StateFlow<AdminOfferFormData> = _formData.asStateFlow()

    private val _advertisers = MutableStateFlow<List<AdvertiserOption>>(emptyList())
    val advertisers: StateFlow<List<AdvertiserOption>> = _advertisers.asStateFlow()

    private val _categories = MutableStateFlow<List<String>>(emptyList())
    val categories: StateFlow<List<String>> = _categories.asStateFlow()

    private val _offerTypes = MutableStateFlow<List<String>>(emptyList())
    val offerTypes: StateFlow<List<String>> = _offerTypes.asStateFlow()

    fun loadInitialData(offerId: Int?) {
        viewModelScope.launch {
            _uiState.value = AdminOfferFormUiState.Loading
            repository.getAdminOffers()
                .onSuccess { response ->
                    response.meta?.let { meta ->
                        _advertisers.value = meta.advertisers
                        _categories.value = meta.categories
                        _offerTypes.value = meta.offerTypes
                    }
                    
                    if (offerId != null) {
                        val offer = response.data.find { it.id == offerId }
                        if (offer != null) {
                            _formData.value = AdminOfferFormData(
                                name = offer.name,
                                advertiserId = offer.advertiserId.toString(),
                                offerUrl = offer.offerUrl ?: "",
                                payoutType = offer.payoutType,
                                payout = offer.payout.toString(),
                                revenue = offer.revenue.toString(),
                                status = offer.status,
                                category = offer.category ?: "",
                                offerType = offer.offerType ?: "",
                                visibility = offer.visibility,
                                requireApproval = offer.requireApproval,
                                dailyCap = offer.dailyCap.takeIf { it > 0 }?.toString() ?: "",
                                totalCap = offer.totalCap.takeIf { it > 0 }?.toString() ?: "",
                                geoTargeting = offer.geoTargeting.joinToString(", "),
                                deviceTargeting = offer.deviceTargeting.joinToString(", "),
                                description = offer.description ?: "",
                                isInHouse = offer.advName == "In-House" || offer.advertiserId == 0
                            )
                        } else {
                            _uiState.value = AdminOfferFormUiState.Error("Offer not found")
                            return@launch
                        }
                    }
                    _uiState.value = AdminOfferFormUiState.Idle
                }
                .onFailure {
                    _uiState.value = AdminOfferFormUiState.Error(it.message ?: "Failed to load data")
                }
        }
    }

    fun updateFormData(update: AdminOfferFormData.() -> AdminOfferFormData) {
        _formData.value = _formData.value.update()
    }

    fun submit(offerId: Int?) {
        val data = _formData.value
        
        val advId = data.advertiserId.toIntOrNull()
        if (!data.isInHouse && (advId == null || advId <= 0)) {
            _uiState.value = AdminOfferFormUiState.Error("Please select an advertiser")
            return
        }
        if (data.name.isBlank()) {
            _uiState.value = AdminOfferFormUiState.Error("Offer name is required")
            return
        }
        if (data.offerUrl.isBlank()) {
            _uiState.value = AdminOfferFormUiState.Error("Offer URL is required")
            return
        }

        viewModelScope.launch {
            _uiState.value = AdminOfferFormUiState.Loading

            val geoList = data.geoTargeting.split(",").map { it.trim() }.filter { it.isNotEmpty() }
            val deviceList = data.deviceTargeting.split(",").map { it.trim() }.filter { it.isNotEmpty() }

            if (offerId == null) {
                val req = AdminOfferCreateRequest(
                    name = data.name,
                    advertiserId = if (data.isInHouse) 0 else (advId ?: 0),
                    offerUrl = data.offerUrl,
                    payoutType = data.payoutType,
                    payout = data.payout.toDoubleOrNull() ?: 0.0,
                    revenue = data.revenue.toDoubleOrNull() ?: 0.0,
                    status = data.status,
                    category = data.category,
                    offerType = data.offerType,
                    visibility = data.visibility,
                    requireApproval = data.requireApproval,
                    dailyCap = data.dailyCap.toIntOrNull() ?: 0,
                    totalCap = data.totalCap.toIntOrNull() ?: 0,
                    geoTargeting = geoList,
                    deviceTargeting = deviceList,
                    description = data.description,
                    isInhouse = if (data.isInHouse) true else null
                )
                repository.createAdminOffer(req)
                    .onSuccess { _uiState.value = AdminOfferFormUiState.Success("Offer created successfully") }
                    .onFailure { _uiState.value = AdminOfferFormUiState.Error(it.message ?: "Creation failed") }
            } else {
                val req = AdminOfferEditRequest(
                    id = offerId,
                    name = data.name,
                    advertiserId = if (data.isInHouse) 0 else (advId ?: 0),
                    offerUrl = data.offerUrl,
                    payoutType = data.payoutType,
                    payout = data.payout.toDoubleOrNull() ?: 0.0,
                    revenue = data.revenue.toDoubleOrNull() ?: 0.0,
                    status = data.status,
                    category = data.category,
                    offerType = data.offerType,
                    visibility = data.visibility,
                    requireApproval = data.requireApproval,
                    dailyCap = data.dailyCap.toIntOrNull() ?: 0,
                    totalCap = data.totalCap.toIntOrNull() ?: 0,
                    geoTargeting = geoList,
                    deviceTargeting = deviceList,
                    description = data.description,
                    isInhouse = if (data.isInHouse) true else null
                )
                repository.editAdminOffer(req)
                    .onSuccess { _uiState.value = AdminOfferFormUiState.Success("Offer updated successfully") }
                    .onFailure { _uiState.value = AdminOfferFormUiState.Error(it.message ?: "Update failed") }
            }
        }
    }
}
