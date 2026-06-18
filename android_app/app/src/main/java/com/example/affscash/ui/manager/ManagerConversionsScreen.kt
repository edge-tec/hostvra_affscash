package com.example.affscash.ui.manager

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Assessment
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.affscash.data.model.Conversion
import com.example.affscash.ui.conversions.ManagerConversionsUiState
import com.example.affscash.ui.conversions.ManagerConversionsViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerConversionsScreen(
    viewModel: ManagerConversionsViewModel = hiltViewModel(),
    onNavigateToDuplicates: () -> Unit = {},
    onNavigateToFraud: () -> Unit = {}
) {
    val uiState by viewModel.uiState.collectAsState()
    val statusFilter by viewModel.statusFilter.collectAsState()
    val searchQuery by viewModel.searchQuery.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Manager Conversions") },
                actions = {
                    IconButton(onClick = onNavigateToFraud) {
                        Icon(Icons.Default.Warning, contentDescription = "Fraud Reports")
                    }
                    IconButton(onClick = onNavigateToDuplicates) {
                        Icon(Icons.Default.Assessment, contentDescription = "Duplicate Conversions")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    actionIconContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
    ) { paddingValues ->
        Column(modifier = Modifier.fillMaxSize().padding(paddingValues)) {
            // Tabs
        ScrollableTabRow(
            selectedTabIndex = when(statusFilter) {
                "all" -> 0
                "pending" -> 1
                "approved" -> 2
                "rejected" -> 3
                else -> 0
            },
            edgePadding = 16.dp,
            modifier = Modifier.fillMaxWidth()
        ) {
            Tab(selected = statusFilter == "all", onClick = { viewModel.setStatusFilter("all") }, text = { Text("All") })
            Tab(selected = statusFilter == "pending", onClick = { viewModel.setStatusFilter("pending") }, text = { Text("Pending") })
            Tab(selected = statusFilter == "approved", onClick = { viewModel.setStatusFilter("approved") }, text = { Text("Approved") })
            Tab(selected = statusFilter == "rejected", onClick = { viewModel.setStatusFilter("rejected") }, text = { Text("Rejected") })
        }

        // Search Bar
        OutlinedTextField(
            value = searchQuery,
            onValueChange = { viewModel.setSearchQuery(it) },
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            placeholder = { Text("Search by Click ID...") },
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search") },
            singleLine = true
        )

        Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
            when (val state = uiState) {
                is ManagerConversionsUiState.Loading -> {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
                is ManagerConversionsUiState.Error -> {
                    Column(
                        modifier = Modifier.align(Alignment.Center),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(text = "Error: ${state.message}", color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadConversions() }) {
                            Text("Retry")
                        }
                    }
                }
                is ManagerConversionsUiState.Success -> {
                    val allConversions = state.data.data
                    val filteredConversions = allConversions.filter {
                        (statusFilter == "all" || it.status.equals(statusFilter, ignoreCase = true)) &&
                        (searchQuery.isBlank() || it.clickId.contains(searchQuery, ignoreCase = true))
                    }

                    if (filteredConversions.isNotEmpty()) {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(horizontal = 16.dp),
                            contentPadding = PaddingValues(vertical = 8.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            items(filteredConversions) { conversion ->
                                ManagerConversionItem(conversion = conversion)
                            }
                        }
                    } else {
                        Text("No conversions found", modifier = Modifier.align(Alignment.Center))
                    }
                }
                }
            }
        }
    }
}

@Composable
fun ManagerConversionItem(conversion: Conversion) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Text(
                    text = conversion.offerName ?: "Unknown Offer",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.weight(1f).padding(end = 8.dp)
                )
                val statusLower = conversion.status.lowercase()
                val containerColor = when(statusLower) {
                    "approved" -> Color(0xFF4CAF50)
                    "rejected" -> MaterialTheme.colorScheme.error
                    else -> Color(0xFFFFA500)
                }
                Box(
                    modifier = Modifier
                        .background(color = containerColor, shape = RoundedCornerShape(12.dp))
                        .padding(horizontal = 8.dp, vertical = 4.dp)
                ) {
                    Text(
                        text = conversion.status.uppercase(),
                        color = Color.White,
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Bold
                    )
                }
            }
            Spacer(modifier = Modifier.height(4.dp))
            Text(text = "Affiliate: ${conversion.affName} (${conversion.affiliateCode})", style = MaterialTheme.typography.bodyMedium)
            
            Spacer(modifier = Modifier.height(8.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(text = "Click ID: ${conversion.clickId.take(16)}...", style = MaterialTheme.typography.bodySmall)
                val ipqsScore = conversion.fraudScore ?: 0
                val ipqsColor = when {
                    ipqsScore > 85 -> MaterialTheme.colorScheme.error
                    ipqsScore > 75 -> MaterialTheme.colorScheme.error
                    ipqsScore > 50 -> MaterialTheme.colorScheme.secondary
                    else -> MaterialTheme.colorScheme.primary
                }
                Text(text = "IPQS: $ipqsScore", style = MaterialTheme.typography.bodySmall, color = ipqsColor, fontWeight = FontWeight.Bold)
            }
            Spacer(modifier = Modifier.height(4.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(text = "Date: ${conversion.convertedAt.take(16)}", style = MaterialTheme.typography.bodySmall)
                Text(text = "Payout: $${(( conversion.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Bold)
            }
        }
    }
}
