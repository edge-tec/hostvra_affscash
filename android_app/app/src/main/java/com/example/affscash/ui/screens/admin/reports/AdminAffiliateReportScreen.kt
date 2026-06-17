package com.example.affscash.ui.screens.admin.reports

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.FilterList
import androidx.compose.material.icons.filled.Close
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.affscash.ui.components.CustomDropdownMenu

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminAffiliateReportScreen(
    viewModel: AdminAffiliateReportViewModel = viewModel(),
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(uiState.error) {
        uiState.error?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearError()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (uiState.viewingAffiliateId != null) "Traffic Detail" else "Affiliate Report") },
                navigationIcon = {
                    IconButton(onClick = {
                        if (uiState.viewingAffiliateId != null) {
                            viewModel.closeTrafficDetail()
                        } else {
                            onNavigateBack()
                        }
                    }) {
                        Icon(Icons.Default.ArrowBack, "Back")
                    }
                },
                actions = {
                    if (uiState.viewingAffiliateId == null) {
                        IconButton(onClick = { viewModel.toggleFilters() }) {
                            Icon(if (uiState.isFiltersExpanded) Icons.Default.Close else Icons.Default.FilterList, "Filters")
                        }
                    }
                }
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { padding ->
        Column(modifier = Modifier.fillMaxSize().padding(padding)) {
            
            // Expandable Filters
            AnimatedVisibility(visible = uiState.isFiltersExpanded && uiState.viewingAffiliateId == null) {
                Card(modifier = Modifier.fillMaxWidth().padding(8.dp), elevation = CardDefaults.cardElevation(4.dp)) {
                    Column(modifier = Modifier.padding(12.dp)) {
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedTextField(
                                value = uiState.fromDate,
                                onValueChange = { viewModel.updateFilter("fromDate", it) },
                                label = { Text("From (YYYY-MM-DD)") },
                                modifier = Modifier.weight(1f)
                            )
                            OutlinedTextField(
                                value = uiState.toDate,
                                onValueChange = { viewModel.updateFilter("toDate", it) },
                                label = { Text("To (YYYY-MM-DD)") },
                                modifier = Modifier.weight(1f)
                            )
                        }
                        Spacer(Modifier.height(8.dp))
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            val offerOptions = listOf("" to "All Offers") + uiState.offers.map { it.id.toString() to (it.name ?: "") }
                            CustomDropdownMenu(
                                options = offerOptions,
                                selectedOption = uiState.offerId,
                                onOptionSelected = { viewModel.updateFilter("offerId", it) },
                                label = "Offer",
                                modifier = Modifier.weight(1f)
                            )
                            OutlinedTextField(
                                value = uiState.affiliateCode,
                                onValueChange = { viewModel.updateFilter("affiliateCode", it) },
                                label = { Text("Aff Code") },
                                modifier = Modifier.weight(1f)
                            )
                        }
                        Spacer(Modifier.height(8.dp))
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            CustomDropdownMenu(
                                options = listOf("" to "All Statuses", "approved" to "Approved", "pending" to "Pending", "rejected" to "Rejected"),
                                selectedOption = uiState.convStatus,
                                onOptionSelected = { viewModel.updateFilter("convStatus", it) },
                                label = "Conv Status",
                                modifier = Modifier.weight(1f)
                            )
                            CustomDropdownMenu(
                                options = listOf("" to "All Traffic", "clean" to "Clean", "fraud" to "Fraud", "blocked" to "Blocked"),
                                selectedOption = uiState.trafficStatus,
                                onOptionSelected = { viewModel.updateFilter("trafficStatus", it) },
                                label = "Traffic Status",
                                modifier = Modifier.weight(1f)
                            )
                        }
                        Spacer(Modifier.height(16.dp))
                        androidx.compose.material3.Button(
                            onClick = { viewModel.loadReport() },
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Apply Filters")
                        }
                    }
                }
            }

            if (uiState.isLoading) {
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            }

            if (uiState.viewingAffiliateId != null) {
                // Traffic Detail List
                LazyColumn(contentPadding = PaddingValues(8.dp)) {
                    items(uiState.trafficDetail) { row ->
                        Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
                            Column(modifier = Modifier.padding(12.dp)) {
                                Text("Click ID: ${row.click_id}", fontWeight = FontWeight.Bold)
                                Text("Offer: ${row.offer_name ?: "N/A"}", style = MaterialTheme.typography.bodySmall)
                                Text("IP: ${row.ip_address} (${row.country})", style = MaterialTheme.typography.bodySmall)
                                Text("Device: ${row.device_type} | ${row.os} | ${row.browser}", style = MaterialTheme.typography.bodySmall)
                                Text("Fraud Score: ${row.fraud_score ?: "N/A"} (${if(row.is_fraud == 1) "FRAUD" else "CLEAN"})", style = MaterialTheme.typography.bodySmall, color = if(row.is_fraud == 1) Color.Red else Color(0xFF388E3C))
                                if (row.conv_status != null) {
                                    Text("Conv Status: ${row.conv_status.uppercase()}", style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Bold, color = Color(0xFF1976D2))
                                    Text("Payout: $${row.conv_payout}", style = MaterialTheme.typography.bodySmall)
                                }
                                Text("Time: ${row.clicked_at}", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                            }
                        }
                    }
                }
            } else {
                // Aggregated Affiliate List
                LazyColumn(contentPadding = PaddingValues(8.dp)) {
                    items(uiState.rows) { row ->
                        val ipqs = uiState.ipqsStats[row.affiliate_id.toString()]
                        val cr = if(row.total_clicks.toIntOrNull() ?: 0 > 0) String.format("%.2f", (row.conv_total.toFloat() / row.total_clicks.toFloat()) * 100) else "0.00"
                        val profit = String.format("%.2f", row.revenue.toFloat() - row.payout.toFloat())
                        
                        val fraudClicks = row.fraud_clicks.toIntOrNull() ?: 0
                        val blockedClicks = row.blocked_clicks.toIntOrNull() ?: 0
                        val totalClicks = row.total_clicks.toIntOrNull() ?: 0
                        val badTraffic = if (totalClicks > 0) (fraudClicks + blockedClicks).toFloat() / totalClicks else 0f
                        
                        val tqColor = when {
                            badTraffic >= 0.20f -> Color.Red
                            badTraffic >= 0.05f -> Color(0xFFF57C00) // Orange
                            else -> Color(0xFF388E3C) // Green
                        }
                        val tqLabel = when {
                            badTraffic >= 0.20f -> "Low Quality"
                            badTraffic >= 0.05f -> "Medium Quality"
                            else -> "High Quality"
                        }

                        Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
                            Column(modifier = Modifier.padding(12.dp)) {
                                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                    Column {
                                        Text(row.aff_name, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                                        Text("#${row.affiliate_code} | ${row.email}", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                                    }
                                    Badge(containerColor = if (row.user_status == "active") Color(0xFF388E3C) else Color.Red) {
                                        Text(row.user_status.uppercase(), color = Color.White, modifier = Modifier.padding(horizontal = 4.dp))
                                    }
                                }
                                
                                Divider(modifier = Modifier.padding(vertical = 8.dp))
                                
                                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                    Column {
                                        Text("Clicks", style = MaterialTheme.typography.labelSmall)
                                        Text(row.total_clicks, fontWeight = FontWeight.Bold)
                                    }
                                    Column {
                                        Text("Convs", style = MaterialTheme.typography.labelSmall)
                                        Text(row.conv_total, fontWeight = FontWeight.Bold, color = Color(0xFF1976D2))
                                    }
                                    Column {
                                        Text("CR %", style = MaterialTheme.typography.labelSmall)
                                        Text("$cr%", fontWeight = FontWeight.Bold)
                                    }
                                    Column {
                                        Text("Profit", style = MaterialTheme.typography.labelSmall)
                                        Text("$$profit", fontWeight = FontWeight.Bold, color = Color(0xFF388E3C))
                                    }
                                }
                                
                                Spacer(Modifier.height(8.dp))
                                
                                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                                    Column {
                                        Text("Traffic Quality", style = MaterialTheme.typography.labelSmall)
                                        Text(tqLabel, color = tqColor, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.bodySmall)
                                    }
                                    if (ipqs != null) {
                                        Column {
                                            Text("Avg Fraud Score", style = MaterialTheme.typography.labelSmall)
                                            Text("${ipqs.avg_score ?: "N/A"}", fontWeight = FontWeight.Bold, style = MaterialTheme.typography.bodySmall)
                                        }
                                    }
                                    androidx.compose.material3.Button(onClick = { viewModel.viewTrafficDetail(row.affiliate_id.toString()) }) {
                                        Text("View")
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
