package net.affscash.android.ui.screens.admin.settings

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.AdminPlatformConfig
import net.affscash.android.data.model.AdminPlatformSettingsRequest
import net.affscash.android.data.repository.AdminPlatformSettingsRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject

sealed class AdminPlatformSettingsUiState {
    object Loading : AdminPlatformSettingsUiState()
    data class Success(val config: AdminPlatformConfig) : AdminPlatformSettingsUiState()
    data class Error(val message: String) : AdminPlatformSettingsUiState()
}

@HiltViewModel
class AdminPlatformSettingsViewModel @Inject constructor(
    private val repository: AdminPlatformSettingsRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminPlatformSettingsUiState>(AdminPlatformSettingsUiState.Loading)
    val uiState: StateFlow<AdminPlatformSettingsUiState> = _uiState

    private val _isSaving = MutableStateFlow(false)
    val isSaving: StateFlow<Boolean> = _isSaving

    private val _saveMessage = MutableStateFlow<String?>(null)
    val saveMessage: StateFlow<String?> = _saveMessage

    init {
        loadSettings()
    }

    fun loadSettings() {
        _uiState.value = AdminPlatformSettingsUiState.Loading
        viewModelScope.launch {
            repository.getPlatformSettings()
                .onSuccess { response ->
                    _uiState.value = AdminPlatformSettingsUiState.Success(
                        response.config ?: AdminPlatformConfig()
                    )
                }
                .onFailure { error ->
                    _uiState.value = AdminPlatformSettingsUiState.Error(
                        error.message ?: "Failed to load settings"
                    )
                }
        }
    }

    fun saveSettings(config: AdminPlatformConfig) {
        _isSaving.value = true
        _saveMessage.value = null
        viewModelScope.launch {
            repository.updatePlatformSettings(AdminPlatformSettingsRequest(config))
                .onSuccess {
                    _isSaving.value = false
                    _saveMessage.value = "Settings saved successfully"
                    _uiState.value = AdminPlatformSettingsUiState.Success(config)
                }
                .onFailure { error ->
                    _isSaving.value = false
                    _saveMessage.value = "Failed to save settings: ${error.message}"
                }
        }
    }

    fun clearSaveMessage() {
        _saveMessage.value = null
    }
}
