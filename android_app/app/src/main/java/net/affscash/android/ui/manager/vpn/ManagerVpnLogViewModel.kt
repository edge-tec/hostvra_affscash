package net.affscash.android.ui.manager.vpn

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import net.affscash.android.data.model.VpnLogItem
import net.affscash.android.data.model.VpnLogStats
import net.affscash.android.data.repository.ManagerVpnLogRepository
import javax.inject.Inject

data class ManagerVpnLogUiState(
    val logs: List<VpnLogItem> = emptyList(),
    val stats: VpnLogStats? = null,
    val isLoadingLogs: Boolean = false,
    val isLoadingStats: Boolean = false,
    val isClearing: Boolean = false,
    val error: String? = null,
    val clearSuccessMessage: String? = null,
    
    // Filters
    val filterIp: String = "",
    val filterAffiliate: String = "",
    val filterType: String = "",
    val filterDateFrom: String = "",
    val filterDateTo: String = ""
)

@HiltViewModel
class ManagerVpnLogViewModel @Inject constructor(
    private val repository: ManagerVpnLogRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(ManagerVpnLogUiState())
    val uiState: StateFlow<ManagerVpnLogUiState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun loadData() {
        loadStats()
        loadLogs()
    }

    private fun loadStats() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingStats = true, error = null) }
            repository.getVpnLogStats().fold(
                onSuccess = { res ->
                    if (res.success) {
                        _uiState.update { it.copy(stats = res.data, isLoadingStats = false) }
                    } else {
                        _uiState.update { it.copy(error = res.error ?: "Unknown error", isLoadingStats = false) }
                    }
                },
                onFailure = { e ->
                    _uiState.update { it.copy(error = e.message ?: "Failed to load stats", isLoadingStats = false) }
                }
            )
        }
    }

    fun loadLogs() {
        viewModelScope.launch {
            val state = _uiState.value
            _uiState.update { it.copy(isLoadingLogs = true, error = null) }
            
            val typeParam = if (state.filterType == "All Types" || state.filterType.isEmpty()) null else state.filterType
            
            repository.getVpnLogs(
                ip = state.filterIp.takeIf { it.isNotBlank() },
                affiliate = state.filterAffiliate.takeIf { it.isNotBlank() },
                type = typeParam,
                dateFrom = state.filterDateFrom.takeIf { it.isNotBlank() },
                dateTo = state.filterDateTo.takeIf { it.isNotBlank() }
            ).fold(
                onSuccess = { res ->
                    if (res.success) {
                        _uiState.update { it.copy(logs = res.data ?: emptyList(), isLoadingLogs = false) }
                    } else {
                        _uiState.update { it.copy(error = res.error ?: "Unknown error", isLoadingLogs = false) }
                    }
                },
                onFailure = { e ->
                    _uiState.update { it.copy(error = e.message ?: "Failed to load logs", isLoadingLogs = false) }
                }
            )
        }
    }

    fun updateFilters(ip: String, affiliate: String, type: String, dateFrom: String, dateTo: String) {
        _uiState.update { 
            it.copy(
                filterIp = ip,
                filterAffiliate = affiliate,
                filterType = type,
                filterDateFrom = dateFrom,
                filterDateTo = dateTo
            )
        }
        loadLogs()
    }

    fun clearOldLogs() {
        viewModelScope.launch {
            _uiState.update { it.copy(isClearing = true, error = null, clearSuccessMessage = null) }
            repository.clearOldLogs().fold(
                onSuccess = { res ->
                    if (res.success) {
                        _uiState.update { it.copy(isClearing = false, clearSuccessMessage = res.message) }
                        loadData() // refresh
                    } else {
                        _uiState.update { it.copy(error = res.error ?: "Failed to clear logs", isClearing = false) }
                    }
                },
                onFailure = { e ->
                    _uiState.update { it.copy(error = e.message ?: "Failed to clear logs", isClearing = false) }
                }
            )
        }
    }
    
    fun clearMessage() {
        _uiState.update { it.copy(clearSuccessMessage = null) }
    }
}
