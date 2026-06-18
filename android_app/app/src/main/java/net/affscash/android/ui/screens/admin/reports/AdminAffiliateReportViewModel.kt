package net.affscash.android.ui.screens.admin.reports

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.*
import net.affscash.android.data.repository.AdminAffiliateReportRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale
import javax.inject.Inject

data class AdminAffiliateReportState(
    val isLoading: Boolean = false,
    
    // Filters
    val fromDate: String = "",
    val toDate: String = "",
    val affiliateId: String = "",
    val affiliateCode: String = "",
    val affiliateName: String = "",
    val offerId: String = "",
    val country: String = "",
    val convStatus: String = "",
    val device: String = "",
    val trafficStatus: String = "",
    val ip: String = "",
    
    // Filter Options
    val offers: List<AdminReportFilterOption> = emptyList(),
    val affiliates: List<AdminReportFilterOption> = emptyList(),
    val countries: List<String> = emptyList(),
    
    // Data
    val rows: List<AdminAffiliateReportRow> = emptyList(),
    val ipqsStats: Map<String, AdminAffiliateReportIpqsStats> = emptyMap(),
    val trafficDetail: List<AdminAffiliateReportTrafficRow> = emptyList(),
    
    // UI State
    val viewingAffiliateId: String? = null,
    val isFiltersExpanded: Boolean = false,
    val error: String? = null
)

@HiltViewModel
class AdminAffiliateReportViewModel @Inject constructor(
    private val repository: AdminAffiliateReportRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminAffiliateReportState())
    val uiState: StateFlow<AdminAffiliateReportState> = _uiState.asStateFlow()

    init {
        // Set default dates to current month
        val cal = Calendar.getInstance()
        val sdf = SimpleDateFormat("yyyy-MM-dd", Locale.US)
        val toStr = sdf.format(cal.time)
        cal.set(Calendar.DAY_OF_MONTH, 1)
        val fromStr = sdf.format(cal.time)
        
        _uiState.update { it.copy(fromDate = fromStr, toDate = toStr) }
        
        loadFilters()
        loadReport()
    }

    private fun loadFilters() {
        viewModelScope.launch {
            repository.getFilters().onSuccess { response ->
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
            
            repository.getReport(
                from = state.fromDate.takeIf { it.isNotBlank() },
                to = state.toDate.takeIf { it.isNotBlank() },
                affiliateId = state.affiliateId.takeIf { it.isNotBlank() },
                affiliateCode = state.affiliateCode.takeIf { it.isNotBlank() },
                affiliateName = state.affiliateName.takeIf { it.isNotBlank() },
                offerId = state.offerId.takeIf { it.isNotBlank() },
                country = state.country.takeIf { it.isNotBlank() },
                convStatus = state.convStatus.takeIf { it.isNotBlank() },
                device = state.device.takeIf { it.isNotBlank() },
                trafficStatus = state.trafficStatus.takeIf { it.isNotBlank() },
                ip = state.ip.takeIf { it.isNotBlank() }
            ).onSuccess { response ->
                _uiState.update { 
                    it.copy(
                        isLoading = false,
                        rows = response.rows,
                        ipqsStats = response.ipqs_stats,
                        trafficDetail = response.traffic_detail,
                        isFiltersExpanded = false // Collapse filters on success
                    ) 
                }
            }.onFailure { e ->
                _uiState.update { it.copy(isLoading = false, error = e.message) }
            }
        }
    }

    fun updateFilter(filterType: String, value: String) {
        _uiState.update {
            when (filterType) {
                "fromDate" -> it.copy(fromDate = value)
                "toDate" -> it.copy(toDate = value)
                "affiliateId" -> it.copy(affiliateId = value)
                "affiliateCode" -> it.copy(affiliateCode = value)
                "affiliateName" -> it.copy(affiliateName = value)
                "offerId" -> it.copy(offerId = value)
                "country" -> it.copy(country = value)
                "convStatus" -> it.copy(convStatus = value)
                "device" -> it.copy(device = value)
                "trafficStatus" -> it.copy(trafficStatus = value)
                "ip" -> it.copy(ip = value)
                else -> it
            }
        }
    }

    fun toggleFilters() {
        _uiState.update { it.copy(isFiltersExpanded = !it.isFiltersExpanded) }
    }

    fun viewTrafficDetail(affiliateId: String) {
        _uiState.update { it.copy(viewingAffiliateId = affiliateId, affiliateId = affiliateId) }
        loadReport()
    }
    
    fun closeTrafficDetail() {
        _uiState.update { it.copy(viewingAffiliateId = null, affiliateId = "") }
        loadReport()
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }
}
