package net.affscash.android.ui.screens.admin.invoices

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import net.affscash.android.data.model.*
import net.affscash.android.data.repository.AdminInvoiceRepository
import java.text.SimpleDateFormat
import java.util.*
import javax.inject.Inject

data class AdminCreateInvoiceUiState(
    val isLoading: Boolean = false,
    val isSubmitting: Boolean = false,
    val successMessage: String? = null,
    val error: String? = null,
    
    val formData: AdminInvoiceFormData? = null,
    val invoiceType: String = "affiliate_payout",
    val selectedEntityId: Int? = null,
    
    val periodStart: String = "",
    val periodEnd: String = "",
    val dueDate: String = "",
    
    val entityBalance: Double? = null,
    val entityThreshold: Double? = null,
    val entityPaymentMethod: String? = null,
    val entityPaymentDetails: String? = null,
    
    val loadedOffers: List<AdminInvoiceOfferItem> = emptyList(),
    val editableItems: List<EditableLineItem> = emptyList(),
    
    val taxRate: String = "0",
    val customNotes: String = "",
    val paymentDetailsOverride: String = "",
    val totalOverride: String = ""
)

data class EditableLineItem(
    val description: String,
    val qty: String,
    val rate: String
)

@HiltViewModel
class AdminCreateInvoiceViewModel @Inject constructor(
    private val repository: AdminInvoiceRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminCreateInvoiceUiState())
    val uiState: StateFlow<AdminCreateInvoiceUiState> = _uiState.asStateFlow()

    init {
        loadFormData()
    }

    private fun loadFormData() {
        _uiState.update { it.copy(isLoading = true, error = null) }
        viewModelScope.launch {
            val result = repository.getFormData()
            if (result.isSuccess) {
                _uiState.update { it.copy(isLoading = false, formData = result.getOrNull()) }
            } else {
                _uiState.update { it.copy(isLoading = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    fun setInvoiceType(type: String) {
        _uiState.update { 
            it.copy(
                invoiceType = type, 
                selectedEntityId = null,
                entityBalance = null,
                entityThreshold = null,
                entityPaymentMethod = null,
                entityPaymentDetails = null,
                loadedOffers = emptyList(),
                editableItems = emptyList()
            ) 
        }
    }

    fun setSelectedEntity(id: Int?) {
        _uiState.update { it.copy(selectedEntityId = id, loadedOffers = emptyList(), editableItems = emptyList()) }
        if (id != null) {
            val type = _uiState.value.invoiceType
            if (type == "affiliate_payout") {
                loadAffiliateInfo(id)
            } else if (type == "manager_fee") {
                loadManagerInfo(id)
            }
        } else {
            _uiState.update { 
                it.copy(
                    entityBalance = null, entityThreshold = null, 
                    entityPaymentMethod = null, entityPaymentDetails = null
                ) 
            }
        }
    }

    private fun loadAffiliateInfo(id: Int) {
        _uiState.update { it.copy(isLoading = true, error = null) }
        viewModelScope.launch {
            val result = repository.getAffiliateInfo(id)
            if (result.isSuccess) {
                val info = result.getOrNull()
                _uiState.update { 
                    it.copy(
                        isLoading = false,
                        entityBalance = info?.balance,
                        entityThreshold = info?.threshold,
                        entityPaymentMethod = info?.paymentMethod,
                        entityPaymentDetails = info?.paymentDetails
                    ) 
                }
            } else {
                _uiState.update { it.copy(isLoading = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    private fun loadManagerInfo(id: Int) {
        _uiState.update { it.copy(isLoading = true, error = null) }
        viewModelScope.launch {
            val result = repository.getManagerInfo(id)
            if (result.isSuccess) {
                val info = result.getOrNull()
                _uiState.update { 
                    it.copy(
                        isLoading = false,
                        entityBalance = info?.balance,
                        entityThreshold = null,
                        entityPaymentMethod = null,
                        entityPaymentDetails = null
                    ) 
                }
            } else {
                _uiState.update { it.copy(isLoading = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    fun setPeriodStart(date: String) {
        _uiState.update { it.copy(periodStart = date) }
    }

    fun setPeriodEnd(date: String) {
        _uiState.update { it.copy(periodEnd = date) }
    }

    fun setDueDate(date: String) {
        _uiState.update { it.copy(dueDate = date) }
    }

    fun setTaxRate(rate: String) {
        _uiState.update { it.copy(taxRate = rate) }
    }

    fun setNotes(notes: String) {
        _uiState.update { it.copy(customNotes = notes) }
    }

    fun setPaymentDetailsOverride(details: String) {
        _uiState.update { it.copy(paymentDetailsOverride = details) }
    }

    fun setTotalOverride(total: String) {
        _uiState.update { it.copy(totalOverride = total) }
    }

    fun updateLineItem(index: Int, description: String, qty: String, rate: String) {
        val items = _uiState.value.editableItems.toMutableList()
        if (index in items.indices) {
            items[index] = EditableLineItem(description, qty, rate)
            _uiState.update { it.copy(editableItems = items) }
        }
    }

    fun addLineItem() {
        val items = _uiState.value.editableItems.toMutableList()
        items.add(EditableLineItem("", "1.0", "0.0"))
        _uiState.update { it.copy(editableItems = items) }
    }

    fun removeLineItem(index: Int) {
        val items = _uiState.value.editableItems.toMutableList()
        if (index in items.indices) {
            items.removeAt(index)
            _uiState.update { it.copy(editableItems = items) }
        }
    }

    fun loadOffers() {
        val state = _uiState.value
        val entityId = state.selectedEntityId
        if (entityId == null) {
            _uiState.update { it.copy(error = "Please select an entity first") }
            return
        }
        if (state.periodStart.isEmpty() || state.periodEnd.isEmpty()) {
            _uiState.update { it.copy(error = "Please select Period Start and Period End") }
            return
        }

        if (state.invoiceType == "advertiser_billing") {
            // Advertiser billing might not load offers the same way in the current web app, 
            // but we'll try if the backend supports it, else we just add an empty item.
            // Let's just add an empty item for advertisers or managers if not affiliate.
            val items = state.editableItems.toMutableList()
            if (items.isEmpty()) {
                items.add(EditableLineItem("Billing for ${state.periodStart} to ${state.periodEnd}", "1.0", "0.0"))
            }
            _uiState.update { it.copy(editableItems = items) }
            return
        }

        _uiState.update { it.copy(isLoading = true, error = null) }
        viewModelScope.launch {
            val result = repository.loadOffers(entityId, state.periodStart, state.periodEnd)
            if (result.isSuccess) {
                val offers = result.getOrNull() ?: emptyList()
                val items = offers.map { 
                    EditableLineItem(
                        description = "Offer: ${it.offerName} (#${it.offerId})",
                        qty = it.conversions.toString(),
                        rate = it.avgRate.toString()
                    )
                }
                _uiState.update { 
                    it.copy(
                        isLoading = false,
                        loadedOffers = offers,
                        editableItems = if (items.isNotEmpty()) items else listOf(EditableLineItem("No conversions found", "1.0", "0.0"))
                    ) 
                }
            } else {
                _uiState.update { it.copy(isLoading = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    fun generateInvoice() {
        val state = _uiState.value
        if (state.selectedEntityId == null) {
            _uiState.update { it.copy(error = "Please select an entity") }
            return
        }
        if (state.editableItems.isEmpty()) {
            _uiState.update { it.copy(error = "Please add at least one line item") }
            return
        }

        val requestItems = state.editableItems.map {
            AdminCreateInvoiceLineItem(
                description = it.description,
                qty = it.qty.toDoubleOrNull() ?: 1.0,
                rate = it.rate.toDoubleOrNull() ?: 0.0
            )
        }

        val request = AdminCreateInvoiceRequest(
            type = state.invoiceType,
            entityId = state.selectedEntityId,
            periodStart = state.periodStart.ifEmpty { null },
            periodEnd = state.periodEnd.ifEmpty { null },
            dueDate = state.dueDate.ifEmpty { null },
            notes = state.customNotes,
            taxRate = state.taxRate.toDoubleOrNull() ?: 0.0,
            totalOverride = state.totalOverride.ifEmpty { null },
            paymentDetailsOverride = state.paymentDetailsOverride,
            items = requestItems
        )

        _uiState.update { it.copy(isSubmitting = true, error = null) }
        viewModelScope.launch {
            val result = repository.createInvoice(request)
            if (result.isSuccess) {
                _uiState.update { it.copy(isSubmitting = false, successMessage = result.getOrNull()) }
            } else {
                _uiState.update { it.copy(isSubmitting = false, error = result.exceptionOrNull()?.message) }
            }
        }
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }
    
    fun clearSuccessMessage() {
        _uiState.update { it.copy(successMessage = null) }
    }
}
