package net.affscash.android.ui.affiliate.duplicate_conversions

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.AffiliateDuplicateConversionsData
import net.affscash.android.data.repository.DuplicateConversionsRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.*
import javax.inject.Inject

sealed class DuplicateConversionsUiState {
    object Loading : DuplicateConversionsUiState()
    data class Success(val data: AffiliateDuplicateConversionsData) : DuplicateConversionsUiState()
    data class Error(val message: String) : DuplicateConversionsUiState()
}

@HiltViewModel
class DuplicateConversionsViewModel @Inject constructor(
    private val repository: DuplicateConversionsRepository
) : ViewModel() {

    private val dateFormat = SimpleDateFormat("yyyy-MM-dd", Locale.US)

    private val _uiState = MutableStateFlow<DuplicateConversionsUiState>(DuplicateConversionsUiState.Loading)
    val uiState: StateFlow<DuplicateConversionsUiState> = _uiState.asStateFlow()

    private val _fromDate = MutableStateFlow(getFirstDayOfMonth())
    val fromDate: StateFlow<String> = _fromDate.asStateFlow()

    private val _toDate = MutableStateFlow(dateFormat.format(Date()))
    val toDate: StateFlow<String> = _toDate.asStateFlow()

    init {
        loadData()
    }

    private fun getFirstDayOfMonth(): String {
        val calendar = Calendar.getInstance()
        calendar.set(Calendar.DAY_OF_MONTH, 1)
        return dateFormat.format(calendar.time)
    }

    fun setFromDate(date: String) {
        _fromDate.value = date
    }

    fun setToDate(date: String) {
        _toDate.value = date
    }

    fun loadData() {
        viewModelScope.launch {
            _uiState.value = DuplicateConversionsUiState.Loading
            repository.getDuplicateConversions(_fromDate.value, _toDate.value)
                .onSuccess { response ->
                    response.data?.let {
                        _uiState.value = DuplicateConversionsUiState.Success(it)
                    } ?: run {
                        _uiState.value = DuplicateConversionsUiState.Error("No data returned")
                    }
                }
                .onFailure {
                    _uiState.value = DuplicateConversionsUiState.Error(it.message ?: "Failed to load duplicate conversions")
                }
        }
    }
}
