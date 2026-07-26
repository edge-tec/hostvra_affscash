package net.affscash.android.ui.admin.traffic_source_override

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import net.affscash.android.data.model.TrafficSourceOverrideData
import net.affscash.android.data.repository.TrafficSourceOverrideRepository
import javax.inject.Inject

data class TrafficSourceOverrideUiState(
    val isLoading: Boolean = false,
    val data: TrafficSourceOverrideData? = null,
    val error: String? = null,
    val actionSuccess: String? = null
)

@HiltViewModel
class TrafficSourceOverrideViewModel @Inject constructor(
    private val repository: TrafficSourceOverrideRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(TrafficSourceOverrideUiState())
    val uiState: StateFlow<TrafficSourceOverrideUiState> = _uiState.asStateFlow()

    fun loadData(isManager: Boolean = false) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            val result = if (isManager) repository.getManagerTrafficSourceOverride() else repository.getAdminTrafficSourceOverride()
            result.onSuccess { response ->
                android.util.Log.d("TSOverride", "API Response: status=${response.status}, affiliates=${response.data?.affiliates?.size}, offers=${response.data?.offers?.size}, advertisers=${response.data?.advertisers?.size}")
                _uiState.update { it.copy(
                    isLoading = false, 
                    data = response.data, 
                    error = if (response.status != "success") response.message else null
                ) }
            }.onFailure { exception ->
                android.util.Log.e("TSOverride", "API Failure: ${exception.message}", exception)
                _uiState.update { it.copy(isLoading = false, error = exception.message ?: "Failed to load traffic source override rules") }
            }
        }
    }

    fun toggleGlobal(isManager: Boolean = false) {
        viewModelScope.launch {
            val body = mapOf("action" to "toggle_global")
            val result = if (isManager) repository.postManagerTrafficSourceOverride(body) else repository.postAdminTrafficSourceOverride(body)
            result.onSuccess {
                loadData(isManager)
            }
        }
    }

    fun toggleRule(ruleId: Int, isManager: Boolean = false) {
        viewModelScope.launch {
            val body = mapOf("action" to "toggle_rule", "rule_id" to ruleId)
            val result = if (isManager) repository.postManagerTrafficSourceOverride(body) else repository.postAdminTrafficSourceOverride(body)
            result.onSuccess {
                loadData(isManager)
            }
        }
    }

    fun deleteRule(ruleId: Int, isManager: Boolean = false) {
        viewModelScope.launch {
            val body = mapOf("action" to "delete_rule", "rule_id" to ruleId)
            val result = if (isManager) repository.postManagerTrafficSourceOverride(body) else repository.postAdminTrafficSourceOverride(body)
            result.onSuccess {
                loadData(isManager)
            }
        }
    }

    fun saveRule(
        name: String,
        targetSources: List<String>,
        overrideSource: String,
        priority: Int,
        affiliateIds: List<Int> = emptyList(),
        offerIds: List<Int> = emptyList(),
        advertiserIds: List<Int> = emptyList(),
        countries: List<String> = emptyList(),
        deviceTypes: List<String> = emptyList(),
        ruleId: Int? = null,
        isManager: Boolean = false
    ) {
        viewModelScope.launch {
            val body = mutableMapOf<String, Any?>(
                "action" to if (ruleId == null) "add_rule" else "edit_rule",
                "name" to name,
                "target_original_sources" to targetSources,
                "override_source" to overrideSource,
                "priority" to priority,
                "enabled" to 1,
                "affiliate_ids" to affiliateIds,
                "offer_ids" to offerIds,
                "advertiser_ids" to advertiserIds,
                "countries" to countries,
                "device_types" to deviceTypes
            )
            if (ruleId != null) body["rule_id"] = ruleId

            val result = if (isManager) repository.postManagerTrafficSourceOverride(body) else repository.postAdminTrafficSourceOverride(body)
            result.onSuccess {
                _uiState.update { it.copy(actionSuccess = "Rule saved successfully") }
                loadData(isManager)
            }
        }
    }

    fun clearActionSuccess() {
        _uiState.update { it.copy(actionSuccess = null) }
    }
}
