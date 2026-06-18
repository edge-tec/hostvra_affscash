package net.affscash.android.ui.affiliate.referral

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.AffiliateReferralData
import net.affscash.android.data.repository.ReferralRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class AffiliateReferralUiState {
    object Loading : AffiliateReferralUiState()
    data class Success(val data: AffiliateReferralData) : AffiliateReferralUiState()
    data class Error(val message: String) : AffiliateReferralUiState()
}

@HiltViewModel
class AffiliateReferralViewModel @Inject constructor(
    private val repository: ReferralRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<AffiliateReferralUiState>(AffiliateReferralUiState.Loading)
    val uiState: StateFlow<AffiliateReferralUiState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun loadData() {
        viewModelScope.launch {
            _uiState.value = AffiliateReferralUiState.Loading
            repository.getAffiliateReferral()
                .onSuccess { response ->
                    response.data?.let {
                        _uiState.value = AffiliateReferralUiState.Success(it)
                    } ?: run {
                        _uiState.value = AffiliateReferralUiState.Error("No data returned")
                    }
                }
                .onFailure {
                    _uiState.value = AffiliateReferralUiState.Error(it.message ?: "Failed to load referral data")
                }
        }
    }
}
