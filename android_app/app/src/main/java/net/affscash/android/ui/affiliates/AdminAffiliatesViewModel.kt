package net.affscash.android.ui.affiliates

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.AdminAffiliateResponse
import net.affscash.android.data.repository.AffiliateRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject
import net.affscash.android.data.local.UserManager

import net.affscash.android.data.model.EditAffiliateRequest
import kotlinx.coroutines.flow.update

data class AdminAffiliatesUiStateData(
    val isLoading: Boolean = false,
    val affiliates: List<net.affscash.android.data.model.AdminAffiliate> = emptyList(),
    val error: String? = null,
    val actionMessage: String? = null,
    
    // Details
    val isDetailsLoading: Boolean = false,
    val selectedAffiliateDetails: net.affscash.android.data.model.ManagerAffiliateDetailsData? = null
)

@HiltViewModel
class AdminAffiliatesViewModel @Inject constructor(
    private val repository: AffiliateRepository,
    private val userManager: UserManager
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminAffiliatesUiStateData())
    val uiState: StateFlow<AdminAffiliatesUiStateData> = _uiState.asStateFlow()

    private val _status = MutableStateFlow("all")
    val status: StateFlow<String> = _status.asStateFlow()

    private val _searchQuery = MutableStateFlow("")
    val searchQuery: StateFlow<String> = _searchQuery.asStateFlow()

    init {
        loadAffiliates()
    }

    fun setStatus(newStatus: String) {
        _status.value = newStatus
        loadAffiliates()
    }

    fun setSearchQuery(query: String) {
        _searchQuery.value = query
        loadAffiliates()
    }

    fun loadAffiliates() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            repository.getAdminAffiliates(_status.value, _searchQuery.value)
                .onSuccess { response ->
                    _uiState.update { it.copy(isLoading = false, affiliates = response.data ?: emptyList()) }
                }
                .onFailure { exception ->
                    _uiState.update { it.copy(isLoading = false, error = exception.message ?: "Unknown error") }
                }
        }
    }

    fun performAction(action: String, userId: Int, onSuccess: (String?) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            repository.adminAffiliateAction(action, userId)
                .onSuccess { response ->
                    if (action == "approve") {
                        loadAffiliates()
                        onSuccess(null)
                    } else if (action == "impersonate") {
                        response.role?.let {
                            userManager.saveIsImpersonating(true)
                            userManager.saveUser(it, response.user?.email ?: "", response.user?.firstName + " " + response.user?.lastName)
                        }
                        onSuccess(response.role)
                    }
                }
                .onFailure { exception ->
                    onError(exception.message ?: "Action failed")
                }
        }
    }

    fun loadAffiliateDetails(affId: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isDetailsLoading = true, selectedAffiliateDetails = null, error = null) }
            repository.getAdminAffiliateDetails(affId)
                .onSuccess { response ->
                    _uiState.update { it.copy(
                        isDetailsLoading = false,
                        selectedAffiliateDetails = response.data
                    ) }
                }
                .onFailure { exception ->
                    _uiState.update { it.copy(
                        isDetailsLoading = false,
                        error = exception.message ?: "Unknown error"
                    ) }
                }
        }
    }

    fun editAffiliate(request: EditAffiliateRequest, onSuccess: () -> Unit) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            repository.editAdminAffiliate(request)
                .onSuccess {
                    _uiState.update { it.copy(isLoading = false, actionMessage = "Affiliate updated successfully") }
                    loadAffiliates()
                    onSuccess()
                }
                .onFailure { exception ->
                    _uiState.update { it.copy(isLoading = false, error = exception.message ?: "Unknown error") }
                }
        }
    }
    
    fun clearActionMessage() {
        _uiState.update { it.copy(actionMessage = null, error = null) }
    }
}
