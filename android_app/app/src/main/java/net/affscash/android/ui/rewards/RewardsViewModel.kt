package net.affscash.android.ui.rewards

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.RewardsResponse
import net.affscash.android.data.repository.RewardsRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class RewardsState {
    object Loading : RewardsState()
    data class Success(val data: RewardsResponse) : RewardsState()
    data class Error(val message: String) : RewardsState()
}

@HiltViewModel
class RewardsViewModel @Inject constructor(
    private val repository: RewardsRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<RewardsState>(RewardsState.Loading)
    val uiState: StateFlow<RewardsState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun loadData() {
        viewModelScope.launch {
            _uiState.value = RewardsState.Loading
            repository.getRewards().fold(
                onSuccess = { response ->
                    if (response.success) {
                        _uiState.value = RewardsState.Success(response)
                    } else {
                        _uiState.value = RewardsState.Error(response.error ?: "Failed to load rewards")
                    }
                },
                onFailure = { error ->
                    _uiState.value = RewardsState.Error(error.message ?: "Unknown error")
                }
            )
        }
    }
}
