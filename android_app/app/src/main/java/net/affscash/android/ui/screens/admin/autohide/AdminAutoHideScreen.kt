package net.affscash.android.ui.screens.admin.autohide

import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import net.affscash.android.data.model.AdminAutoHideCreateRequest
import net.affscash.android.ui.dashboard.PremiumUI
import net.affscash.android.ui.components.CustomDropdownMenu

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminAutoHideScreen(
    viewModel: AdminAutoHideViewModel = viewModel(),
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(uiState.error) {
        uiState.error?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearError()
        }
    }
    LaunchedEffect(uiState.successMessage) {
        uiState.successMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearSuccessMessage()
        }
    }

    Scaffold(
        topBar = {
            net.affscash.android.ui.components.CompactTopBar(
                title = { Text("Auto Hide Conversions") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) { Icon(Icons.Default.ArrowBack, "Back") }
                }
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { padding ->
        Column(modifier = Modifier.fillMaxSize().padding(padding)) {
            // Stats
            uiState.stats?.let { stats ->
                Row(
                    modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()).padding(8.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    StatCard("Total Hidden", stats.total_hidden.toString())
                    StatCard("Payout Saved", "$${(( stats.payout_saved )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }")
                    StatCard("Active Rules", stats.active_rules.toString())
                    StatCard("Total Rules", stats.total_rules.toString())
                }
            }

            // Tabs
            TabRow(selectedTabIndex = if (uiState.tab == "rules") 0 else if (uiState.tab == "hidden") 1 else 2) {
                Tab(selected = uiState.tab == "rules", onClick = { viewModel.updateTab("rules") }, text = { Text("Rules") })
                Tab(selected = uiState.tab == "hidden", onClick = { viewModel.updateTab("hidden") }, text = { Text("Hidden") })
                Tab(selected = uiState.tab == "new_rule", onClick = { viewModel.updateTab("new_rule") }, text = { Text("+ New Rule") })
            }

            if (uiState.isLoading) {
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            }

            // Content
            when (uiState.tab) {
                "rules" -> {
                    LazyColumn(contentPadding = PaddingValues(8.dp)) {
                        items(uiState.rules) { rule ->
                            Card(modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp)) {
                                Column(modifier = Modifier.padding(8.dp)) {
                                    Text(rule.name, fontWeight = FontWeight.Bold)
                                    Text("Type: ${rule.type.uppercase()}", style = MaterialTheme.typography.bodySmall)
                                    val target = when(rule.type) {
                                        "offer" -> rule.offer_name ?: "Unknown Offer"
                                        "affiliate" -> rule.aff_name ?: "Unknown Affiliate"
                                        else -> "All Conversions"
                                    }
                                    Text("Target: $target", style = MaterialTheme.typography.bodySmall)
                                    Text("Hide %: ${rule.hide_percent}%", style = MaterialTheme.typography.bodySmall, color = Color(0xFFD32F2F))
                                    Text("Reason: ${rule.reason}", style = MaterialTheme.typography.bodySmall)
                                    Text("Status: ${if(rule.is_active == 1) "ACTIVE" else "INACTIVE"}", style = MaterialTheme.typography.bodySmall, color = if(rule.is_active == 1) Color(0xFF388E3C) else Color.Gray)

                                    Row(modifier = Modifier.fillMaxWidth().padding(top = 8.dp), horizontalArrangement = Arrangement.SpaceBetween) {
                                        androidx.compose.material3.Button(
                                            onClick = { viewModel.toggleRule(rule.id) },
                                            colors = ButtonDefaults.buttonColors(containerColor = if (rule.is_active == 1) Color.Gray else Color(0xFF388E3C))
                                        ) {
                                            Text(if (rule.is_active == 1) "Disable" else "Enable")
                                        }
                                        androidx.compose.material3.Button(
                                            onClick = { viewModel.deleteRule(rule.id) },
                                            colors = ButtonDefaults.buttonColors(containerColor = Color.Red)
                                        ) {
                                            Text("Delete")
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                "hidden" -> {
                    LazyColumn(contentPadding = PaddingValues(8.dp)) {
                        items(uiState.hiddenConversions) { conv ->
                            Card(modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp)) {
                                Column(modifier = Modifier.padding(8.dp)) {
                                    Text("Conv ID: ${conv.conversion_id}", fontWeight = FontWeight.Bold)
                                    Text("Offer: ${conv.offer_name}", style = MaterialTheme.typography.bodySmall)
                                    Text("Affiliate: ${conv.aff_name} (${conv.affiliate_code})", style = MaterialTheme.typography.bodySmall)
                                    Text("Payout Saved: $${(( conv.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", style = MaterialTheme.typography.bodySmall, color = Color(0xFF388E3C))
                                    Text("Reason: ${conv.hide_reason}", style = MaterialTheme.typography.bodySmall)
                                    
                                    androidx.compose.material3.Button(
                                        onClick = { viewModel.unhideConversion(conv.conversion_id) },
                                        modifier = Modifier.padding(top = 8.dp)
                                    ) {
                                        Text("Unhide & Restore Balance")
                                    }
                                }
                            }
                        }
                    }
                }
                "new_rule" -> {
                    CreateRuleForm(uiState, viewModel)
                }
            }
        }
    }
}

@Composable
fun CreateRuleForm(uiState: AdminAutoHideState, viewModel: AdminAutoHideViewModel) {
    var name by remember { mutableStateOf("") }
    var type by remember { mutableStateOf("global") }
    var offerId by remember { mutableStateOf<String?>(null) }
    var affiliateId by remember { mutableStateOf<String?>(null) }
    var hidePercent by remember { mutableStateOf(10f) }
    var reason by remember { mutableStateOf("") }
    var applyExisting by remember { mutableStateOf(false) }

    Column(modifier = Modifier.fillMaxSize().padding(8.dp).horizontalScroll(rememberScrollState())) {
        OutlinedTextField(
            value = name,
            onValueChange = { name = it },
            label = { Text("Rule Name *") },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(8.dp))

        CustomDropdownMenu(
            options = listOf("global" to "Global - All conversions", "offer" to "Offer", "affiliate" to "Affiliate"),
            selectedOption = type,
            onOptionSelected = { type = it },
            label = "Rule Type",
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(8.dp))

        if (type == "offer") {
            val offerOptions = uiState.offers.map { it.id.toString() to (it.name ?: "") }
            CustomDropdownMenu(
                options = offerOptions,
                selectedOption = offerId ?: "",
                onOptionSelected = { offerId = it },
                label = "Select Offer *",
                modifier = Modifier.fillMaxWidth()
            )
            Spacer(Modifier.height(8.dp))
        }

        if (type == "affiliate") {
            val affOptions = uiState.affiliates.map { it.id.toString() to (it.name ?: "") }
            CustomDropdownMenu(
                options = affOptions,
                selectedOption = affiliateId ?: "",
                onOptionSelected = { affiliateId = it },
                label = "Select Affiliate *",
                modifier = Modifier.fillMaxWidth()
            )
            Spacer(Modifier.height(8.dp))
        }

        Text("Hide Percentage: ${hidePercent.toInt()}%")
        Slider(
            value = hidePercent,
            onValueChange = { hidePercent = it },
            valueRange = 1f..100f
        )
        Spacer(Modifier.height(8.dp))

        OutlinedTextField(
            value = reason,
            onValueChange = { reason = it },
            label = { Text("Internal Reason (Optional)") },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(16.dp))

        Card(colors = CardDefaults.cardColors(containerColor = Color(0xFFFFF3E0))) {
            Row(modifier = Modifier.padding(8.dp), verticalAlignment = Alignment.CenterVertically) {
                Checkbox(checked = applyExisting, onCheckedChange = { applyExisting = it })
                Column {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Warning, contentDescription = null, tint = Color(0xFFE65100), modifier = Modifier.size(16.dp))
                        Spacer(Modifier.width(4.dp))
                        Text("Apply to existing conversions retroactively", fontWeight = FontWeight.Bold, color = Color(0xFFE65100))
                    }
                    Text(
                        "Will hide past conversions matching this rule at the set percentage. Affiliate balances will be adjusted. This cannot be undone automatically.",
                        style = MaterialTheme.typography.bodySmall,
                        color = Color(0xFFE65100)
                    )
                }
            }
        }
        Spacer(Modifier.height(16.dp))

        androidx.compose.material3.Button(
            onClick = {
                viewModel.createRule(
                    AdminAutoHideCreateRequest(
                        name = name,
                        type = type,
                        offer_id = offerId?.toIntOrNull(),
                        affiliate_id = affiliateId?.toIntOrNull(),
                        hide_percent = hidePercent.toDouble(),
                        reason = reason,
                        apply_existing = applyExisting
                    )
                )
            },
            modifier = Modifier.fillMaxWidth(),
            enabled = name.isNotBlank() && (type == "global" || (type == "offer" && offerId != null) || (type == "affiliate" && affiliateId != null))
        ) {
            Text("Create Auto-Hide Rule")
        }
    }
}

@Composable
fun StatCard(label: String, value: String) {
    Card {
        Column(
            modifier = Modifier.padding(8.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(label, style = MaterialTheme.typography.labelSmall)
            Text(
                value,
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.Bold
            )
        }
    }
}
