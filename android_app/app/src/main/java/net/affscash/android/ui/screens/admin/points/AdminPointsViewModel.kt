package net.affscash.android.ui.screens.admin.points

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import net.affscash.android.data.model.AdminPointsActionRequest
import net.affscash.android.data.model.AdminPointsBalance
import net.affscash.android.data.model.AdminPointsConfig
import net.affscash.android.data.model.AdminPointsTransaction
import net.affscash.android.data.repository.AdminPointsRepository
import javax.inject.Inject

data class AdminPointsUiState(
    val isLoading: Boolean = false,
    val config: AdminPointsConfig? = null,
    val balances: List<AdminPointsBalance> = emptyList(),
    val recent: List<AdminPointsTransaction> = emptyList(),
    val error: String? = null,
    val syncLog: List<String> = emptyList()
)

@HiltViewModel
class AdminPointsViewModel @Inject constructor(
    private val repository: AdminPointsRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminPointsUiState())
    val uiState: StateFlow<AdminPointsUiState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun loadData() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, error = null)
            repository.getPointsData()
                .onSuccess { response ->
                    if (response.success) {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            config = response.config,
                            balances = response.balances,
                            recent = response.recent
                        )
                    } else {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            error = response.error ?: "Failed to load data"
                        )
                    }
                }
                .onFailure {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        error = it.message ?: "An error occurred"
                    )
                }
        }
    }

    fun saveConfig(enabled: Boolean, usdPerPoint: Int, onSuccess: (String) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true)
            repository.submitAction(AdminPointsActionRequest(
                action = "save_config",
                enabled = enabled,
                usdPerPoint = usdPerPoint
            )).onSuccess { res ->
                if (res.success) {
                    onSuccess(res.message ?: "Config saved")
                    loadData()
                } else {
                    _uiState.value = _uiState.value.copy(isLoading = false)
                    onError(res.error ?: "Failed to save config")
                }
            }.onFailure {
                _uiState.value = _uiState.value.copy(isLoading = false)
                onError(it.message ?: "An error occurred")
            }
        }
    }

    fun adjustPoints(affiliateId: Int, delta: Int, reason: String, onSuccess: (String) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true)
            repository.submitAction(AdminPointsActionRequest(
                action = "adjust",
                affiliateId = affiliateId,
                delta = delta,
                reason = reason
            )).onSuccess { res ->
                if (res.success) {
                    onSuccess(res.message ?: "Adjustment successful")
                    loadData()
                } else {
                    _uiState.value = _uiState.value.copy(isLoading = false)
                    onError(res.error ?: "Failed to adjust points")
                }
            }.onFailure {
                _uiState.value = _uiState.value.copy(isLoading = false)
                onError(it.message ?: "An error occurred")
            }
        }
    }

    fun syncPoints(dryRun: Boolean, since: String?, onSuccess: (String) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, syncLog = emptyList())
            repository.submitAction(AdminPointsActionRequest(
                action = "sync_all",
                dryRun = dryRun,
                since = since
            )).onSuccess { res ->
                if (res.success) {
                    _uiState.value = _uiState.value.copy(syncLog = res.log)
                    onSuccess(res.message ?: "Sync completed")
                    loadData()
                } else {
                    _uiState.value = _uiState.value.copy(isLoading = false)
                    onError(res.error ?: "Failed to sync")
                }
            }.onFailure {
                _uiState.value = _uiState.value.copy(isLoading = false)
                onError(it.message ?: "An error occurred")
            }
        }
    }

    fun clearSyncLog() {
        _uiState.value = _uiState.value.copy(syncLog = emptyList())
    }
}
