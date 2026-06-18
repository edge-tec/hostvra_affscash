package com.example.affscash.ui.admin

import android.content.Intent
import android.widget.Toast
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.affscash.MainActivity
import com.example.affscash.data.model.AdminAffiliate
import com.example.affscash.ui.affiliates.AdminAffiliatesUiState
import com.example.affscash.ui.affiliates.AdminAffiliatesViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminUsersScreen(
    viewModel: AdminAffiliatesViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val statusTab by viewModel.status.collectAsState()
    val searchQuery by viewModel.searchQuery.collectAsState()
    val context = LocalContext.current

    val tabs = listOf("all", "pending", "active", "rejected", "suspended")

    Column(modifier = Modifier.fillMaxSize()) {
        OutlinedTextField(
            value = searchQuery,
            onValueChange = { viewModel.setSearchQuery(it) },
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            placeholder = { Text("Search by name, email, code") },
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
            singleLine = true
        )

        ScrollableTabRow(
            selectedTabIndex = tabs.indexOf(statusTab),
            edgePadding = 16.dp,
            modifier = Modifier.fillMaxWidth()
        ) {
            tabs.forEachIndexed { index, tab ->
                Tab(
                    selected = index == tabs.indexOf(statusTab),
                    onClick = { viewModel.setStatus(tab) },
                    text = { Text(tab.uppercase()) }
                )
            }
        }

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
                            items(affiliates) { affiliate ->
                                AdminAffiliateItem(
                                    affiliate = affiliate,
                                    onAction = { action ->
                                        viewModel.performAction(action, affiliate.userId, onSuccess = { role ->
                                            if (role != null) {
                                                Toast.makeText(context, "Logged in as ${affiliate.firstName}", Toast.LENGTH_SHORT).show()
                                                val intent = Intent(context, MainActivity::class.java).apply {
                                                    flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                                                }
                                                context.startActivity(intent)
                                            } else {
                                                Toast.makeText(context, "Action successful", Toast.LENGTH_SHORT).show()
                                            }
                                        }, onError = { msg ->
                                            Toast.makeText(context, "Error: $msg", Toast.LENGTH_LONG).show()
                                        })
                                    }
                                )
                            }
                        }
                    } else {
                        Text("No affiliates found", modifier = Modifier.align(Alignment.Center))
                    }
                }
            }
        }
    }
}

@Composable
fun AdminAffiliateItem(affiliate: AdminAffiliate, onAction: (String) -> Unit) {
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
                Badge(containerColor = if (affiliate.status == "active") MaterialTheme.colorScheme.primary else if (affiliate.status == "pending") MaterialTheme.colorScheme.secondary else MaterialTheme.colorScheme.error) {
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
                Text(text = "Balance: $${(( affiliate.balance )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Bold)
            }
            
            Spacer(modifier = Modifier.height(16.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.End
            ) {
                if (affiliate.status == "pending") {
                    Button(onClick = { onAction("approve") }, modifier = Modifier.padding(end = 8.dp)) {
                        Text("Approve")
                    }
                }
                if (affiliate.status == "active") {
                    OutlinedButton(onClick = { onAction("impersonate") }) {
                        Text("Login As")
                    }
                }
            }
        }
    }
}
