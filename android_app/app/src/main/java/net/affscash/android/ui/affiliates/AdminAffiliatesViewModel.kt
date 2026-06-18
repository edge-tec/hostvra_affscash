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

sealed class AdminAffiliatesUiState {
    object Loading : AdminAffiliatesUiState()
    data class Success(val data: AdminAffiliateResponse) : AdminAffiliatesUiState()
    data class Error(val message: String) : AdminAffiliatesUiState()
}

@HiltViewModel
class AdminAffiliatesViewModel @Inject constructor(
    private val repository: AffiliateRepository,
    private val userManager: UserManager
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminAffiliatesUiState>(AdminAffiliatesUiState.Loading)
    val uiState: StateFlow<AdminAffiliatesUiState> = _uiState.asStateFlow()

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
            _uiState.value = AdminAffiliatesUiState.Loading
            repository.getAdminAffiliates(_status.value, _searchQuery.value)
                .onSuccess { response ->
                    _uiState.value = AdminAffiliatesUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = AdminAffiliatesUiState.Error(exception.message ?: "Unknown error")
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
                        response.role?.let { userManager.saveUser(it, response.user?.email ?: "", response.user?.firstName + " " + response.user?.lastName) }
                        onSuccess(response.role)
                    }
                }
                .onFailure { exception ->
                    onError(exception.message ?: "Action failed")
                }
        }
    }
}
