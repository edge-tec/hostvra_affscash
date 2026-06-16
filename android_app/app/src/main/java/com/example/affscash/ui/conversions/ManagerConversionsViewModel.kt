package com.example.affscash.ui.conversions

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.ConversionResponse
import com.example.affscash.data.repository.ConversionRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class ManagerConversionsUiState {
    object Loading : ManagerConversionsUiState()
    data class Success(val data: ConversionResponse) : ManagerConversionsUiState()
    data class Error(val message: String) : ManagerConversionsUiState()
}

@HiltViewModel
class ManagerConversionsViewModel @Inject constructor(
    private val repository: ConversionRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<ManagerConversionsUiState>(ManagerConversionsUiState.Loading)
    val uiState: StateFlow<ManagerConversionsUiState> = _uiState.asStateFlow()

    init {
        loadConversions()
    }

    fun loadConversions() {
        viewModelScope.launch {
            _uiState.value = ManagerConversionsUiState.Loading
            repository.getManagerConversions()
                .onSuccess { response ->
                    _uiState.value = ManagerConversionsUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = ManagerConversionsUiState.Error(exception.message ?: "Unknown error")
                }
        }
    }
}
