package net.affscash.android.ui.admin.traffic_source_override

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.AltRoute
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.SwapHoriz
import androidx.compose.material.icons.filled.Tune
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.TrafficSourceOverrideRule
import net.affscash.android.ui.components.CompactTopBar
import net.affscash.android.ui.dashboard.PremiumUI

@OptIn(ExperimentalLayoutApi::class)
@Composable
fun TrafficSourceOverrideScreen(
    isManager: Boolean = false,
    onNavigateBack: () -> Unit = {},
    onNavigateToLogs: () -> Unit = {},
    viewModel: TrafficSourceOverrideViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    var showAddDialog by remember { mutableStateOf(false) }
    var editingRule by remember { mutableStateOf<TrafficSourceOverrideRule?>(null) }

    LaunchedEffect(isManager) {
        viewModel.loadData(isManager)
    }

    Scaffold(
        topBar = {
            CompactTopBar(
                title = { Text("Traffic Source Override") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = onNavigateToLogs) {
                        Icon(Icons.Default.History, contentDescription = "Override Logs")
                    }
                    IconButton(onClick = { viewModel.loadData(isManager) }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Refresh")
                    }
                }
            )
        },
        floatingActionButton = {
            FloatingActionButton(
                onClick = {
                    editingRule = null
                    showAddDialog = true
                },
                containerColor = MaterialTheme.colorScheme.primary,
                contentColor = MaterialTheme.colorScheme.onPrimary
            ) {
                Icon(Icons.Default.Add, contentDescription = "Add Rule")
            }
        }
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .padding(paddingValues)
                .fillMaxSize()
                .background(PremiumUI.PageBackground)
        ) {
            if (uiState.isLoading && uiState.data == null) {
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth().align(Alignment.TopCenter))
            }

            uiState.data?.let { data ->
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(start = 12.dp, end = 12.dp, top = 12.dp, bottom = 80.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    // Global Toggle Status Card
                    item {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            shape = PremiumUI.CardShape,
                            colors = CardDefaults.cardColors(
                                containerColor = if (data.globalEnabled) 
                                    MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.4f) 
                                else 
                                    MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                            )
                        ) {
                            Row(
                                modifier = Modifier.padding(16.dp),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Row(
                                    modifier = Modifier.weight(1f),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Surface(
                                        shape = CircleShape,
                                        color = if (data.globalEnabled) Color(0xFF10B981) else Color.Gray,
                                        modifier = Modifier.size(40.dp)
                                    ) {
                                        Icon(
                                            Icons.Default.AltRoute,
                                            contentDescription = null,
                                            tint = Color.White,
                                            modifier = Modifier.padding(8.dp)
                                        )
                                    }
                                    Spacer(modifier = Modifier.width(12.dp))
                                    Column {
                                        Text(
                                            text = if (data.globalEnabled) "Override Status: ACTIVE" else "Override Status: INACTIVE",
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 15.sp,
                                            color = MaterialTheme.colorScheme.onSurface
                                        )
                                        Text(
                                            text = if (data.globalEnabled) "Incoming chat traffic will be reclassified" else "Global override module is disabled",
                                            fontSize = 12.sp,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }

                                Switch(
                                    checked = data.globalEnabled,
                                    onCheckedChange = { viewModel.toggleGlobal(isManager) }
                                )
                            }
                        }
                    }

                    // Section Title
                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                text = "ACTIVE OVERRIDE RULES (${data.rules.size})",
                                fontSize = 13.sp,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )

                            Button(
                                onClick = onNavigateToLogs,
                                colors = ButtonDefaults.outlinedButtonColors()
                            ) {
                                Icon(Icons.Default.History, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(4.dp))
                                Text("View Logs", fontSize = 12.sp)
                            }
                        }
                    }

                    if (data.rules.isEmpty()) {
                        item {
                            Card(
                                modifier = Modifier.fillMaxWidth().padding(vertical = 16.dp),
                                shape = PremiumUI.CardShape,
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                            ) {
                                Column(
                                    modifier = Modifier.padding(24.dp).fillMaxWidth(),
                                    horizontalAlignment = Alignment.CenterHorizontally
                                ) {
                                    Icon(
                                        Icons.Default.Tune,
                                        contentDescription = null,
                                        modifier = Modifier.size(40.dp),
                                        tint = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.5f)
                                    )
                                    Spacer(modifier = Modifier.height(8.dp))
                                    Text(
                                        "No traffic override rules found.",
                                        fontWeight = FontWeight.Medium,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                    Text(
                                        "Tap + button to create a new override rule.",
                                        fontSize = 12.sp,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f)
                                    )
                                }
                            }
                        }
                    } else {
                        items(data.rules) { rule ->
                            RuleCardItem(
                                rule = rule,
                                destinations = data.destinations.associate { it.key to it.label },
                                onToggle = { viewModel.toggleRule(rule.id, isManager) },
                                onDelete = { viewModel.deleteRule(rule.id, isManager) },
                                onEdit = {
                                    editingRule = rule
                                    showAddDialog = true
                                }
                            )
                        }
                    }
                }
            }
        }
    }

    if (showAddDialog) {
        AddEditRuleDialog(
            rule = editingRule,
            chatSources = uiState.data?.chatSources ?: listOf("telegram", "whatsapp", "messenger", "discord", "signal", "viber"),
            destinations = uiState.data?.destinations ?: emptyList(),
            onDismiss = { showAddDialog = false },
            onSave = { name, targets, overrideSrc, priority ->
                viewModel.saveRule(name, targets, overrideSrc, priority, editingRule?.id, isManager)
                showAddDialog = false
            }
        )
    }
}

@OptIn(ExperimentalLayoutApi::class)
@Composable
private fun RuleCardItem(
    rule: TrafficSourceOverrideRule,
    destinations: Map<String, String>,
    onToggle: () -> Unit,
    onDelete: () -> Unit,
    onEdit: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = PremiumUI.CardShape,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(14.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = rule.name,
                        fontWeight = FontWeight.Bold,
                        fontSize = 15.sp,
                        color = MaterialTheme.colorScheme.onSurface,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    Text(
                        text = "Priority: ${rule.priority}",
                        fontSize = 11.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }

                Row(verticalAlignment = Alignment.CenterVertically) {
                    Switch(
                        checked = rule.enabled == 1,
                        onCheckedChange = { onToggle() },
                        modifier = Modifier.height(24.dp)
                    )
                    IconButton(onClick = onEdit) {
                        Icon(Icons.Default.Edit, contentDescription = "Edit", tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(20.dp))
                    }
                    IconButton(onClick = onDelete) {
                        Icon(Icons.Default.Delete, contentDescription = "Delete", tint = MaterialTheme.colorScheme.error, modifier = Modifier.size(20.dp))
                    }
                }
            }

            Spacer(modifier = Modifier.height(8.dp))

            // Reclassification Badges
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(6.dp)
            ) {
                // Target Sources Badges
                FlowRow(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    rule.targetOriginalSources.forEach { src ->
                        Surface(
                            color = MaterialTheme.colorScheme.primaryContainer,
                            shape = RoundedCornerShape(4.dp)
                        ) {
                            Text(
                                text = src.uppercase(),
                                fontSize = 10.sp,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onPrimaryContainer,
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                            )
                        }
                    }
                }

                Icon(
                    Icons.Default.SwapHoriz,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(18.dp)
                )

                // Override Destination Badge
                Surface(
                    color = Color(0xFF10B981).copy(alpha = 0.15f),
                    shape = RoundedCornerShape(4.dp)
                ) {
                    Text(
                        text = (destinations[rule.overrideSource] ?: rule.overrideSource).uppercase(),
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF10B981),
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                    )
                }
            }
        }
    }
}

@OptIn(ExperimentalLayoutApi::class)
@Composable
private fun AddEditRuleDialog(
    rule: TrafficSourceOverrideRule?,
    chatSources: List<String>,
    destinations: List<net.affscash.android.data.model.TrafficSourceOverrideDestination>,
    onDismiss: () -> Unit,
    onSave: (name: String, targetSources: List<String>, overrideSource: String, priority: Int) -> Unit
) {
    var name by remember { mutableStateOf(rule?.name ?: "") }
    var selectedSources by remember { mutableStateOf(rule?.targetOriginalSources?.toSet() ?: setOf("telegram")) }
    var selectedOverride by remember { mutableStateOf(rule?.overrideSource ?: (destinations.firstOrNull()?.key ?: "organic")) }
    var priorityText by remember { mutableStateOf((rule?.priority ?: 0).toString()) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(if (rule == null) "Add Override Rule" else "Edit Override Rule") },
        text = {
            Column(
                modifier = Modifier.fillMaxWidth(),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                OutlinedTextField(
                    value = name,
                    onValueChange = { name = it },
                    label = { Text("Rule Name") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                Text("Target Original Chat Sources:", fontSize = 12.sp, fontWeight = FontWeight.Bold)

                FlowRow(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    chatSources.forEach { src ->
                        val isSelected = selectedSources.contains(src)
                        FilterChip(
                            selected = isSelected,
                            onClick = {
                                selectedSources = if (isSelected) selectedSources - src else selectedSources + src
                            },
                            label = { Text(src.uppercase(), fontSize = 11.sp) }
                        )
                    }
                }

                Text("Override Destination Source:", fontSize = 12.sp, fontWeight = FontWeight.Bold)

                FlowRow(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    destinations.forEach { dest ->
                        val isSelected = selectedOverride == dest.key
                        FilterChip(
                            selected = isSelected,
                            onClick = { selectedOverride = dest.key },
                            label = { Text(dest.label, fontSize = 11.sp) }
                        )
                    }
                }

                OutlinedTextField(
                    value = priorityText,
                    onValueChange = { priorityText = it },
                    label = { Text("Priority (Higher runs first)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    if (name.isNotBlank() && selectedSources.isNotEmpty()) {
                        onSave(name, selectedSources.toList(), selectedOverride, priorityText.toIntOrNull() ?: 0)
                    }
                }
            ) {
                Text("Save")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancel")
            }
        }
    )
}
