package com.example.affscash.ui.manager

import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.affscash.data.model.DuplicateConversionGroup
import com.example.affscash.data.model.DuplicateConversionRow
import com.example.affscash.data.network.RetrofitClient
import com.example.affscash.data.repository.ManagerReportRepository
import java.text.SimpleDateFormat
import java.util.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerDuplicateConversionsScreen(
    onNavigateBack: () -> Unit = {}
) {
    val apiService = RetrofitClient.getApiService()
    val repository = remember { ManagerReportRepository(apiService) }
    val viewModel: ManagerDuplicateConversionsViewModel = viewModel(factory = ManagerDuplicateConversionsViewModelFactory(repository))

    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Duplicate Conversions", fontSize = 18.sp) },
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
            
            // Date Range Picker Strip
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(8.dp)
                    .horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                val dateRanges = listOf("Today", "Yesterday", "Last 7 Days", "This Month", "Last 30 Days")
                dateRanges.forEach { range ->
                    FilterChip(
                        selected = false,
                        onClick = {
                            val cal = Calendar.getInstance()
                            val sdf = SimpleDateFormat("yyyy-MM-dd", Locale.US)
                            val to = sdf.format(cal.time)
                            val from = when (range) {
                                "Today" -> to
                                "Yesterday" -> { cal.add(Calendar.DAY_OF_YEAR, -1); sdf.format(cal.time).also { cal.add(Calendar.DAY_OF_YEAR, 1) } }
                                "Last 7 Days" -> { cal.add(Calendar.DAY_OF_YEAR, -7); sdf.format(cal.time).also { cal.add(Calendar.DAY_OF_YEAR, 7) } }
                                "This Month" -> { cal.set(Calendar.DAY_OF_MONTH, 1); sdf.format(cal.time) }
                                "Last 30 Days" -> { cal.add(Calendar.DAY_OF_YEAR, -30); sdf.format(cal.time).also { cal.add(Calendar.DAY_OF_YEAR, 30) } }
                                else -> to
                            }
                            if (range == "Yesterday") {
                                viewModel.setDateRange(from, from)
                            } else {
                                viewModel.setDateRange(from, to)
                            }
                        },
                        label = { Text(range) }
                    )
                }
            }

            // Summary Info
            Row(
                modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(text = "From: ${uiState.fromDate}", fontSize = 12.sp, color = Color.Gray)
                Text(text = "To: ${uiState.toDate}", fontSize = 12.sp, color = Color.Gray)
            }
            
            Spacer(modifier = Modifier.height(8.dp))

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
                        // Totals section
                        Row(modifier = Modifier.fillMaxWidth().padding(8.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            Card(modifier = Modifier.weight(1f)) {
                                Column(modifier = Modifier.padding(12.dp)) {
                                    Text("DUPLICATE CLUSTERS", fontSize = 10.sp, color = Color.Gray, fontWeight = FontWeight.Bold)
                                    Text("${uiState.response!!.totalGroups}", fontSize = 20.sp, fontWeight = FontWeight.Bold)
                                    Text("Unique (offer + IP) groups", fontSize = 10.sp, color = Color.Gray)
                                }
                            }
                            Card(modifier = Modifier.weight(1f)) {
                                Column(modifier = Modifier.padding(12.dp)) {
                                    Text("DUPLICATE CONVERSIONS", fontSize = 10.sp, color = Color.Gray, fontWeight = FontWeight.Bold)
                                    Text("${uiState.response!!.totalRows}", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = Color(0xFFEF4444))
                                    Text("Total flagged rows", fontSize = 10.sp, color = Color.Gray)
                                }
                            }
                        }

                        // List of clusters
                        LazyColumn(modifier = Modifier.fillMaxSize()) {
                            if (uiState.response!!.groups.isEmpty()) {
                                item {
                                    Text(
                                        "No duplicate conversions found for this date range.",
                                        modifier = Modifier.padding(16.dp),
                                        color = Color.Gray
                                    )
                                }
                            } else {
                                items(uiState.response!!.groups) { group ->
                                    DuplicateClusterView(group = group)
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
fun DuplicateClusterView(group: DuplicateConversionGroup) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 8.dp, vertical = 4.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
    ) {
        Column(modifier = Modifier.padding(0.dp)) {
            // Header
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(Color(0xFFFEF2F2))
                    .padding(8.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Box(
                    modifier = Modifier
                        .background(Color(0xFFDC2626), shape = MaterialTheme.shapes.small)
                        .padding(horizontal = 6.dp, vertical = 2.dp)
                ) {
                    Text(
                        "${group.dupCount} DUPLICATES",
                        color = Color.White,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold
                    )
                }
                Spacer(modifier = Modifier.width(8.dp))
                Text(
                    "Offer: ${group.offerName} · IP: ${group.ipAddress}",
                    fontSize = 12.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color(0xFF991B1B)
                )
            }

            // Rows
            Column(modifier = Modifier.padding(8.dp)) {
                group.conversions.forEachIndexed { index, row ->
                    DuplicateRowView(row = row)
                    if (index < group.conversions.size - 1) {
                        Divider(modifier = Modifier.padding(vertical = 4.dp))
                    }
                }
            }
        }
    }
}

@Composable
fun DuplicateRowView(row: DuplicateConversionRow) {
    Column {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Text("Conv ID: ${row.conversionId}", fontSize = 11.sp, fontWeight = FontWeight.Bold)
            Text(row.convertedAt, fontSize = 10.sp, color = Color.Gray)
        }
        Spacer(modifier = Modifier.height(2.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Text("Affiliate: ${row.affiliateName} (#${row.affiliateId})", fontSize = 12.sp)
            Text("$${row.payout}", fontSize = 12.sp, fontWeight = FontWeight.Bold)
        }
        Spacer(modifier = Modifier.height(2.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Text(
                row.status.uppercase(),
                fontSize = 10.sp,
                color = if (row.status == "rejected") Color(0xFFDC2626) else if (row.status == "approved") Color(0xFF10B981) else Color.Gray,
                fontWeight = FontWeight.Bold
            )
            Text("Txn: ${row.transactionId ?: "-"}", fontSize = 10.sp, color = Color.Gray)
        }
    }
}
