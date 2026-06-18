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

    private val _statusFilter = MutableStateFlow("all")
    val statusFilter: StateFlow<String> = _statusFilter.asStateFlow()

    private val _searchQuery = MutableStateFlow("")
    val searchQuery: StateFlow<String> = _searchQuery.asStateFlow()

    init {
        loadConversions()
    }

    fun setStatusFilter(status: String) {
        _statusFilter.value = status
    }

    fun setSearchQuery(query: String) {
        _searchQuery.value = query
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
