package net.affscash.android.ui.screens.admin.points

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Info
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import net.affscash.android.data.model.AdminPointsBalance
import net.affscash.android.data.model.AdminPointsTransaction

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminPointsScreen(
    viewModel: AdminPointsViewModel,
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    var selectedTab by remember { mutableStateOf(0) }
    val tabs = listOf("Balances", "Recent Activity", "Tools")

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Points Module") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    navigationIconContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
        ) {
            TabRow(selectedTabIndex = selectedTab) {
                tabs.forEachIndexed { index, title ->
                    Tab(
                        selected = selectedTab == index,
                        onClick = { selectedTab = index },
                        text = { Text(title) }
                    )
                }
            }

            if (uiState.isLoading && uiState.balances.isEmpty() && uiState.recent.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            } else if (uiState.error != null) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text(uiState.error!!, color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(16.dp))
                        Button(onClick = { viewModel.loadData() }) {
                            Text("Retry")
                        }
                    }
                }
            } else {
                when (selectedTab) {
                    0 -> BalancesList(uiState.balances)
                    1 -> TransactionsList(uiState.recent)
                    2 -> ToolsSection(uiState, viewModel, context)
                }
            }
        }
        
        // Show Sync Log Dialog
        if (uiState.syncLog.isNotEmpty()) {
            AlertDialog(
                onDismissRequest = { viewModel.clearSyncLog() },
                title = { Text("Sync Log") },
                text = {
                    LazyColumn {
                        items(uiState.syncLog) { log ->
                            Text(log, style = MaterialTheme.typography.bodySmall)
                            Divider(modifier = Modifier.padding(vertical = 4.dp))
                        }
                    }
                },
                confirmButton = {
                    TextButton(onClick = { viewModel.clearSyncLog() }) {
                        Text("Close")
                    }
                }
            )
        }
    }
}

@Composable
fun BalancesList(balances: List<AdminPointsBalance>) {
    if (balances.isEmpty()) {
        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Text("No balances found.", color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
        return
    }
    
    val numberFormat = java.text.NumberFormat.getNumberInstance(java.util.Locale.US)
    
    LazyColumn(
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        items(balances) { balance ->
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Column(modifier = Modifier.weight(1f)) {
                        Text(
                            text = "#${balance.affiliateId} · ${balance.name ?: ""}",
                            fontWeight = FontWeight.Bold,
                            style = MaterialTheme.typography.titleMedium
                        )
                        Text(
                            text = balance.email ?: "N/A",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = "Earned: ${numberFormat.format(balance.lifetimeEarned)} | Spent: ${numberFormat.format(balance.lifetimeSpent)}",
                            style = MaterialTheme.typography.labelSmall
                        )
                        if (!balance.updatedAt.isNullOrEmpty()) {
                            Text(
                                text = "Last Updated: ${balance.updatedAt}",
                                style = MaterialTheme.typography.labelSmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                    Column(horizontalAlignment = Alignment.End) {
                        Text(
                            text = numberFormat.format(balance.balance),
                            fontWeight = FontWeight.Bold,
                            style = MaterialTheme.typography.headlineSmall,
                            color = Color(0xFF388E3C)
                        )
                        Text(
                            text = "Points",
                            style = MaterialTheme.typography.labelSmall
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun TransactionsList(transactions: List<AdminPointsTransaction>) {
    if (transactions.isEmpty()) {
        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Text("No recent transactions.", color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
        return
    }
    LazyColumn(
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        items(transactions) { tx ->
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Column(modifier = Modifier.weight(1f)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            val typeColor = if (tx.amount > 0) Color(0xFF388E3C) else Color(0xFFD32F2F)
                            Box(
                                modifier = Modifier
                                    .background(typeColor.copy(alpha = 0.1f), RoundedCornerShape(4.dp))
                                    .padding(horizontal = 6.dp, vertical = 2.dp)
                            ) {
                                Text(
                                    text = tx.type.uppercase(),
                                    color = typeColor,
                                    fontSize = 10.sp,
                                    fontWeight = FontWeight.Bold
                                )
                            }
                            Spacer(modifier = Modifier.width(8.dp))
                            Text(
                                text = "Affiliate #${tx.affiliateId}",
                                style = MaterialTheme.typography.labelMedium,
                                fontWeight = FontWeight.Bold
                            )
                        }
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = tx.reason ?: "No reason provided",
                            style = MaterialTheme.typography.bodySmall
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = tx.createdAt,
                            style = MaterialTheme.typography.labelSmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                    Text(
                        text = if (tx.amount > 0) "+${tx.amount}" else tx.amount.toString(),
                        fontWeight = FontWeight.Bold,
                        style = MaterialTheme.typography.titleMedium,
                        color = if (tx.amount > 0) Color(0xFF388E3C) else Color(0xFFD32F2F)
                    )
                }
            }
        }
    }
}

@Composable
fun ToolsSection(
    uiState: AdminPointsUiState,
    viewModel: AdminPointsViewModel,
    context: android.content.Context
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp)
            .verticalScroll(rememberScrollState()),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        // Config Card
        var enabled by remember(uiState.config) { mutableStateOf(uiState.config?.enabled ?: false) }
        var usdPerPointStr by remember(uiState.config) { mutableStateOf(uiState.config?.usdPerPoint?.toString() ?: "1") }

        Card(modifier = Modifier.fillMaxWidth(), elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)) {
            Column(modifier = Modifier.padding(16.dp)) {
                Text("Conversion Rule", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                Spacer(modifier = Modifier.height(8.dp))
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Switch(checked = enabled, onCheckedChange = { enabled = it })
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Points module enabled")
                }
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedTextField(
                    value = usdPerPointStr,
                    onValueChange = { usdPerPointStr = it },
                    label = { Text("USD per Point") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth()
                )
                Spacer(modifier = Modifier.height(8.dp))
                Button(
                    onClick = {
                        val usd = usdPerPointStr.toIntOrNull() ?: 1
                        viewModel.saveConfig(
                            enabled = enabled,
                            usdPerPoint = usd,
                            onSuccess = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() },
                            onError = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() }
                        )
                    },
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("Save Rule")
                }
            }
        }

        // Manual Adjustment Card
        var adjustAffId by remember { mutableStateOf("") }
        var adjustDelta by remember { mutableStateOf("") }
        var adjustReason by remember { mutableStateOf("") }

        Card(modifier = Modifier.fillMaxWidth(), elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)) {
            Column(modifier = Modifier.padding(16.dp)) {
                Text("Manual Adjustment", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                Spacer(modifier = Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = adjustAffId,
                        onValueChange = { adjustAffId = it },
                        label = { Text("Affiliate ID") },
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        modifier = Modifier.weight(1f)
                    )
                    OutlinedTextField(
                        value = adjustDelta,
                        onValueChange = { adjustDelta = it },
                        label = { Text("Delta (+ or -)") },
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        modifier = Modifier.weight(1f)
                    )
                }
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedTextField(
                    value = adjustReason,
                    onValueChange = { adjustReason = it },
                    label = { Text("Reason") },
                    modifier = Modifier.fillMaxWidth()
                )
                Spacer(modifier = Modifier.height(8.dp))
                Button(
                    onClick = {
                        val affId = adjustAffId.toIntOrNull() ?: 0
                        val delta = adjustDelta.toIntOrNull() ?: 0
                        if (affId > 0 && delta != 0) {
                            viewModel.adjustPoints(
                                affiliateId = affId,
                                delta = delta,
                                reason = adjustReason,
                                onSuccess = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() },
                                onError = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() }
                            )
                        } else {
                            Toast.makeText(context, "Invalid Affiliate ID or Delta", Toast.LENGTH_SHORT).show()
                        }
                    },
                    modifier = Modifier.fillMaxWidth(),
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.secondary)
                ) {
                    Text("Apply Adjustment")
                }
            }
        }

        // Auto Sync Card
        var syncSince by remember { mutableStateOf("") }
        var dryRun by remember { mutableStateOf(true) }

        Card(modifier = Modifier.fillMaxWidth(), elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)) {
            Column(modifier = Modifier.padding(16.dp)) {
                Text("Auto-Points Sync", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                Spacer(modifier = Modifier.height(8.dp))
                Text("Scans all approved conversions and automatically credits missing points following the active rule.", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedTextField(
                    value = syncSince,
                    onValueChange = { syncSince = it },
                    label = { Text("Only since date (optional, YYYY-MM-DD)") },
                    modifier = Modifier.fillMaxWidth()
                )
                Spacer(modifier = Modifier.height(8.dp))
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Checkbox(checked = dryRun, onCheckedChange = { dryRun = it })
                    Text("Dry Run (preview only)")
                }
                Spacer(modifier = Modifier.height(8.dp))
                Button(
                    onClick = {
                        viewModel.syncPoints(
                            dryRun = dryRun,
                            since = syncSince.takeIf { it.isNotBlank() },
                            onSuccess = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() },
                            onError = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() }
                        )
                    },
                    modifier = Modifier.fillMaxWidth(),
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.tertiary)
                ) {
                    Text("Run Auto-Sync")
                }
            }
        }
        
        Spacer(modifier = Modifier.height(32.dp))
    }
}
