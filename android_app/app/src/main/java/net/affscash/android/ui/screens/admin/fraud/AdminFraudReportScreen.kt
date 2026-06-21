package net.affscash.android.ui.screens.admin.fraud

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.AdminFraudConversion
import net.affscash.android.data.model.AdminFraudFilterItem
import net.affscash.android.ui.components.CustomDropdownMenu
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminFraudReportScreen(
    viewModel: AdminFraudReportViewModel = hiltViewModel(),
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }
    val scope = rememberCoroutineScope()

    LaunchedEffect(uiState.error) {
        uiState.error?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearMessages()
        }
    }
    LaunchedEffect(uiState.successMessage) {
        uiState.successMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearMessages()
        }
    }

    // Optional: Periodic live tick polling
    LaunchedEffect(Unit) {
        // Poll every 10 seconds if there are pending checks
        while (true) {
            kotlinx.coroutines.delay(10000)
            if ((uiState.stats?.pendingCheck ?: 0) > 0) {
                viewModel.triggerLiveCheck()
            }
        }
    }

    Scaffold(
        topBar = {
            net.affscash.android.ui.components.CompactTopBar(
                title = { Text("Fraud Score Report") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { viewModel.loadReport() }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Refresh")
                    }
                }
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
        ) {
            if (uiState.isLoading && uiState.conversions.isEmpty()) {
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            }

            // Stats Cards
            uiState.stats?.let { stats ->
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(8.dp),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    StatCard("Total", stats.totalConversions.toString(), Modifier.weight(1f))
                    Spacer(modifier = Modifier.width(4.dp))
                    StatCard("Pending", stats.pendingCheck.toString(), Modifier.weight(1f), if (stats.pendingCheck > 0) Color(0xFFF57C00) else null)
                    Spacer(modifier = Modifier.width(4.dp))
                    StatCard("Avg Score", "${stats.avgFraudScore}", Modifier.weight(1f))
                    Spacer(modifier = Modifier.width(4.dp))
                    StatCard("High Risk", stats.highRisk.toString(), Modifier.weight(1f), if (stats.highRisk > 0) Color(0xFFD32F2F) else null)
                }
            }

            // Filters
            var showFilters by remember { mutableStateOf(false) }
            Button(
                onClick = { showFilters = !showFilters },
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 8.dp)
            ) {
                Icon(Icons.Default.FilterList, contentDescription = null)
                Spacer(Modifier.width(8.dp))
                Text(if (showFilters) "Hide Filters" else "Show Filters")
            }

            if (showFilters) {
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(8.dp)
                ) {
                    Column(modifier = Modifier.padding(8.dp)) {
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            CustomDropdownMenu(
                                options = listOf("all" to "All Status", "pending" to "Pending", "approved" to "Approved", "rejected" to "Rejected"),
                                selectedOption = uiState.status,
                                onOptionSelected = { viewModel.updateFilter(status = it) },
                                label = "Status",
                                modifier = Modifier.weight(1f)
                            )
                            Spacer(Modifier.width(8.dp))
                            // Simple sort dropdown
                            CustomDropdownMenu(
                                options = listOf("converted_at" to "Date", "fraud_score" to "Fraud Score"),
                                selectedOption = uiState.sort,
                                onOptionSelected = { viewModel.updateFilter(sort = it) },
                                label = "Sort By",
                                modifier = Modifier.weight(1f)
                            )
                        }
                    }
                }
            }

            // List
            LazyColumn(
                modifier = Modifier.fillMaxSize(),
                contentPadding = PaddingValues(8.dp)
            ) {
                items(uiState.conversions) { conv ->
                    ConversionFraudCard(
                        conversion = conv,
                        onApprove = { viewModel.updateConversionStatus(conv.conversionId, "approved") },
                        onReject = { reason -> viewModel.updateConversionStatus(conv.conversionId, "rejected", reason) }
                    )
                }
            }
        }
    }
}

@Composable
fun StatCard(label: String, value: String, modifier: Modifier = Modifier, valueColor: Color? = null) {
    Card(modifier = modifier) {
        Column(
            modifier = Modifier.padding(8.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(label, style = MaterialTheme.typography.labelSmall)
            Text(
                value,
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.Bold,
                color = valueColor ?: Color.Unspecified
            )
        }
    }
}

@Composable
fun ConversionFraudCard(
    conversion: AdminFraudConversion,
    onApprove: () -> Unit,
    onReject: (String) -> Unit
) {
    var showRejectDialog by remember { mutableStateOf(false) }

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 2.dp),
        colors = CardDefaults.cardColors(
            containerColor = if (conversion.fraudScore != null && conversion.fraudScore >= 75) 
                Color(0xFFFFEBEE) else MaterialTheme.colorScheme.surfaceVariant
        )
    ) {
        Column(modifier = Modifier.padding(8.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text("ID: ${conversion.conversionId.take(8)}...", fontWeight = FontWeight.Bold)
                StatusBadge(conversion.status)
            }
            Spacer(Modifier.height(4.dp))
            Text("Affiliate: ${conversion.affName ?: "N/A"} (${conversion.affiliateCode ?: ""})", style = MaterialTheme.typography.bodySmall)
            Text("Offer: ${conversion.offerName ?: "Custom URL"}", style = MaterialTheme.typography.bodySmall)
            Text("IP: ${conversion.ipAddress ?: "N/A"}", style = MaterialTheme.typography.bodySmall)
            
            Spacer(Modifier.height(8.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column {
                    Text("IPQS Score", style = MaterialTheme.typography.labelSmall)
                    val scoreText = conversion.fraudScore?.toString() ?: "Pending"
                    val scoreColor = when {
                        conversion.fraudScore == null -> Color.Gray
                        conversion.fraudScore >= 75 -> Color.Red
                        conversion.fraudScore >= 40 -> Color(0xFFF57C00)
                        else -> Color(0xFF388E3C)
                    }
                    Text(scoreText, color = scoreColor, fontWeight = FontWeight.Bold)
                }
                
                Column {
                    Text("IPQuery", style = MaterialTheme.typography.labelSmall)
                    Text("${conversion.ipqueryRiskScore ?: 0} / ${conversion.ipqueryRiskLevel ?: "N/A"}")
                }
            }
            
            Spacer(Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                if (conversion.status != "approved") {
                    OutlinedButton(onClick = onApprove, modifier = Modifier.padding(end = 8.dp)) {
                        Text("Approve")
                    }
                }
                if (conversion.status != "rejected") {
                    Button(onClick = { showRejectDialog = true }, colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)) {
                        Text("Reject")
                    }
                }
            }
        }
    }

    if (showRejectDialog) {
        var reason by remember { mutableStateOf("") }
        AlertDialog(
            onDismissRequest = { showRejectDialog = false },
            title = { Text("Reject Conversion") },
            text = {
                OutlinedTextField(
                    value = reason,
                    onValueChange = { reason = it },
                    label = { Text("Reason (Optional)") },
                    modifier = Modifier.fillMaxWidth()
                )
            },
            confirmButton = {
                Button(
                    onClick = {
                        onReject(reason)
                        showRejectDialog = false
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    Text("Confirm Reject")
                }
            },
            dismissButton = {
                TextButton(onClick = { showRejectDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }
}

@Composable
fun StatusBadge(status: String) {
    val (color, text) = when (status) {
        "approved" -> Color(0xFF4CAF50) to "Approved"
        "rejected" -> Color(0xFFF44336) to "Rejected"
        else -> Color(0xFFFF9800) to "Pending"
    }
    Surface(
        color = color.copy(alpha = 0.1f),
        contentColor = color,
        shape = MaterialTheme.shapes.small
    ) {
        Text(
            text = text,
            modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
            style = MaterialTheme.typography.labelSmall,
            fontWeight = FontWeight.Bold
        )
    }
}
