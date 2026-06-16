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
import com.example.affscash.data.model.AdminAffiliate
import com.example.affscash.ui.affiliates.AdminAffiliatesUiState
import com.example.affscash.ui.affiliates.AdminAffiliatesViewModel

@Composable
fun AdminUsersScreen(
    viewModel: AdminAffiliatesViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    Box(modifier = Modifier.fillMaxSize()) {
        when (val state = uiState) {
            is AdminAffiliatesUiState.Loading -> {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            }
            is AdminAffiliatesUiState.Error -> {
                Column(
                    modifier = Modifier.align(Alignment.Center),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Text(text = "Error: ${state.message}", color = MaterialTheme.colorScheme.error)
                    Spacer(modifier = Modifier.height(8.dp))
                    Button(onClick = { viewModel.loadAffiliates() }) {
                        Text("Retry")
                    }
                }
            }
            is AdminAffiliatesUiState.Success -> {
                val affiliates = state.data.data
                if (affiliates.isNotEmpty()) {
                    LazyColumn(
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(horizontal = 16.dp),
                        contentPadding = PaddingValues(vertical = 16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        item {
                            Text(
                                text = "Manage Affiliates",
                                style = MaterialTheme.typography.headlineMedium,
                                modifier = Modifier.padding(bottom = 8.dp)
                            )
                        }
                        items(affiliates) { affiliate ->
                            AdminAffiliateItem(affiliate = affiliate)
                        }
                    }
                } else {
                    Text("No affiliates found", modifier = Modifier.align(Alignment.Center))
                }
            }
        }
    }
}

@Composable
fun AdminAffiliateItem(affiliate: AdminAffiliate) {
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
                    text = "${affiliate.firstName} ${affiliate.lastName}",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
                Badge(containerColor = if (affiliate.status == "active") MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.error) {
                    Text(affiliate.status.uppercase(), modifier = Modifier.padding(horizontal = 4.dp))
                }
            }
            Spacer(modifier = Modifier.height(4.dp))
            Text(text = affiliate.email, style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.secondary)
            
            Spacer(modifier = Modifier.height(8.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(text = "Code: ${affiliate.affiliateCode}", style = MaterialTheme.typography.bodySmall)
                Text(text = "Fraud Score: ${affiliate.fraudScore}", style = MaterialTheme.typography.bodySmall, color = if (affiliate.fraudScore > 20) MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.onSurface)
            }
            Spacer(modifier = Modifier.height(4.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(text = "Joined: ${affiliate.createdAt.take(10)}", style = MaterialTheme.typography.bodySmall)
                Text(text = "Balance: $${affiliate.balance}", style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Bold)
            }
        }
    }
}
