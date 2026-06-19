package net.affscash.android.ui.screens.admin.payment_settings

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import net.affscash.android.data.model.PaymentSettingsData
import net.affscash.android.data.repository.AdminPaymentSettingsRepository
import javax.inject.Inject

data class AdminPaymentSettingsUiState(
    val data: PaymentSettingsData? = null,
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val error: String? = null,
    val successMessage: String? = null,
    val selectedTab: Int = 0 // 0: Methods, 1: Terms, 2: Commission, 3: Payout Info
)

@HiltViewModel
class AdminPaymentSettingsViewModel @Inject constructor(
    private val repository: AdminPaymentSettingsRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminPaymentSettingsUiState())
    val uiState: StateFlow<AdminPaymentSettingsUiState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun loadData() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            repository.getPaymentSettings().fold(
                onSuccess = { res ->
                    if (res.success) {
                        _uiState.update { it.copy(data = res.data, isLoading = false) }
                    } else {
                        _uiState.update { it.copy(error = res.error ?: "Unknown error", isLoading = false) }
                    }
                },
                onFailure = { e ->
                    _uiState.update { it.copy(error = e.message ?: "Failed to load payment settings", isLoading = false) }
                }
            )
        }
    }

    fun setTab(index: Int) {
        _uiState.update { it.copy(selectedTab = index) }
    }

    fun clearMessage() {
        _uiState.update { it.copy(successMessage = null, error = null) }
    }

    // --- Payment Methods Actions ---
    fun savePaymentMethod(action: String, id: Int?, name: String, type: String, desc: String, inst: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, error = null) }
            val req = mutableMapOf(
                "pm_action" to action,
                "pm_name" to name,
                "pm_method_type" to type,
                "pm_description" to desc,
                "pm_instructions" to inst
            )
            id?.let { req["pm_id"] = it.toString() }

            repository.savePaymentMethod(req).fold(
                onSuccess = { res ->
                    if (res.success) {
                        _uiState.update { it.copy(successMessage = res.message, isSaving = false) }
                        loadData()
                    } else {
                        _uiState.update { it.copy(error = res.error, isSaving = false) }
                    }
                },
                onFailure = { e -> _uiState.update { it.copy(error = e.message, isSaving = false) } }
            )
        }
    }

    fun togglePaymentMethod(id: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, error = null) }
            repository.savePaymentMethod(mapOf("pm_action" to "toggle", "pm_id" to id.toString())).fold(
                onSuccess = { res ->
                    if (res.success) {
                        _uiState.update { it.copy(successMessage = res.message, isSaving = false) }
                        loadData()
                    } else {
                        _uiState.update { it.copy(error = res.error, isSaving = false) }
                    }
                },
                onFailure = { e -> _uiState.update { it.copy(error = e.message, isSaving = false) } }
            )
        }
    }

    fun deletePaymentMethod(id: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, error = null) }
            repository.savePaymentMethod(mapOf("pm_action" to "delete", "pm_id" to id.toString())).fold(
                onSuccess = { res ->
                    if (res.success) {
                        _uiState.update { it.copy(successMessage = res.message, isSaving = false) }
                        loadData()
                    } else {
                        _uiState.update { it.copy(error = res.error, isSaving = false) }
                    }
                },
                onFailure = { e -> _uiState.update { it.copy(error = e.message, isSaving = false) } }
            )
        }
    }

    // --- Payment Terms Actions ---
    fun savePaymentTerms(applyTo: String, terms: String, selectedAffiliateId: Int?) {
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, error = null) }
            val applyToVal = if (applyTo == "all") "all" else "selected"
            val affiliateIdVal = if (applyTo == "selected") selectedAffiliateId else null
            
            val req = PaymentTermsRequest(
                paymentTerms = terms,
                applyTo = applyToVal,
                affiliateId = affiliateIdVal
            )
            repository.savePaymentTerms(req).fold(
                onSuccess = { res ->
                    if (res.success) {
                        _uiState.update { it.copy(successMessage = res.message, isSaving = false) }
                        loadData()
                    } else {
                        _uiState.update { it.copy(error = res.error, isSaving = false) }
                    }
                },
                onFailure = { e -> _uiState.update { it.copy(error = e.message, isSaving = false) } }
            )
        }
    }

    // --- Manager Commission Actions ---
    fun saveManagerCommission(mgrId: Int, rate: Double) {
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, error = null) }
            repository.saveManagerCommission(mapOf("manager_id" to mgrId.toString(), "commission_rate" to rate.toString())).fold(
                onSuccess = { res ->
                    if (res.success) {
                        _uiState.update { it.copy(successMessage = res.message, isSaving = false) }
                        loadData()
                    } else {
                        _uiState.update { it.copy(error = res.error, isSaving = false) }
                    }
                },
                onFailure = { e -> _uiState.update { it.copy(error = e.message, isSaving = false) } }
            )
        }
    }

    fun saveOfferCommission(mgrId: Int, offerId: Int, rate: Double) {
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, error = null) }
            repository.saveOfferCommission(mapOf(
                "oc_action" to "save",
                "oc_manager_id" to mgrId.toString(),
                "oc_offer_id" to offerId.toString(),
                "oc_rate" to rate.toString()
            )).fold(
                onSuccess = { res ->
                    if (res.success) {
                        _uiState.update { it.copy(successMessage = res.message, isSaving = false) }
                        loadData()
                    } else {
                        _uiState.update { it.copy(error = res.error, isSaving = false) }
                    }
                },
                onFailure = { e -> _uiState.update { it.copy(error = e.message, isSaving = false) } }
            )
        }
    }

    fun deleteOfferCommission(ocId: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, error = null) }
            repository.saveOfferCommission(mapOf(
                "oc_action" to "delete",
                "oc_id" to ocId.toString()
            )).fold(
                onSuccess = { res ->
                    if (res.success) {
                        _uiState.update { it.copy(successMessage = res.message, isSaving = false) }
                        loadData()
                    } else {
                        _uiState.update { it.copy(error = res.error, isSaving = false) }
                    }
                },
                onFailure = { e -> _uiState.update { it.copy(error = e.message, isSaving = false) } }
            )
        }
    }

    // --- Payout Info Actions ---
    fun savePayoutInfo(type: String, entityId: Int, method: String, details: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, error = null) }
            val req = net.affscash.android.data.model.PayoutInfoRequest(
                type = type,
                id = entityId,
                paymentMethod = method,
                paymentDetails = details
            )
            repository.savePayoutInfo(req).fold(
                onSuccess = { res ->
                    if (res.success) {
                        _uiState.update { it.copy(successMessage = res.message, isSaving = false) }
                        loadData()
                    } else {
                        _uiState.update { it.copy(error = res.error, isSaving = false) }
                    }
                },
                onFailure = { e -> _uiState.update { it.copy(error = e.message, isSaving = false) } }
            )
        }
    }
}
