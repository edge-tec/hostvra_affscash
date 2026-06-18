package net.affscash.android.ui.manager

import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.ManagerFraudConversion
import net.affscash.android.data.model.ManagerFraudTotals

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerFraudReportsScreen(
    onNavigateBack: () -> Unit = {},
    viewModel: ManagerFraudReportsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Fraud Reports", fontSize = 18.sp) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    navigationIconContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            
            // Search and Filter Bar
            Column(modifier = Modifier.fillMaxWidth().padding(8.dp)) {
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 8.dp),
                    elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f)),
                    shape = MaterialTheme.shapes.medium
                ) {
                    OutlinedTextField(
                        value = uiState.searchQuery,
                        onValueChange = { viewModel.setSearchQuery(it) },
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        placeholder = { Text("Search by Conv ID, IP, or click ID...") },
                        leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search", tint = MaterialTheme.colorScheme.primary) },
                        trailingIcon = {
                            if (uiState.searchQuery.isNotEmpty()) {
                                IconButton(onClick = { viewModel.setSearchQuery("") }) {
                                    Icon(Icons.Default.Clear, contentDescription = "Clear")
                                }
                            }
                        },
                        singleLine = true,
                        shape = MaterialTheme.shapes.medium,
                        colors = OutlinedTextFieldDefaults.colors(
                            unfocusedBorderColor = Color.Transparent,
                            focusedBorderColor = MaterialTheme.colorScheme.primary,
                            unfocusedContainerColor = MaterialTheme.colorScheme.surface,
                            focusedContainerColor = MaterialTheme.colorScheme.surface
                        )
                    )
                }
                
                Spacer(modifier = Modifier.height(8.dp))
                
                Row(
                    modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    val statuses = listOf("All Statuses", "Approved", "Pending", "Rejected")
                    statuses.forEach { status ->
                        FilterChip(
                            selected = uiState.statusFilter == status,
                            onClick = { viewModel.setStatusFilter(status) },
                            label = { Text(status) }
                        )
                    }
                }
            }

            // Loading / Error / Data
            Box(modifier = Modifier.fillMaxSize().weight(1f)) {
                if (uiState.isLoading) {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                } else if (uiState.error != null) {
                    Column(
                        modifier = Modifier.align(Alignment.Center),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(uiState.error!!, color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadReport() }) {
                            Text("Retry")
                        }
                    }
                } else if (uiState.response != null) {
                    Column(modifier = Modifier.fillMaxSize()) {
                        // Totals summary boxes (horizontally scrollable)
                        uiState.response!!.totals?.let { totals ->
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .horizontalScroll(rememberScrollState())
                                    .padding(horizontal = 8.dp),
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                SummaryCard("TOTAL", totals.total.toString(), Color.Black)
                                SummaryCard("APPROVED", totals.approved.toString(), Color(0xFF10B981))
                                SummaryCard("PENDING", totals.pending.toString(), Color(0xFFF59E0B))
                                SummaryCard("BLOCKED", totals.blocked.toString(), Color(0xFFDC2626))
                                SummaryCard("FRAUD FLAGGED", totals.fraudFlagged.toString(), Color(0xFFDC2626))
                                SummaryCard("PAYOUT", "$${(( totals.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", Color.Black)
                            }
                        }
                        
                        Spacer(modifier = Modifier.height(8.dp))

                        // Filtered conversions
                        val filteredConversions = uiState.response!!.conversions.filter { cv ->
                            val matchStatus = uiState.statusFilter == "All Statuses" || cv.status.equals(uiState.statusFilter, ignoreCase = true)
                            val q = uiState.searchQuery.lowercase()
                            val matchQuery = q.isEmpty() || 
                                             cv.conversionId.lowercase().contains(q) || 
                                             cv.clickId.lowercase().contains(q) || 
                                             (cv.ipAddress ?: "").lowercase().contains(q)
                            matchStatus && matchQuery
                        }

                        LazyColumn(modifier = Modifier.fillMaxSize()) {
                            if (filteredConversions.isEmpty()) {
                                item {
                                    Text(
                                        "No conversions match the filters.",
                                        modifier = Modifier.padding(16.dp),
                                        color = Color.Gray
                                    )
                                }
                            } else {
                                items(filteredConversions) { cv ->
                                    ManagerFraudConversionItem(cv)
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun SummaryCard(title: String, value: String, valueColor: Color) {
    Card(modifier = Modifier.width(120.dp)) {
        Column(
            modifier = Modifier.padding(12.dp).fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(value, fontSize = 20.sp, fontWeight = FontWeight.Bold, color = valueColor)
            Text(title, fontSize = 10.sp, color = Color.Gray, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
fun ManagerFraudConversionItem(cv: ManagerFraudConversion) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 8.dp, vertical = 4.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            // Header: Conv ID & Date
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(cv.conversionId.take(16) + "...", fontWeight = FontWeight.Bold, fontSize = 12.sp, color = MaterialTheme.colorScheme.primary)
                Text(cv.convertedAt, fontSize = 10.sp, color = Color.Gray)
            }
            
            Spacer(modifier = Modifier.height(4.dp))
            
            // Affiliate & Offer
            Text("Affiliate: ${cv.affName} (${cv.affiliateCode})", fontSize = 12.sp, fontWeight = FontWeight.Bold)
            Text("Offer: ${cv.offerName ?: "Unknown"}", fontSize = 11.sp)
            
            Spacer(modifier = Modifier.height(4.dp))
            
            // IP & Click ID
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("IP: ${cv.ipAddress ?: "-"}", fontSize = 11.sp, color = Color.Blue)
                Text("Click ID: ${cv.clickId.take(12)}...", fontSize = 10.sp, color = Color.Gray)
            }
            
            Spacer(modifier = Modifier.height(4.dp))
            
            // Device info
            Text("${cv.deviceType ?: "Unknown"} · ${cv.osVersion ?: "Unknown OS"}", fontSize = 11.sp, color = Color.DarkGray)
            Text(cv.userAgent?.take(50) ?: "Unknown UA", fontSize = 10.sp, color = Color.Gray)
            
            Spacer(modifier = Modifier.height(8.dp))
            
            // Metrics row
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column {
                    Text("Payout", fontSize = 9.sp, color = Color.Gray)
                    Text("$${(( cv.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                }
                
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text("IPQS Score", fontSize = 9.sp, color = Color.Gray)
                    val score = cv.ipqsScore ?: 0
                    val scoreColor = if (score > 80) Color.Red else if (score > 50) Color(0xFFF59E0B) else Color(0xFF10B981)
                    Text("$score", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = scoreColor)
                }
                
                Column(horizontalAlignment = Alignment.End) {
                    Text("Status", fontSize = 9.sp, color = Color.Gray)
                    val statusColor = if (cv.status == "rejected") Color.Red else if (cv.status == "approved") Color(0xFF10B981) else Color(0xFFF59E0B)
                    Text(cv.status.uppercase(), fontSize = 12.sp, fontWeight = FontWeight.Bold, color = statusColor)
                }
            }
            
            // Rejection reason if any
            if (cv.status == "rejected" && cv.rejectionReason.isNotEmpty()) {
                Spacer(modifier = Modifier.height(4.dp))
                Text("Reason: ${cv.rejectionReason}", fontSize = 10.sp, color = Color.Red)
            }
        }
    }
}
