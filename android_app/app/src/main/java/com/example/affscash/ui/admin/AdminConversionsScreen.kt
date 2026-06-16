package com.example.affscash.ui.admin

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.affscash.data.model.Conversion
import com.example.affscash.ui.conversions.AdminConversionsUiState
import com.example.affscash.ui.conversions.AdminConversionsViewModel

@Composable
fun AdminConversionsScreen(
    viewModel: AdminConversionsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    Box(modifier = Modifier.fillMaxSize()) {
        when (val state = uiState) {
            is AdminConversionsUiState.Loading -> {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            }
            is AdminConversionsUiState.Error -> {
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
            is AdminConversionsUiState.Success -> {
                val conversions = state.data.data
                if (conversions.isNotEmpty()) {
                    LazyColumn(
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(horizontal = 16.dp),
                        contentPadding = PaddingValues(vertical = 16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        item {
                            Text(
                                text = "All Conversions",
                                style = MaterialTheme.typography.headlineMedium,
                                modifier = Modifier.padding(bottom = 8.dp)
                            )
                        }
                        items(conversions) { conversion ->
                            AdminConversionItem(conversion = conversion)
                        }
                    }
                } else {
                    Text("No conversions found", modifier = Modifier.align(Alignment.Center))
                }
            }
        }
    }
}

@Composable
fun AdminConversionItem(conversion: Conversion) {
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
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = conversion.offerName ?: "Unknown Offer",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
                Badge(containerColor = if (conversion.status == "approved") MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.error) {
                    Text(conversion.status.uppercase(), modifier = Modifier.padding(horizontal = 4.dp))
                }
            }
            Spacer(modifier = Modifier.height(4.dp))
            Text(text = "Affiliate: ${conversion.affName} (${conversion.affiliateCode})", style = MaterialTheme.typography.bodyMedium)
            
            Spacer(modifier = Modifier.height(8.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(text = "Click ID: ${conversion.clickId.take(12)}...", style = MaterialTheme.typography.bodySmall)
                Text(text = "IP: ${conversion.ipAddress}", style = MaterialTheme.typography.bodySmall)
            }
            Spacer(modifier = Modifier.height(4.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(text = "Date: ${conversion.convertedAt.take(10)}", style = MaterialTheme.typography.bodySmall)
                Text(text = "Payout: $${conversion.payout} (Rev: $${conversion.revenue})", style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Bold)
            }
        }
    }
}
