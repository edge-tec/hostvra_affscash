package com.example.affscash.ui.affiliates

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.AdminAffiliateResponse
import com.example.affscash.data.repository.AffiliateRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class AdminAffiliatesUiState {
    object Loading : AdminAffiliatesUiState()
    data class Success(val data: AdminAffiliateResponse) : AdminAffiliatesUiState()
    data class Error(val message: String) : AdminAffiliatesUiState()
}

@HiltViewModel
class AdminAffiliatesViewModel @Inject constructor(
    private val repository: AffiliateRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminAffiliatesUiState>(AdminAffiliatesUiState.Loading)
    val uiState: StateFlow<AdminAffiliatesUiState> = _uiState.asStateFlow()

    init {
        loadAffiliates()
    }

    fun loadAffiliates() {
        viewModelScope.launch {
            _uiState.value = AdminAffiliatesUiState.Loading
            repository.getAdminAffiliates()
                .onSuccess { response ->
                    _uiState.value = AdminAffiliatesUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = AdminAffiliatesUiState.Error(exception.message ?: "Unknown error")
                }
        }
    }
}
