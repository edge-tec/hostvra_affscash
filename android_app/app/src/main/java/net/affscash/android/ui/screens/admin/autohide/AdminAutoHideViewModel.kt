package net.affscash.android.ui.screens.admin.autohide

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.*
import net.affscash.android.data.repository.AdminAutoHideRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class AdminAutoHideState(
    val isLoading: Boolean = false,
    val tab: String = "rules", // "rules", "hidden", "new_rule"
    
    // Data
    val stats: AdminAutoHideStats? = null,
    val rules: List<AdminAutoHideRule> = emptyList(),
    val hiddenConversions: List<AdminAutoHideConversion> = emptyList(),
    
    // Filters for form
    val offers: List<AdminReportFilterOption> = emptyList(),
    val affiliates: List<AdminReportFilterOption> = emptyList(),
    
    // UI Feedback
    val error: String? = null,
    val successMessage: String? = null
)

@HiltViewModel
class AdminAutoHideViewModel @Inject constructor(
    private val repository: AdminAutoHideRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminAutoHideState())
    val uiState: StateFlow<AdminAutoHideState> = _uiState.asStateFlow()

    init {
        loadData()
        loadFilters()
    }

    fun loadData() {
        loadStats()
        when (_uiState.value.tab) {
            "rules" -> loadRules()
            "hidden" -> loadHiddenConversions()
        }
    }

    fun updateTab(tab: String) {
        _uiState.update { it.copy(tab = tab) }
        loadData()
    }

    private fun loadStats() {
        viewModelScope.launch {
            repository.getStats().onSuccess { response ->
                _uiState.update { it.copy(stats = response.stats) }
            }
        }
    }

    private fun loadRules() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            repository.getRules().onSuccess { response ->
                _uiState.update { it.copy(isLoading = false, rules = response.rules) }
            }.onFailure { e ->
                _uiState.update { it.copy(isLoading = false, error = e.message) }
            }
        }
    }

    private fun loadHiddenConversions() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            repository.getHiddenConversions().onSuccess { response ->
                _uiState.update { it.copy(isLoading = false, hiddenConversions = response.conversions) }
            }.onFailure { e ->
                _uiState.update { it.copy(isLoading = false, error = e.message) }
            }
        }
    }

    private fun loadFilters() {
        viewModelScope.launch {
            repository.getFilters().onSuccess { response ->
                _uiState.update { 
                    it.copy(
                        offers = response.offers,
                        affiliates = response.affiliates
                    ) 
                }
            }
        }
    }

    fun createRule(request: AdminAutoHideCreateRequest) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            repository.createRule(request).onSuccess { response ->
                _uiState.update { it.copy(isLoading = false, successMessage = response.message, tab = "rules") }
                loadData()
            }.onFailure { e ->
                _uiState.update { it.copy(isLoading = false, error = e.message) }
            }
        }
    }

    fun toggleRule(ruleId: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            repository.toggleRule(ruleId).onSuccess { response ->
                _uiState.update { it.copy(isLoading = false, successMessage = response.message) }
                loadRules()
                loadStats()
            }.onFailure { e ->
                _uiState.update { it.copy(isLoading = false, error = e.message) }
            }
        }
    }

    fun deleteRule(ruleId: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            repository.deleteRule(ruleId).onSuccess { response ->
                _uiState.update { it.copy(isLoading = false, successMessage = response.message) }
                loadRules()
                loadStats()
            }.onFailure { e ->
                _uiState.update { it.copy(isLoading = false, error = e.message) }
            }
        }
    }

    fun unhideConversion(conversionId: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            repository.unhideConversion(conversionId).onSuccess { response ->
                _uiState.update { it.copy(isLoading = false, successMessage = response.message) }
                loadHiddenConversions()
                loadStats()
            }.onFailure { e ->
                _uiState.update { it.copy(isLoading = false, error = e.message) }
            }
        }
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }

    fun clearSuccessMessage() {
        _uiState.update { it.copy(successMessage = null) }
    }
}
