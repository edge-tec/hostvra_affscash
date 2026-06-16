package com.example.affscash.ui.affiliates

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.ManagerAffiliateResponse
import com.example.affscash.data.repository.AffiliateRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject
import com.example.affscash.data.local.UserManager

sealed class ManagerAffiliatesUiState {
    object Loading : ManagerAffiliatesUiState()
    data class Success(val data: ManagerAffiliateResponse) : ManagerAffiliatesUiState()
    data class Error(val message: String) : ManagerAffiliatesUiState()
}

@HiltViewModel
class ManagerAffiliatesViewModel @Inject constructor(
    private val repository: AffiliateRepository,
    private val userManager: UserManager
) : ViewModel() {

    private val _uiState = MutableStateFlow<ManagerAffiliatesUiState>(ManagerAffiliatesUiState.Loading)
    val uiState: StateFlow<ManagerAffiliatesUiState> = _uiState.asStateFlow()

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
            _uiState.value = ManagerAffiliatesUiState.Loading
            repository.getManagerAffiliates(_status.value, _searchQuery.value)
                .onSuccess { response ->
                    _uiState.value = ManagerAffiliatesUiState.Success(response)
                }
                .onFailure { exception ->
                    _uiState.value = ManagerAffiliatesUiState.Error(exception.message ?: "Unknown error")
                }
        }
    }

    fun performAction(action: String, affId: Int, onSuccess: (String?) -> Unit, onError: (String) -> Unit) {
        viewModelScope.launch {
            repository.managerAffiliateAction(action, affId)
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
