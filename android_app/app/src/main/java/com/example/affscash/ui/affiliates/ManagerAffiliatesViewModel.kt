package com.example.affscash.ui.affiliates

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.ManagerAffiliateResponse
import com.example.affscash.data.repository.AffiliateRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class ManagerAffiliatesUiState {
    object Loading : ManagerAffiliatesUiState()
    data class Success(val data: ManagerAffiliateResponse) : ManagerAffiliatesUiState()
    data class Error(val message: String) : ManagerAffiliatesUiState()
}

@HiltViewModel
class ManagerAffiliatesViewModel @Inject constructor(
    private val repository: AffiliateRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<ManagerAffiliatesUiState>(ManagerAffiliatesUiState.Loading)
    val uiState: StateFlow<ManagerAffiliatesUiState> = _uiState.asStateFlow()

    init {
        loadAffiliates()
    }

    fun loadAffiliates() {
        viewModelScope.launch {
            _uiState.value = ManagerAffiliatesUiState.Loading
            repository.getManagerAffiliates()
                .onSuccess { response ->
                    _uiState.value = ManagerAffiliatesUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = ManagerAffiliatesUiState.Error(exception.message ?: "Unknown error")
                }
        }
    }
}
