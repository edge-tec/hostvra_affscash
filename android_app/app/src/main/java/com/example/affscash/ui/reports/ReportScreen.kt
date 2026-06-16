package com.example.affscash.ui.reports

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.affscash.data.model.ReportRow

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ReportScreen(
    viewModel: ReportViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    var selectedTab by remember { mutableStateOf("day") }
    val tabs = listOf("day" to "Daily", "offer" to "By Offer")

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Analytics & Reports") },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            TabRow(selectedTabIndex = tabs.indexOfFirst { it.first == selectedTab }) {
                tabs.forEachIndexed { index, tabInfo ->
                    Tab(
                        selected = selectedTab == tabInfo.first,
                        onClick = {
                            selectedTab = tabInfo.first
                            viewModel.loadReports(tabInfo.first)
                        },
                        text = { Text(tabInfo.second) }
                    )
                }
            }

            Box(modifier = Modifier.fillMaxSize().weight(1f)) {
                when (uiState) {
                    is ReportState.Loading -> {
                        CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                    }
                    is ReportState.Error -> {
                        val msg = (uiState as ReportState.Error).message
                        Column(
                            modifier = Modifier.align(Alignment.Center),
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            Text(msg, color = MaterialTheme.colorScheme.error)
                            Spacer(modifier = Modifier.height(8.dp))
                            Button(onClick = { viewModel.loadReports(selectedTab) }) {
                                Text("Retry")
                            }
                        }
                    }
                    is ReportState.Success -> {
                        val state = uiState as ReportState.Success
                        if (state.rows.isEmpty()) {
                            Text("No data available for this period.", modifier = Modifier.align(Alignment.Center))
                        } else {
                            LazyColumn(
                                contentPadding = PaddingValues(16.dp),
                                verticalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                items(state.rows) { row ->
                                    ReportRowItem(row)
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
fun ReportRowItem(row: ReportRow) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(row.label, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Spacer(modifier = Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Clicks: ${row.clicks}", style = MaterialTheme.typography.bodyMedium)
                    Text("Conversions: ${row.conv}", style = MaterialTheme.typography.bodyMedium)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("Payout: $${row.payout}", style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.SemiBold)
                    Text("Approved: ${row.approved}", style = MaterialTheme.typography.bodyMedium)
                }
            }
        }
    }
}
