package net.affscash.android.ui.screens.admin.reports

import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ArrowDropDown
import androidx.compose.material.icons.filled.FilterList
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import net.affscash.android.ui.components.CustomDropdownMenu
import kotlinx.serialization.json.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminReportsScreen(
    viewModel: AdminReportsViewModel = viewModel(),
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

    Scaffold(
        topBar = {
            net.affscash.android.ui.components.CompactTopBar(
                title = { Text("Reports") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) { Icon(Icons.Default.ArrowBack, "Back") }
                },
                actions = {
                    IconButton(onClick = { viewModel.loadReport() }) { Icon(Icons.Default.Refresh, "Refresh") }
                }
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { padding ->
        Column(modifier = Modifier.fillMaxSize().padding(padding)) {
            // Tabs as Dropdown
            var reportTypeExpanded by remember { mutableStateOf(false) }
            val tabs = listOf(
                "performance" to "Performance",
                "clicks" to "Clicks",
                "conversions" to "Conversions",
                "rejected" to "Rejected",
                "pending" to "Pending",
                "autohide" to "Autohide",
                "offer_report" to "Offer Reports",
                "postback" to "Postback Log",
                "sl_clicks" to "SmartLink Clicks",
                "sl_conversions" to "SmartLink Conv",
                "sl_affiliates" to "SmartLink Affiliates"
            )
            val currentTabName = tabs.find { it.first == uiState.tab }?.second ?: "Report Type"
            Box(modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp, vertical = 4.dp)) {
                OutlinedButton(
                    onClick = { reportTypeExpanded = true },
                    modifier = Modifier.fillMaxWidth(),
                    contentPadding = PaddingValues(horizontal = 16.dp)
                ) {
                    Text(currentTabName, modifier = Modifier.weight(1f))
                    Icon(Icons.Default.ArrowDropDown, contentDescription = null)
                }
                DropdownMenu(expanded = reportTypeExpanded, onDismissRequest = { reportTypeExpanded = false }) {
                    tabs.forEach { tabInfo ->
                        DropdownMenuItem(
                            text = { Text(tabInfo.second) },
                            onClick = { viewModel.updateTab(tabInfo.first); reportTypeExpanded = false }
                        )
                    }
                }
            }

            if (uiState.isLoading) {
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            }

            // Filters Toggle
            var showFilters by remember { mutableStateOf(false) }
            Button(
                onClick = { showFilters = !showFilters },
                modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp, vertical = 4.dp),
                variant = if (showFilters) ButtonDefaults.filledTonalButtonColors() else ButtonDefaults.buttonColors()
            ) {
                Icon(Icons.Default.FilterList, contentDescription = null)
                Spacer(Modifier.width(8.dp))
                Text(if (showFilters) "Hide Filters" else "Show Filters")
            }

            if (showFilters) {
                Card(modifier = Modifier.fillMaxWidth().padding(8.dp)) {
                    Column(modifier = Modifier.padding(8.dp)) {
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            CustomDropdownMenu(
                                options = listOf("date" to "Day", "offer" to "Offer", "affiliate" to "Affiliate", "country" to "Country"),
                                selectedOption = uiState.groupBy,
                                onOptionSelected = { viewModel.updateFilter(groupBy = it) },
                                label = "Group By",
                                modifier = Modifier.weight(1f)
                            )
                            Spacer(Modifier.width(8.dp))
                            Button(onClick = { viewModel.loadReport() }, modifier = Modifier.weight(1f)) {
                                Text("Apply")
                            }
                        }
                        // Other filters can be added here (Offer, Affiliate, Dates)
                    }
                }
            }

            // Stats Cards
            uiState.totals?.let { totals ->
                Row(
                    modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()).padding(8.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    StatCard("Clicks", totals.clicks.toString())
                    StatCard("Unique", totals.uclicks.toString())
                    StatCard("Conversions", totals.conversions.toString(), Color(0xFF388E3C))
                    StatCard("Payout", "$${(( totals.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }")
                    StatCard("Revenue", "$${(( totals.revenue )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }")
                    StatCard("Profit", "$${(( totals.profit )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", if (totals.profit >= 0) Color(0xFF388E3C) else Color.Red)
                }
            }

            // Data List
            LazyColumn(
                modifier = Modifier.fillMaxSize(),
                contentPadding = PaddingValues(8.dp)
            ) {
                items(uiState.rows) { row ->
                    ReportRowCard(tab = uiState.tab, row = row)
                }
            }
        }
    }
}

// Ensure Button component does not error on variant by not using variant but colors if needed
@Composable
fun Button(
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    variant: ButtonColors = ButtonDefaults.buttonColors(),
    content: @Composable RowScope.() -> Unit
) {
    androidx.compose.material3.Button(onClick = onClick, modifier = modifier, colors = variant, content = content)
}

@Composable
fun StatCard(label: String, value: String, valueColor: Color = Color.Unspecified) {
    Card {
        Column(
            modifier = Modifier.padding(8.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(label, style = MaterialTheme.typography.labelSmall)
            Text(
                value,
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.Bold,
                color = valueColor
            )
        }
    }
}

@Composable
fun ReportRowCard(tab: String, row: JsonObject) {
    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp)) {
        Column(modifier = Modifier.padding(8.dp)) {
            when (tab) {
                "performance" -> {
                    Text(row["label"].asString("Unknown"), fontWeight = FontWeight.Bold)
                    Spacer(Modifier.height(4.dp))
                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text("Clicks: ${row["clicks"].asString()}", style = MaterialTheme.typography.bodySmall)
                        Text("Conv: ${row["conversions"].asString()}", style = MaterialTheme.typography.bodySmall)
                        Text("Profit: $${(( row["revenue"].asDouble() - row["payout"].asDouble() )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", style = MaterialTheme.typography.bodySmall)
                    }
                }
                "offer_report" -> {
                    Text(row["offer_name"].asString("Unknown Offer"), fontWeight = FontWeight.Bold)
                    Text("Status: ${row["offer_status"].asString()}", style = MaterialTheme.typography.labelSmall)
                    Spacer(Modifier.height(4.dp))
                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text("Clicks: ${row["clicks"].asString()}", style = MaterialTheme.typography.bodySmall)
                        Text("Conv: ${row["conversions"].asString()}", style = MaterialTheme.typography.bodySmall)
                        Text("Payout: $${(( row["payout"].asString() )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", style = MaterialTheme.typography.bodySmall)
                    }
                }
                "clicks", "sl_clicks" -> {
                    Text("IP: ${row["ip_address"].asString()}", fontWeight = FontWeight.Bold)
                    Text("Affiliate: ID ${row["affiliate_id"].asString()} - ${row["aff_name"].asString()}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.primary)
                    Text("Offer: ${row["offer_name"].asString()}", style = MaterialTheme.typography.bodySmall)
                    Text("Time: ${row["clicked_at"].asString()}", style = MaterialTheme.typography.bodySmall)
                }
                "conversions", "rejected", "pending", "autohide", "sl_conversions" -> {
                    Text("Conv ID: ${row["conversion_id"].asString()}", fontWeight = FontWeight.Bold)
                    Text("Affiliate: ID ${row["affiliate_id"].asString()} - ${row["aff_name"].asString()}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.primary)
                    Text("Offer: ${row["offer_name"].asString()}", style = MaterialTheme.typography.bodySmall)
                    Text("Payout: $${(( row["payout"].asString() )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } } | Status: ${row["status"].asString()}", style = MaterialTheme.typography.bodySmall)
                }
                "postback" -> {
                    Text("URL: ${row["fired_url"].asString().take(50)}...", fontWeight = FontWeight.Bold)
                    Text("Status: ${row["http_status"].asString()} | Success: ${row["is_success"].asString()}", style = MaterialTheme.typography.bodySmall)
                }
                "sl_affiliates" -> {
                    Text("Affiliate: ${row["aff_name"].asString()}", fontWeight = FontWeight.Bold)
                    Text("SmartLink: ${row["smartlink_name"].asString()}", style = MaterialTheme.typography.bodySmall)
                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text("Clicks: ${row["clicks"].asString()}", style = MaterialTheme.typography.bodySmall)
                        Text("Conv: ${row["conversions"].asString()}", style = MaterialTheme.typography.bodySmall)
                        Text("Payout: $${(( row["payout"].asString() )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", style = MaterialTheme.typography.bodySmall)
                    }
                }
                else -> {
                    Text(row.toString(), style = MaterialTheme.typography.bodySmall)
                }
            }
        }
    }
}

private fun JsonElement?.asString(default: String = ""): String {
    return this?.jsonPrimitive?.contentOrNull ?: default
}

private fun JsonElement?.asDouble(): Double {
    return this?.jsonPrimitive?.doubleOrNull ?: 0.0
}

fun getTabIndex(tab: String): Int {
    val tabs = listOf("performance", "clicks", "conversions", "rejected", "pending", "autohide", "offer_report", "postback", "sl_clicks", "sl_conversions", "sl_affiliates")
    return tabs.indexOf(tab).takeIf { it >= 0 } ?: 0
}
