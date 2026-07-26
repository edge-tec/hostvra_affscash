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
import androidx.compose.material.icons.filled.ArrowDropDown
import androidx.compose.material.icons.filled.ArrowDropUp
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

val DEFAULT_TARGET_SOURCES = listOf(
    "Unknown",
    "Direct",
    "WhatsApp",
    "Telegram",
    "Facebook Messenger",
    "Instagram Direct",
    "Threads",
    "Discord",
    "Skype",
    "Signal",
    "WeChat",
    "LINE",
    "Viber",
    "Reddit",
    "TikTok"
)

val DEFAULT_DESTINATIONS = listOf(
    TrafficSourceOverrideDestination("Gmail", "Gmail"),
    TrafficSourceOverrideDestination("Email", "Email"),
    TrafficSourceOverrideDestination("Google Search", "Google Search"),
    TrafficSourceOverrideDestination("Google Ads", "Google Ads"),
    TrafficSourceOverrideDestination("Paid Ads", "Paid Ads"),
    TrafficSourceOverrideDestination("Display Ads", "Display Ads"),
    TrafficSourceOverrideDestination("Organic", "SEO / Organic"),
    TrafficSourceOverrideDestination("Social", "Social"),
    TrafficSourceOverrideDestination("Native Ads", "Native Ads"),
    TrafficSourceOverrideDestination("Push", "Push"),
    TrafficSourceOverrideDestination("Other", "Other")
)

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
                val destinationMap = remember(data.destinations) { (data.destinations.ifEmpty { DEFAULT_DESTINATIONS }).associate { it.key to it.label } }
                val affiliateMap = remember(data.affiliates) { data.affiliates.associateBy { it.id } }
                val offerMap = remember(data.offers) { data.offers.associateBy { it.id } }

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
                        items(data.rules) { rule ->
                            RuleCardItem(
                                rule = rule,
                                destinations = destinationMap,
                                affiliateMap = affiliateMap,
                                offerMap = offerMap,
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
            chatSources = if (!uiState.data?.chatSources.isNullOrEmpty()) uiState.data!!.chatSources else DEFAULT_TARGET_SOURCES,
            destinations = if (!uiState.data?.destinations.isNullOrEmpty()) uiState.data!!.destinations else DEFAULT_DESTINATIONS,
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
    affiliateMap: Map<Int, SimpleOptionItem>,
    offerMap: Map<Int, SimpleOptionItem>,
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

            Spacer(modifier = Modifier.height(6.dp))

            // Affiliate & Offer Target Scope Badges
            val affCond = rule.conditions?.affiliateIds
            val offerCond = rule.conditions?.offerIds

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(6.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Surface(
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.7f),
                    shape = RoundedCornerShape(4.dp)
                ) {
                    val affText = if (affCond.isNullOrEmpty()) {
                        "Affiliate: ALL"
                    } else {
                        "Affiliate: " + affCond.map { affiliateMap[it]?.let { a -> "${a.name} (${a.affiliateCode ?: a.id})" } ?: "#$it" }.joinToString(", ")
                    }
                    Text(
                        text = affText,
                        fontSize = 10.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        fontWeight = FontWeight.SemiBold,
                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 3.dp),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }

                Surface(
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.7f),
                    shape = RoundedCornerShape(4.dp)
                ) {
                    val offerText = if (offerCond.isNullOrEmpty()) {
                        "Offer: ALL"
                    } else {
                        "Offer: " + offerCond.map { offerMap[it]?.name ?: "#$it" }.joinToString(", ")
                    }
                    Text(
                        text = offerText,
                        fontSize = 10.sp,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        fontWeight = FontWeight.SemiBold,
                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 3.dp),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
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
    var selectedSources by remember { mutableStateOf(rule?.targetOriginalSources?.toSet() ?: setOf("WhatsApp")) }
    var customSourceInput by remember { mutableStateOf("") }

    var selectedOverride by remember { mutableStateOf(rule?.overrideSource ?: (destinations.firstOrNull()?.key ?: "Paid Ads")) }
    var customOverrideInput by remember { mutableStateOf("") }
    var priorityText by remember { mutableStateOf((rule?.priority ?: 0).toString()) }

    var selectedAffiliates by remember { mutableStateOf(rule?.conditions?.affiliateIds?.toSet() ?: emptySet()) }
    var selectedOffers by remember { mutableStateOf(rule?.conditions?.offerIds?.toSet() ?: emptySet()) }
    var selectedAdvertisers by remember { mutableStateOf(rule?.conditions?.advertiserIds?.toSet() ?: emptySet()) }
    var selectedCountries by remember { mutableStateOf(rule?.conditions?.countries?.toSet() ?: emptySet()) }
    var selectedDeviceTypes by remember { mutableStateOf(rule?.conditions?.deviceTypes?.toSet() ?: emptySet()) }

    // Affiliate Dropdown Search State
    var affSearchText by remember { mutableStateOf("") }
    var affDropdownExpanded by remember { mutableStateOf(false) }

    val filteredAffiliates = remember(affiliates, affSearchText) {
        if (affSearchText.isBlank()) affiliates
        else affiliates.filter {
            it.name.contains(affSearchText, ignoreCase = true) ||
            (it.affiliateCode ?: "").contains(affSearchText, ignoreCase = true) ||
            it.id.toString() == affSearchText.trim()
        }
    }

    // Offer Dropdown Search State
    var offerSearchText by remember { mutableStateOf("") }
    var offerDropdownExpanded by remember { mutableStateOf(false) }

    val filteredOffers = remember(offers, offerSearchText) {
        if (offerSearchText.isBlank()) offers
        else offers.filter {
            it.name.contains(offerSearchText, ignoreCase = true) ||
            it.id.toString() == offerSearchText.trim()
        }
    }

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

                HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))

                // TARGET AFFILIATE ACCOUNT SELECTION
                Text(
                    text = "TARGET AFFILIATE ACCOUNT",
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary,
                    letterSpacing = 1.sp
                )
                Text(
                    text = "Select specific affiliate(s) or leave empty to apply rule to ALL affiliates.",
                    fontSize = 10.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                // Selected Affiliates Chips
                if (selectedAffiliates.isNotEmpty()) {
                    FlowRow(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                        selectedAffiliates.forEach { affId ->
                            val affObj = affiliates.find { it.id == affId }
                            val labelText = affObj?.let { "${it.name} (${it.affiliateCode ?: it.id})" } ?: "Affiliate #$affId"
                            InputChip(
                                selected = true,
                                onClick = { selectedAffiliates = selectedAffiliates - affId },
                                label = { Text(labelText, fontSize = 11.sp) },
                                trailingIcon = { Text(" ✕", fontSize = 10.sp, fontWeight = FontWeight.Bold) }
                            )
                        }
                    }
                } else {
                    Surface(
                        color = MaterialTheme.colorScheme.secondaryContainer.copy(alpha = 0.5f),
                        shape = RoundedCornerShape(6.dp)
                    ) {
                        Text(
                            text = "✓ Applies to ALL Affiliates",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSecondaryContainer,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                }

                // Affiliate Search & Select Input Box (Interactive Dropdown)
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    OutlinedTextField(
                        value = affSearchText,
                        onValueChange = {
                            affSearchText = it
                            affDropdownExpanded = true
                        },
                        label = { Text("Search / Select Affiliate") },
                        placeholder = { Text("Type name, code, or ID...") },
                        trailingIcon = {
                            IconButton(onClick = { affDropdownExpanded = !affDropdownExpanded }) {
                                Icon(
                                    imageVector = if (affDropdownExpanded) Icons.Default.ArrowDropUp else Icons.Default.ArrowDropDown,
                                    contentDescription = "Toggle Affiliate List"
                                )
                            }
                        },
                        modifier = Modifier
                            .weight(1f)
                            .clickable { affDropdownExpanded = !affDropdownExpanded },
                        singleLine = true
                    )
                    Spacer(modifier = Modifier.width(6.dp))
                    Button(
                        onClick = {
                            val affIdInt = affSearchText.trim().toIntOrNull()
                            if (affIdInt != null) {
                                selectedAffiliates = selectedAffiliates + affIdInt
                                affSearchText = ""
                                affDropdownExpanded = false
                            } else if (filteredAffiliates.isNotEmpty()) {
                                selectedAffiliates = selectedAffiliates + filteredAffiliates.first().id
                                affSearchText = ""
                                affDropdownExpanded = false
                            }
                        },
                        contentPadding = PaddingValues(horizontal = 12.dp)
                    ) {
                        Text("Add", fontSize = 11.sp)
                    }
                }

                // Inline Expandable Dropdown List for Affiliates
                if (affDropdownExpanded) {
                    Surface(
                        modifier = Modifier
                            .fillMaxWidth()
                            .heightIn(max = 220.dp),
                        shape = RoundedCornerShape(14.dp),
                        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.95f),
                        border = BorderStroke(1.2.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.6f)),
                        shadowElevation = 6.dp
                    ) {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(6.dp)
                        ) {
                            if (filteredAffiliates.isEmpty()) {
                                item {
                                    Text(
                                        text = "No affiliates found matching search.",
                                        fontSize = 12.sp,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                                        modifier = Modifier.padding(12.dp)
                                    )
                                }
                            } else {
                                items(filteredAffiliates) { aff ->
                                    val isSelected = selectedAffiliates.contains(aff.id)
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .clip(RoundedCornerShape(8.dp))
                                            .background(
                                                if (isSelected) MaterialTheme.colorScheme.primaryContainer
                                                else Color.Transparent
                                            )
                                            .clickable {
                                                selectedAffiliates = if (isSelected) selectedAffiliates - aff.id else selectedAffiliates + aff.id
                                                affSearchText = ""
                                                affDropdownExpanded = false
                                            }
                                            .padding(horizontal = 12.dp, vertical = 10.dp),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Text(
                                            text = "${aff.name} (${aff.affiliateCode ?: "ID: " + aff.id})",
                                            fontSize = 12.sp,
                                            fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
                                            color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurface
                                        )
                                        if (isSelected) {
                                            Text(
                                                text = "✓ Selected",
                                                fontSize = 11.sp,
                                                color = MaterialTheme.colorScheme.primary,
                                                fontWeight = FontWeight.Bold
                                            )
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))

                // TARGET OFFER SELECTION
                Text(
                    text = "TARGET OFFER",
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary,
                    letterSpacing = 1.sp
                )
                Text(
                    text = "Select specific offer(s) or leave empty to apply rule to ALL offers.",
                    fontSize = 10.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                // Selected Offers Chips
                if (selectedOffers.isNotEmpty()) {
                    FlowRow(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                        selectedOffers.forEach { offerId ->
                            val offerObj = offers.find { it.id == offerId }
                            val labelText = offerObj?.let { "${it.name} (#${it.id})" } ?: "Offer #$offerId"
                            InputChip(
                                selected = true,
                                onClick = { selectedOffers = selectedOffers - offerId },
                                label = { Text(labelText, fontSize = 11.sp) },
                                trailingIcon = { Text(" ✕", fontSize = 10.sp, fontWeight = FontWeight.Bold) }
                            )
                        }
                    }
                } else {
                    Surface(
                        color = MaterialTheme.colorScheme.secondaryContainer.copy(alpha = 0.5f),
                        shape = RoundedCornerShape(6.dp)
                    ) {
                        Text(
                            text = "✓ Applies to ALL Offers",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSecondaryContainer,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                }

                // Offer Search & Select Input Box (Interactive Dropdown)
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    OutlinedTextField(
                        value = offerSearchText,
                        onValueChange = {
                            offerSearchText = it
                            offerDropdownExpanded = true
                        },
                        label = { Text("Search / Select Offer") },
                        placeholder = { Text("Type offer name or ID...") },
                        trailingIcon = {
                            IconButton(onClick = { offerDropdownExpanded = !offerDropdownExpanded }) {
                                Icon(
                                    imageVector = if (offerDropdownExpanded) Icons.Default.ArrowDropUp else Icons.Default.ArrowDropDown,
                                    contentDescription = "Toggle Offer List"
                                )
                            }
                        },
                        modifier = Modifier
                            .weight(1f)
                            .clickable { offerDropdownExpanded = !offerDropdownExpanded },
                        singleLine = true
                    )
                    Spacer(modifier = Modifier.width(6.dp))
                    Button(
                        onClick = {
                            val offerIdInt = offerSearchText.trim().toIntOrNull()
                            if (offerIdInt != null) {
                                selectedOffers = selectedOffers + offerIdInt
                                offerSearchText = ""
                                offerDropdownExpanded = false
                            } else if (filteredOffers.isNotEmpty()) {
                                selectedOffers = selectedOffers + filteredOffers.first().id
                                offerSearchText = ""
                                offerDropdownExpanded = false
                            }
                        },
                        contentPadding = PaddingValues(horizontal = 12.dp)
                    ) {
                        Text("Add", fontSize = 11.sp)
                    }
                }

                // Inline Expandable Dropdown List for Offers
                if (offerDropdownExpanded) {
                    Surface(
                        modifier = Modifier
                            .fillMaxWidth()
                            .heightIn(max = 220.dp),
                        shape = RoundedCornerShape(14.dp),
                        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.95f),
                        border = BorderStroke(1.2.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.6f)),
                        shadowElevation = 6.dp
                    ) {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(6.dp)
                        ) {
                            if (filteredOffers.isEmpty()) {
                                item {
                                    Text(
                                        text = "No offers found matching search.",
                                        fontSize = 12.sp,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                                        modifier = Modifier.padding(12.dp)
                                    )
                                }
                            } else {
                                items(filteredOffers) { offer ->
                                    val isSelected = selectedOffers.contains(offer.id)
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .clip(RoundedCornerShape(8.dp))
                                            .background(
                                                if (isSelected) MaterialTheme.colorScheme.primaryContainer
                                                else Color.Transparent
                                            )
                                            .clickable {
                                                selectedOffers = if (isSelected) selectedOffers - offer.id else selectedOffers + offer.id
                                                offerSearchText = ""
                                                offerDropdownExpanded = false
                                            }
                                            .padding(horizontal = 12.dp, vertical = 10.dp),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Text(
                                            text = "${offer.name} (#${offer.id})",
                                            fontSize = 12.sp,
                                            fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
                                            color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurface
                                        )
                                        if (isSelected) {
                                            Text(
                                                text = "✓ Selected",
                                                fontSize = 11.sp,
                                                color = MaterialTheme.colorScheme.primary,
                                                fontWeight = FontWeight.Bold
                                            )
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))

                // Target Original Sources
                Text("Target Original Sources", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                Text("Select options or type custom ones... (Leave blank to apply to ALL sources)", fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)

                FlowRow(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    val allSourcesList = (DEFAULT_TARGET_SOURCES + chatSources + selectedSources).distinct()
                    allSourcesList.forEach { src ->
                        val isSelected = selectedSources.contains(src)
                        FilterChip(
                            selected = isSelected,
                            onClick = {
                                selectedSources = if (isSelected) selectedSources - src else selectedSources + src
                            },
                            label = { Text(src, fontSize = 11.sp) }
                        )
                    }
                }

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    OutlinedTextField(
                        value = customSourceInput,
                        onValueChange = { customSourceInput = it },
                        placeholder = { Text("Or type custom source...", fontSize = 11.sp) },
                        singleLine = true,
                        modifier = Modifier.weight(1f)
                    )
                    Spacer(modifier = Modifier.width(6.dp))
                    Button(
                        onClick = {
                            if (customSourceInput.isNotBlank()) {
                                selectedSources = selectedSources + customSourceInput.trim()
                                customSourceInput = ""
                            }
                        },
                        contentPadding = PaddingValues(horizontal = 12.dp)
                    ) {
                        Text("Add", fontSize = 11.sp)
                    }
                }

                // Override As (Destination)
                Text("Override As (Destination)", fontSize = 12.sp, fontWeight = FontWeight.Bold)

                FlowRow(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    val allDestinationsList = (DEFAULT_DESTINATIONS + destinations).distinctBy { it.key }
                    allDestinationsList.forEach { dest ->
                        val isSelected = selectedOverride == dest.key
                        FilterChip(
                            selected = isSelected,
                            onClick = { selectedOverride = dest.key },
                            label = { Text(dest.label, fontSize = 11.sp) }
                        )
                    }
                }

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    OutlinedTextField(
                        value = customOverrideInput,
                        onValueChange = { customOverrideInput = it },
                        placeholder = { Text("Or type custom destination...", fontSize = 11.sp) },
                        singleLine = true,
                        modifier = Modifier.weight(1f)
                    )
                    Spacer(modifier = Modifier.width(6.dp))
                    Button(
                        onClick = {
                            if (customOverrideInput.isNotBlank()) {
                                selectedOverride = customOverrideInput.trim()
                                customOverrideInput = ""
                            }
                        },
                        contentPadding = PaddingValues(horizontal = 12.dp)
                    ) {
                        Text("Set", fontSize = 11.sp)
                    }
                }

                HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp))

                // ADDITIONAL CONDITIONS
                Text(
                    text = "ADDITIONAL CONDITIONS",
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary,
                    letterSpacing = 1.sp
                )

                // Advertisers
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

                // Countries
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

                // Device Types
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
