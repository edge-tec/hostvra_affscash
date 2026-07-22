package net.affscash.android.ui.admin.traffic_source_override

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
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
import net.affscash.android.data.model.CountryOptionItem
import net.affscash.android.data.model.SimpleOptionItem
import net.affscash.android.data.model.TrafficSourceOverrideDestination
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

                    // Rules List Header
                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                text = "Configured Override Rules (${data.rules.size})",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onSurface
                            )
                        }
                    }

                    if (data.rules.isEmpty()) {
                        item {
                            Box(
                                modifier = Modifier.fillMaxWidth().padding(32.dp),
                                contentAlignment = Alignment.Center
                            ) {
                                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                    Icon(
                                        Icons.Default.Tune,
                                        contentDescription = null,
                                        modifier = Modifier.size(48.dp),
                                        tint = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.5f)
                                    )
                                    Spacer(modifier = Modifier.height(8.dp))
                                    Text(
                                        text = "No override rules created yet.\nTap + to add a new rule.",
                                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                                        style = MaterialTheme.typography.bodyMedium
                                    )
                                }
                            }
                        }
                    } else {
                        val destinationMap = data.destinations.associate { it.key to it.label }
                        items(data.rules) { rule ->
                            RuleCardItem(
                                rule = rule,
                                destinations = destinationMap,
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
            affiliates = uiState.data?.affiliates ?: emptyList(),
            offers = uiState.data?.offers ?: emptyList(),
            advertisers = uiState.data?.advertisers ?: emptyList(),
            countries = uiState.data?.countries ?: emptyList(),
            deviceTypes = uiState.data?.deviceTypes ?: listOf("Mobile", "Desktop", "Tablet"),
            onDismiss = { showAddDialog = false },
            onSave = { name, targets, overrideSrc, priority, affIds, offerIds, advIds, cCodes, devs ->
                viewModel.saveRule(
                    name = name,
                    targetSources = targets,
                    overrideSource = overrideSrc,
                    priority = priority,
                    affiliateIds = affIds,
                    offerIds = offerIds,
                    advertiserIds = advIds,
                    countries = cCodes,
                    deviceTypes = devs,
                    ruleId = editingRule?.id,
                    isManager = isManager
                )
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
                        checked = rule.enabled,
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
                    if (rule.targetOriginalSources.isEmpty()) {
                        Surface(
                            color = MaterialTheme.colorScheme.secondaryContainer,
                            shape = RoundedCornerShape(4.dp)
                        ) {
                            Text(
                                text = "ALL SOURCES",
                                fontSize = 10.sp,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onSecondaryContainer,
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                            )
                        }
                    } else {
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

@OptIn(ExperimentalLayoutApi::class, ExperimentalMaterial3Api::class)
@Composable
private fun AddEditRuleDialog(
    rule: TrafficSourceOverrideRule?,
    chatSources: List<String>,
    destinations: List<TrafficSourceOverrideDestination>,
    affiliates: List<SimpleOptionItem>,
    offers: List<SimpleOptionItem>,
    advertisers: List<SimpleOptionItem>,
    countries: List<CountryOptionItem>,
    deviceTypes: List<String>,
    onDismiss: () -> Unit,
    onSave: (
        name: String,
        targetSources: List<String>,
        overrideSource: String,
        priority: Int,
        affiliateIds: List<Int>,
        offerIds: List<Int>,
        advertiserIds: List<Int>,
        countryCodes: List<String>,
        devices: List<String>
    ) -> Unit
) {
    var name by remember { mutableStateOf(rule?.name ?: "") }
    var selectedSources by remember { mutableStateOf(rule?.targetOriginalSources?.toSet() ?: setOf("whatsapp")) }
    var selectedOverride by remember { mutableStateOf(rule?.overrideSource ?: (destinations.firstOrNull()?.key ?: "paid_ads")) }
    var priorityText by remember { mutableStateOf((rule?.priority ?: 0).toString()) }

    var selectedAffiliates by remember { mutableStateOf(rule?.conditions?.affiliateIds?.toSet() ?: emptySet()) }
    var selectedOffers by remember { mutableStateOf(rule?.conditions?.offerIds?.toSet() ?: emptySet()) }
    var selectedAdvertisers by remember { mutableStateOf(rule?.conditions?.advertiserIds?.toSet() ?: emptySet()) }
    var selectedCountries by remember { mutableStateOf(rule?.conditions?.countries?.toSet() ?: emptySet()) }
    var selectedDeviceTypes by remember { mutableStateOf(rule?.conditions?.deviceTypes?.toSet() ?: emptySet()) }

    val scrollState = rememberScrollState()

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(if (rule == null) "+ Add Override Rule" else "Edit Override Rule") },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .heightIn(max = 480.dp)
                    .verticalScroll(scrollState),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                // Rule Name
                OutlinedTextField(
                    value = name,
                    onValueChange = { name = it },
                    label = { Text("Rule Name") },
                    placeholder = { Text("e.g. Map WhatsApp to Paid Ads") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                // Priority
                OutlinedTextField(
                    value = priorityText,
                    onValueChange = { priorityText = it },
                    label = { Text("Priority") },
                    placeholder = { Text("0") },
                    supportingText = { Text("Higher number = higher priority. Evaluated first.", fontSize = 10.sp) },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                // Target Original Sources
                Text("Target Original Sources", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                Text("Leave blank to apply to ALL sources.", fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)

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

                // Override As (Destination)
                Text("Override As (Destination)", fontSize = 12.sp, fontWeight = FontWeight.Bold)

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

                HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))

                // OPTIONAL CONDITIONS
                Text(
                    text = "OPTIONAL CONDITIONS",
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary,
                    letterSpacing = 1.sp
                )

                // 1. Affiliates
                if (affiliates.isNotEmpty()) {
                    Text("Affiliates", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    FlowRow(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                        affiliates.forEach { aff ->
                            val isSelected = selectedAffiliates.contains(aff.id)
                            FilterChip(
                                selected = isSelected,
                                onClick = {
                                    selectedAffiliates = if (isSelected) selectedAffiliates - aff.id else selectedAffiliates + aff.id
                                },
                                label = { Text("${aff.name} (${aff.affiliateCode ?: aff.id})", fontSize = 10.sp) }
                            )
                        }
                    }
                }

                // 2. Offers
                if (offers.isNotEmpty()) {
                    Text("Offers", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    FlowRow(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                        offers.take(15).forEach { offer ->
                            val isSelected = selectedOffers.contains(offer.id)
                            FilterChip(
                                selected = isSelected,
                                onClick = {
                                    selectedOffers = if (isSelected) selectedOffers - offer.id else selectedOffers + offer.id
                                },
                                label = { Text("${offer.name} (#${offer.id})", fontSize = 10.sp) }
                            )
                        }
                    }
                }

                // 3. Advertisers
                if (advertisers.isNotEmpty()) {
                    Text("Advertisers", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    FlowRow(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                        advertisers.forEach { adv ->
                            val isSelected = selectedAdvertisers.contains(adv.id)
                            FilterChip(
                                selected = isSelected,
                                onClick = {
                                    selectedAdvertisers = if (isSelected) selectedAdvertisers - adv.id else selectedAdvertisers + adv.id
                                },
                                label = { Text(adv.name, fontSize = 10.sp) }
                            )
                        }
                    }
                }

                // 4. Countries
                if (countries.isNotEmpty()) {
                    Text("Countries (GEO)", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    FlowRow(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                        countries.forEach { country ->
                            val isSelected = selectedCountries.contains(country.code)
                            FilterChip(
                                selected = isSelected,
                                onClick = {
                                    selectedCountries = if (isSelected) selectedCountries - country.code else selectedCountries + country.code
                                },
                                label = { Text("${country.name} (${country.code})", fontSize = 10.sp) }
                            )
                        }
                    }
                }

                // 5. Device Types
                Text("Device Types", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                FlowRow(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    val devicesList = if (deviceTypes.isNotEmpty()) deviceTypes else listOf("Mobile", "Desktop", "Tablet")
                    devicesList.forEach { dev ->
                        val isSelected = selectedDeviceTypes.contains(dev)
                        FilterChip(
                            selected = isSelected,
                            onClick = {
                                selectedDeviceTypes = if (isSelected) selectedDeviceTypes - dev else selectedDeviceTypes + dev
                            },
                            label = { Text(dev, fontSize = 11.sp) }
                        )
                    }
                }
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    if (name.isNotBlank()) {
                        onSave(
                            name,
                            selectedSources.toList(),
                            selectedOverride,
                            priorityText.toIntOrNull() ?: 0,
                            selectedAffiliates.toList(),
                            selectedOffers.toList(),
                            selectedAdvertisers.toList(),
                            selectedCountries.toList(),
                            selectedDeviceTypes.toList()
                        )
                    }
                }
            ) {
                Text("Save Override Rule")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancel")
            }
        }
    )
}
