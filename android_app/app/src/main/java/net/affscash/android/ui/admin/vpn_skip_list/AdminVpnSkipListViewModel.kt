package net.affscash.android.ui.admin.vpn_skip_list

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import net.affscash.android.data.model.VpnSkipListData
import net.affscash.android.data.repository.VpnSkipListRepository
import javax.inject.Inject

data class AdminVpnSkipListUiState(
    val isLoading: Boolean = false,
    val skipData: VpnSkipListData? = null,
    val error: String? = null,
    val actionMessage: String? = null
)

@HiltViewModel
class AdminVpnSkipListViewModel @Inject constructor(
    private val repository: VpnSkipListRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminVpnSkipListUiState())
    val uiState: StateFlow<AdminVpnSkipListUiState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun loadData() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            val result = repository.getVpnSkipList()
            result.onSuccess { response ->
                _uiState.update { it.copy(isLoading = false, skipData = response.data, error = response.message) }
            }.onFailure { exception ->
                _uiState.update { it.copy(isLoading = false, error = exception.message ?: "Failed to load VPN skip list") }
            }
        }
    }

    fun addEntry(affiliateId: Int, note: String?) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            val result = repository.addVpnSkipEntry(affiliateId, note)
            result.onSuccess { response ->
                _uiState.update { it.copy(actionMessage = response.message ?: "Affiliate added to skip list") }
                loadData()
            }.onFailure { exception ->
                _uiState.update { it.copy(isLoading = false, error = exception.message ?: "Failed to add affiliate") }
            }
        }
    }

    fun removeEntry(id: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            val result = repository.removeVpnSkipEntry(id)
            result.onSuccess { response ->
                _uiState.update { it.copy(actionMessage = response.message ?: "Affiliate removed from skip list") }
                loadData()
            }.onFailure { exception ->
                _uiState.update { it.copy(isLoading = false, error = exception.message ?: "Failed to remove affiliate") }
            }
        }
    }

    fun clearActionMessage() {
        _uiState.update { it.copy(actionMessage = null) }
    }
}
