package com.example.affscash.ui.screens.admin.reports

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.AdminReportFilterOption
import com.example.affscash.data.model.AdminReportTotals
import com.example.affscash.data.repository.AdminReportRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.serialization.json.JsonObject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.time.LocalDate
import java.time.format.DateTimeFormatter
import javax.inject.Inject

data class AdminReportsState(
    val isLoading: Boolean = false,
    val tab: String = "performance",
    
    // Filters state
    val from: String = LocalDate.now().withDayOfMonth(1).format(DateTimeFormatter.ISO_LOCAL_DATE),
    val to: String = LocalDate.now().format(DateTimeFormatter.ISO_LOCAL_DATE),
    val groupBy: String = "date",
    val offerId: Int? = null,
    val affiliateId: Int? = null,
    val country: String? = null,
    val sub1: String = "",
    val slId: Int? = null,
    
    // Filter Options
    val offers: List<AdminReportFilterOption> = emptyList(),
    val affiliates: List<AdminReportFilterOption> = emptyList(),
    val countries: List<String> = emptyList(),
    
    // Data
    val totals: AdminReportTotals? = null,
    val rows: List<JsonObject> = emptyList(),
    val error: String? = null
)

@HiltViewModel
class AdminReportsViewModel @Inject constructor(
    private val repository: AdminReportRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminReportsState())
    val uiState: StateFlow<AdminReportsState> = _uiState.asStateFlow()

    init {
        loadFilters()
        loadReport()
    }

    private fun loadFilters() {
        viewModelScope.launch {
            val result = repository.getFilters()
            result.onSuccess { response ->
                _uiState.update { 
                    it.copy(
                        offers = response.offers,
                        affiliates = response.affiliates,
                        countries = response.countries
                    ) 
                }
            }
        }
    }

    fun loadReport() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, error = null) }
            val state = _uiState.value
            val result = repository.getReports(
                tab = state.tab,
                from = state.from,
                to = state.to,
                groupBy = state.groupBy,
                offerId = state.offerId,
                affiliateId = state.affiliateId,
                country = state.country,
                sub1 = state.sub1.ifBlank { null },
                slId = state.slId
            )
            result.onSuccess { response ->
                _uiState.update { 
                    it.copy(
                        isLoading = false,
                        totals = response.totals,
                        rows = response.rows
                    ) 
                }
            }.onFailure { e ->
                _uiState.update { it.copy(isLoading = false, error = e.message ?: "Failed to load report") }
            }
        }
    }

    fun updateTab(tab: String) {
        _uiState.update { it.copy(tab = tab) }
        loadReport()
    }

    fun updateFilter(
        from: String? = null,
        to: String? = null,
        groupBy: String? = null,
        offerId: Int? = null,
        affiliateId: Int? = null,
        country: String? = null,
        sub1: String? = null,
        slId: Int? = null,
        clearOffer: Boolean = false,
        clearAffiliate: Boolean = false,
        clearCountry: Boolean = false,
        clearSlId: Boolean = false
    ) {
        _uiState.update { state ->
            state.copy(
                from = from ?: state.from,
                to = to ?: state.to,
                groupBy = groupBy ?: state.groupBy,
                offerId = if (clearOffer) null else (offerId ?: state.offerId),
                affiliateId = if (clearAffiliate) null else (affiliateId ?: state.affiliateId),
                country = if (clearCountry) null else (country ?: state.country),
                sub1 = sub1 ?: state.sub1,
                slId = if (clearSlId) null else (slId ?: state.slId)
            )
        }
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }
}
