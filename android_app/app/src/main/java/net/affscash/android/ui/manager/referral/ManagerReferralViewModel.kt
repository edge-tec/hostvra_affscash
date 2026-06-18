package net.affscash.android.ui.manager.referral

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.ManagerReferralData
import net.affscash.android.data.repository.ReferralRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class ManagerReferralUiState {
    object Loading : ManagerReferralUiState()
    data class Success(val data: ManagerReferralData) : ManagerReferralUiState()
    data class Error(val message: String) : ManagerReferralUiState()
}

@HiltViewModel
class ManagerReferralViewModel @Inject constructor(
    private val repository: ReferralRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<ManagerReferralUiState>(ManagerReferralUiState.Loading)
    val uiState: StateFlow<ManagerReferralUiState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun loadData() {
        viewModelScope.launch {
            _uiState.value = ManagerReferralUiState.Loading
            repository.getManagerReferral()
                .onSuccess { response ->
                    response.data?.let {
                        _uiState.value = ManagerReferralUiState.Success(it)
                    } ?: run {
                        _uiState.value = ManagerReferralUiState.Error("No data returned")
                    }
                }
                .onFailure {
                    _uiState.value = ManagerReferralUiState.Error(it.message ?: "Failed to load manager referral data")
                }
        }
    }
}
