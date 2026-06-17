package com.example.affscash.ui.admin

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.CreateAdvertiserRequest
import com.example.affscash.data.model.EditAdvertiserRequest
import com.example.affscash.data.repository.AdminAdvertiserRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class AdminAdvertiserFormState(
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val advertiserId: Int? = null,
    val isEditMode: Boolean = false,
    
    val firstName: String = "",
    val lastName: String = "",
    val email: String = "",
    val password: String = "",
    val company: String = "",
    val phone: String = "",
    val country: String = "US",
    val status: String = "active",
    val budgetExempt: Boolean = false,

    val error: String? = null,
    val saveSuccess: Boolean = false
)

@HiltViewModel
class AdminAdvertiserFormViewModel @Inject constructor(
    private val repository: AdminAdvertiserRepository,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminAdvertiserFormState())
    val uiState: StateFlow<AdminAdvertiserFormState> = _uiState.asStateFlow()

    init {
        val advId = savedStateHandle.get<String>("advertiserId")?.toIntOrNull()
        if (advId != null && advId > 0) {
            _uiState.update { it.copy(advertiserId = advId, isEditMode = true) }
            loadAdvertiser(advId)
        }
    }

    private fun loadAdvertiser(id: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            val result = repository.getAdvertiserDetails(id)
            result.onSuccess { response ->
                response.data?.advertiser?.let { adv ->
                    _uiState.update { 
                        it.copy(
                            isLoading = false,
                            firstName = adv.firstName,
                            lastName = adv.lastName,
                            email = adv.email,
                            company = adv.company ?: "",
                            phone = adv.phone ?: "",
                            country = adv.country ?: "US",
                            status = adv.status,
                            budgetExempt = adv.budgetExempt == 1
                        )
                    }
                }
            }.onFailure { error ->
                _uiState.update { it.copy(isLoading = false, error = error.message) }
            }
        }
    }

    fun updateField(
        firstName: String? = null,
        lastName: String? = null,
        email: String? = null,
        password: String? = null,
        company: String? = null,
        phone: String? = null,
        country: String? = null,
        status: String? = null,
        budgetExempt: Boolean? = null
    ) {
        _uiState.update { 
            it.copy(
                firstName = firstName ?: it.firstName,
                lastName = lastName ?: it.lastName,
                email = email ?: it.email,
                password = password ?: it.password,
                company = company ?: it.company,
                phone = phone ?: it.phone,
                country = country ?: it.country,
                status = status ?: it.status,
                budgetExempt = budgetExempt ?: it.budgetExempt
            )
        }
    }

    fun saveAdvertiser() {
        val state = _uiState.value
        if (state.firstName.isBlank() || state.lastName.isBlank()) {
            _uiState.update { it.copy(error = "First and last name are required") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, error = null) }
            
            val result = if (state.isEditMode && state.advertiserId != null) {
                repository.editAdvertiser(
                    EditAdvertiserRequest(
                        id = state.advertiserId,
                        firstName = state.firstName,
                        lastName = state.lastName,
                        company = state.company.ifBlank { null },
                        phone = state.phone.ifBlank { null },
                        country = state.country,
                        budgetExempt = if (state.budgetExempt) 1 else 0
                    )
                )
            } else {
                if (state.email.isBlank() || state.password.isBlank()) {
                    _uiState.update { it.copy(isSaving = false, error = "Email and password are required for new advertisers") }
                    return@launch
                }
                repository.createAdvertiser(
                    CreateAdvertiserRequest(
                        firstName = state.firstName,
                        lastName = state.lastName,
                        email = state.email,
                        password = state.password,
                        company = state.company.ifBlank { null },
                        phone = state.phone.ifBlank { null },
                        country = state.country,
                        status = state.status,
                        budgetExempt = if (state.budgetExempt) 1 else 0
                    )
                )
            }

            result.onSuccess {
                _uiState.update { it.copy(isSaving = false, saveSuccess = true) }
            }.onFailure { error ->
                _uiState.update { it.copy(isSaving = false, error = error.message) }
            }
        }
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }
}
