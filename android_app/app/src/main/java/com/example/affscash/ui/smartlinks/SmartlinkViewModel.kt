package com.example.affscash.ui.smartlinks

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.Smartlink
import com.example.affscash.data.repository.SmartlinkRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class SmartlinkState {
    object Loading : SmartlinkState()
    data class Success(val smartlinks: List<Smartlink>) : SmartlinkState()
    data class Error(val message: String) : SmartlinkState()
}

@HiltViewModel
class SmartlinkViewModel @Inject constructor(
    private val repository: SmartlinkRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<SmartlinkState>(SmartlinkState.Loading)
    val uiState: StateFlow<SmartlinkState> = _uiState

    init {
        loadSmartlinks()
    }

    fun loadSmartlinks() {
        viewModelScope.launch {
            _uiState.value = SmartlinkState.Loading
            val result = repository.getSmartlinks()
            result.onSuccess {
                _uiState.value = SmartlinkState.Success(it.smartlinks)
            }.onFailure {
                _uiState.value = SmartlinkState.Error(it.message ?: "Failed to load smartlinks")
            }
        }
    }

    fun applySmartlink(smartlinkId: Int, promoDesc: String, onResult: (Boolean, String) -> Unit) {
        viewModelScope.launch {
            val result = repository.applySmartlink(smartlinkId, promoDesc)
            result.onSuccess {
                onResult(true, it.message ?: "Applied successfully")
                loadSmartlinks()
            }.onFailure {
                onResult(false, it.message ?: "Failed to apply")
            }
        }
    }
}
