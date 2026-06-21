package net.affscash.android.ui.screens.admin.smartlinks

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import net.affscash.android.data.model.AdminSmartlinkRequest

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminSmartlinkRequestsScreen(
    viewModel: AdminSmartlinkRequestsViewModel,
    navController: NavController
) {
    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            net.affscash.android.ui.components.CompactTopBar(
                title = { Text("Smartlink Requests") },
                navigationIcon = {
                    IconButton(onClick = { navController.navigateUp() }) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { padding ->
        Box(modifier = Modifier.padding(padding).fillMaxSize()) {
            when (val state = uiState) {
                is AdminSmartlinkRequestsUiState.Loading -> {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
                is AdminSmartlinkRequestsUiState.Error -> {
                    Text(
                        text = state.message,
                        color = MaterialTheme.colorScheme.error,
                        modifier = Modifier.align(Alignment.Center)
                    )
                }
                is AdminSmartlinkRequestsUiState.Success -> {
                    if (state.requests.isEmpty()) {
                        Text("No requests found.", modifier = Modifier.align(Alignment.Center))
                    } else {
                        LazyColumn(
                            modifier = Modifier.fillMaxSize(),
                            contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp),
                            verticalArrangement = Arrangement.spacedBy(16.dp)
                        ) {
                            items(state.requests) { request ->
                                AdminSmartlinkRequestItem(
                                    request = request,
                                    onApprove = { viewModel.reviewRequest(request.id, "approved") },
                                    onReject = { viewModel.reviewRequest(request.id, "rejected") }
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun AdminSmartlinkRequestItem(
    request: AdminSmartlinkRequest,
    onApprove: () -> Unit,
    onReject: () -> Unit
) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(8.dp)) {
            Text(
                text = request.smartlinkName ?: "Unknown Smartlink",
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.Bold
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text(text = "Affiliate: ${request.affName ?: "Unknown"} (#${request.affiliateId})", style = MaterialTheme.typography.bodyMedium)
            Text(text = "Email: ${request.affEmail ?: "N/A"}", style = MaterialTheme.typography.bodySmall)
            Text(text = "Date: ${request.createdAt}", style = MaterialTheme.typography.bodySmall)

            Spacer(modifier = Modifier.height(4.dp))
            val badgeColor = when (request.status) {
                "approved" -> Color(0xFF4CAF50)
                "rejected" -> MaterialTheme.colorScheme.error
                else -> Color.LightGray
            }
            Badge(containerColor = badgeColor, contentColor = Color.White) {
                Text(request.status.uppercase(), modifier = Modifier.padding(horizontal = 4.dp))
            }

            if (request.status == "pending") {
                Spacer(modifier = Modifier.height(4.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End
                ) {
                    TextButton(onClick = onReject) {
                        Icon(Icons.Default.Close, contentDescription = "Reject", tint = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Reject", color = MaterialTheme.colorScheme.error)
                    }
                    Spacer(modifier = Modifier.width(4.dp))
                    Button(onClick = onApprove, colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4CAF50))) {
                        Icon(Icons.Default.Check, contentDescription = "Approve")
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Approve")
                    }
                }
            }
        }
    }
}
