package net.affscash.android.ui.conversions

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.ConversionResponse
import net.affscash.android.data.repository.ConversionRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class AdminConversionsUiState {
    object Loading : AdminConversionsUiState()
    data class Success(val data: ConversionResponse) : AdminConversionsUiState()
    data class Error(val message: String) : AdminConversionsUiState()
}

@HiltViewModel
class AdminConversionsViewModel @Inject constructor(
    private val repository: ConversionRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminConversionsUiState>(AdminConversionsUiState.Loading)
    val uiState: StateFlow<AdminConversionsUiState> = _uiState.asStateFlow()

    init {
        loadConversions()
    }

    fun loadConversions() {
        viewModelScope.launch {
            _uiState.value = AdminConversionsUiState.Loading
            repository.getAdminConversions()
                .onSuccess { response ->
                    _uiState.value = AdminConversionsUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = AdminConversionsUiState.Error(exception.message ?: "Unknown error")
                }
        }
    }
}
