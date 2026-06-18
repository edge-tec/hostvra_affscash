package net.affscash.android.ui.admin.referral

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import net.affscash.android.data.model.*
import net.affscash.android.data.repository.AdminReferralRepository
import javax.inject.Inject

@HiltViewModel
class AdminReferralViewModel @Inject constructor(
    private val repository: AdminReferralRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AdminReferralUiState>(AdminReferralUiState.Loading)
    val uiState: StateFlow<AdminReferralUiState> = _uiState.asStateFlow()

    private val _dashboardData = MutableStateFlow<AdminReferralDashboardData?>(null)
    val dashboardData: StateFlow<AdminReferralDashboardData?> = _dashboardData.asStateFlow()

    private val _signups = MutableStateFlow<List<AdminReferralSignup>>(emptyList())
    val signups: StateFlow<List<AdminReferralSignup>> = _signups.asStateFlow()

    private val _commissions = MutableStateFlow<List<AdminReferralCommission>>(emptyList())
    val commissions: StateFlow<List<AdminReferralCommission>> = _commissions.asStateFlow()

    private val _commissionTotals = MutableStateFlow<AdminReferralCommissionTotals?>(null)
    val commissionTotals: StateFlow<AdminReferralCommissionTotals?> = _commissionTotals.asStateFlow()

    private val _codes = MutableStateFlow<List<AdminReferralCode>>(emptyList())
    val codes: StateFlow<List<AdminReferralCode>> = _codes.asStateFlow()

    private val _currentTab = MutableStateFlow(0)
    val currentTab: StateFlow<Int> = _currentTab.asStateFlow()

    init {
        loadData()
    }

    fun setTab(index: Int) {
        _currentTab.value = index
        loadTabData()
    }

    fun loadData() {
        viewModelScope.launch {
            _uiState.value = AdminReferralUiState.Loading
            repository.getDashboard().collect { result ->
                result.onSuccess { response ->
                    _dashboardData.value = response.data
                    loadTabData()
                }.onFailure {
                    _uiState.value = AdminReferralUiState.Error(it.message ?: "Unknown error")
                }
            }
        }
    }

    private fun loadTabData() {
        viewModelScope.launch {
            _uiState.value = AdminReferralUiState.Loading
            when (_currentTab.value) {
                0 -> {
                    repository.getSignups().collect { result ->
                        result.onSuccess {
                            _signups.value = it.data?.signups ?: emptyList()
                            _uiState.value = AdminReferralUiState.Success
                        }.onFailure {
                            _uiState.value = AdminReferralUiState.Error(it.message ?: "Error")
                        }
                    }
                }
                1 -> {
                    repository.getCommissions().collect { result ->
                        result.onSuccess {
                            _commissions.value = it.data?.commissions ?: emptyList()
                            _commissionTotals.value = it.data?.totals
                            _uiState.value = AdminReferralUiState.Success
                        }.onFailure {
                            _uiState.value = AdminReferralUiState.Error(it.message ?: "Error")
                        }
                    }
                }
                2 -> {
                    repository.getCodes().collect { result ->
                        result.onSuccess {
                            _codes.value = it.data?.codes ?: emptyList()
                            _uiState.value = AdminReferralUiState.Success
                        }.onFailure {
                            _uiState.value = AdminReferralUiState.Error(it.message ?: "Error")
                        }
                    }
                }
            }
        }
    }

    fun approveCommission(id: Int) {
        viewModelScope.launch {
            _uiState.value = AdminReferralUiState.Loading
            repository.approveCommission(id).collect { result ->
                result.onSuccess {
                    loadData()
                }.onFailure {
                    _uiState.value = AdminReferralUiState.Error(it.message ?: "Error approving commission")
                }
            }
        }
    }

    fun rejectCommission(id: Int) {
        viewModelScope.launch {
            _uiState.value = AdminReferralUiState.Loading
            repository.rejectCommission(id).collect { result ->
                result.onSuccess {
                    loadData()
                }.onFailure {
                    _uiState.value = AdminReferralUiState.Error(it.message ?: "Error rejecting commission")
                }
            }
        }
    }
}

sealed class AdminReferralUiState {
    object Loading : AdminReferralUiState()
    object Success : AdminReferralUiState()
    data class Error(val message: String) : AdminReferralUiState()
}
