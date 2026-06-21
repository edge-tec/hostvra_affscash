package net.affscash.android.ui.admin

import android.widget.Toast
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminPrivateOfferDetailScreen(
    offerId: Int,
    onNavigateBack: () -> Unit,
    viewModel: AdminPrivateOfferDetailViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val actionMessage by viewModel.actionMessage.collectAsState()
    val context = LocalContext.current

    LaunchedEffect(offerId) {
        viewModel.loadDetail(offerId)
    }

    LaunchedEffect(actionMessage) {
        actionMessage?.let {
            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
            viewModel.clearActionMessage()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Private Offer Details") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { paddingValues ->
        Box(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            when (val state = uiState) {
                is AdminPrivateOfferDetailUiState.Loading -> {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
                is AdminPrivateOfferDetailUiState.Error -> {
                    Text(state.message, color = MaterialTheme.colorScheme.error, modifier = Modifier.align(Alignment.Center))
                }
                is AdminPrivateOfferDetailUiState.Success -> {
                    val data = state.data
                    LazyColumn(
                        modifier = Modifier.fillMaxSize().padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(16.dp)
                    ) {
                        item {
                            Card(modifier = Modifier.fillMaxWidth()) {
                                Column(modifier = Modifier.padding(12.dp)) {
                                    Text("Offer Details", style = MaterialTheme.typography.titleMedium)
                                    Spacer(modifier = Modifier.height(8.dp))
                                    Text("Name: OFF-${data.offer.id} · ${data.offer.name}")
                                    Text("Payout: ${data.offer.payout_type} ${data.offer.payout ?: "0"}")
                                    Text("Status: ${data.offer.status}")
                                }
                            }
                        }

                        // Grant Access Form
                        item {
                            Card(modifier = Modifier.fillMaxWidth()) {
                                Column(modifier = Modifier.padding(12.dp)) {
                                    Text("Grant Access", style = MaterialTheme.typography.titleMedium)
                                    Spacer(modifier = Modifier.height(8.dp))
                                    
                                    var identifier by remember { mutableStateOf("") }
                                    var notes by remember { mutableStateOf("") }

                                    OutlinedTextField(
                                        value = identifier,
                                        onValueChange = { identifier = it },
                                        label = { Text("Affiliate ID, Code, or Email *") },
                                        modifier = Modifier.fillMaxWidth()
                                    )
                                    Spacer(modifier = Modifier.height(8.dp))
                                    OutlinedTextField(
                                        value = notes,
                                        onValueChange = { notes = it },
                                        label = { Text("Notes (Optional)") },
                                        modifier = Modifier.fillMaxWidth()
                                    )
                                    Spacer(modifier = Modifier.height(8.dp))
                                    Button(
                                        onClick = {
                                            viewModel.grantAccess(identifier, notes)
                                            identifier = ""
                                            notes = ""
                                        },
                                        enabled = identifier.isNotBlank(),
                                        modifier = Modifier.fillMaxWidth()
                                    ) {
                                        Text("Grant Access")
                                    }
                                }
                            }
                        }

                        // Granted Affiliates List
                        item {
                            Card(modifier = Modifier.fillMaxWidth()) {
                                Column(modifier = Modifier.padding(12.dp)) {
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Text("Granted Affiliates", style = MaterialTheme.typography.titleMedium)
                                        Badge { Text("${data.grants.size} TOTAL") }
                                    }
                                    Spacer(modifier = Modifier.height(8.dp))

                                    if (data.grants.isEmpty()) {
                                        Text("No affiliates have access.", modifier = Modifier.padding(8.dp))
                                    } else {
                                        data.grants.forEach { grant ->
                                            Row(
                                                modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp),
                                                horizontalArrangement = Arrangement.SpaceBetween,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Column(modifier = Modifier.weight(1f)) {
                                                    Text("${grant.first_name} ${grant.last_name} (${grant.affiliate_code})", fontWeight = FontWeight.Bold)
                                                    Text(grant.email ?: "", style = MaterialTheme.typography.bodySmall)
                                                    if (!grant.notes.isNullOrBlank()) {
                                                        Text("Notes: ${grant.notes}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                                    }
                                                    Text("Granted: ${grant.granted_at}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                                }
                                                Button(
                                                    onClick = { viewModel.revokeAccess(grant.affiliate_id) },
                                                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                                                ) {
                                                    Text("Revoke")
                                                }
                                            }
                                            Divider()
                                        }
                                    }
                                }
                            }
                        }

                        // Recent Activity (Offer Specific)
                        item {
                            Card(modifier = Modifier.fillMaxWidth()) {
                                Column(modifier = Modifier.padding(12.dp)) {
                                    Text("Recent Activity", style = MaterialTheme.typography.titleMedium)
                                    Spacer(modifier = Modifier.height(8.dp))

                                    if (data.log.isEmpty()) {
                                        Text("No recent activity.", modifier = Modifier.padding(8.dp))
                                    } else {
                                        data.log.forEach { log ->
                                            Column(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
                                                Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                                                    Text(log.created_at, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                                    Badge(
                                                        containerColor = if (log.action == "deny") MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.secondary
                                                    ) {
                                                        Text(log.action.uppercase())
                                                    }
                                                }
                                                if (log.aff_name != null) {
                                                    Text("Affiliate: ${log.aff_name} (${log.affiliate_code})")
                                                }
                                                if (log.details != null) {
                                                    Text("Details: ${log.details}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                                }
                                            }
                                            Divider()
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                else -> {}
            }
        }
    }
}
