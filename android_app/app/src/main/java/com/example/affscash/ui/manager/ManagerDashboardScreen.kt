package com.example.affscash.ui.manager

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.affscash.ui.dashboard.ManagerDashboardUiState
import com.example.affscash.ui.dashboard.ManagerDashboardViewModel

@Composable
fun ManagerDashboardScreen(
    viewModel: ManagerDashboardViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    Box(modifier = Modifier.fillMaxSize()) {
        when (val state = uiState) {
            is ManagerDashboardUiState.Loading -> {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            }
            is ManagerDashboardUiState.Error -> {
                Column(
                    modifier = Modifier.align(Alignment.Center),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Text(text = "Error: ${state.message}", color = MaterialTheme.colorScheme.error)
                    Spacer(modifier = Modifier.height(8.dp))
                    Button(onClick = { viewModel.loadDashboardData() }) {
                        Text("Retry")
                    }
                }
            }
            is ManagerDashboardUiState.Success -> {
                val data = state.data.data
                if (data != null) {
                    Column(
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(16.dp)
                    ) {
                        Text(
                            text = "Manager Dashboard",
                            style = MaterialTheme.typography.headlineMedium,
                            modifier = Modifier.padding(bottom = 16.dp)
                        )

                        // Overview Cards
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            ManagerStatCard(
                                title = "Managed Affiliates",
                                value = data.totalAffiliates.toString(),
                                modifier = Modifier.weight(1f)
                            )
                            ManagerStatCard(
                                title = "Avg Fraud Score",
                                value = data.fraudScoreAverage.toString(),
                                modifier = Modifier.weight(1f)
                            )
                        }

                        Spacer(modifier = Modifier.height(16.dp))

                        // Fraud Insights
                        Text(text = "Fraud Score Insights (30 Days)", style = MaterialTheme.typography.titleLarge)
                        Spacer(modifier = Modifier.height(8.dp))
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
                        ) {
                            Column(modifier = Modifier.padding(16.dp)) {
                                Text("High Risk Conversions: ${data.fraudScoreCounts.high}", color = MaterialTheme.colorScheme.error)
                                Text("Medium Risk Conversions: ${data.fraudScoreCounts.medium}")
                                Text("Low Risk Conversions: ${data.fraudScoreCounts.low}", color = MaterialTheme.colorScheme.primary)
                            }
                        }
                    }
                } else {
                    Text("No data available", modifier = Modifier.align(Alignment.Center))
                }
            }
        }
    }
}

@Composable
fun ManagerStatCard(title: String, value: String, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.secondaryContainer)
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(text = title, style = MaterialTheme.typography.bodyMedium)
            Spacer(modifier = Modifier.height(4.dp))
            Text(text = value, style = MaterialTheme.typography.titleLarge)
        }
    }
}
