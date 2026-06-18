package net.affscash.android.ui.manager

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.ManagedAffiliate
import net.affscash.android.data.model.ManagerOffer

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerOffersScreen(
    onNavigateToApprovals: () -> Unit = {},
    viewModel: ManagerOffersViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    var showFilterSheet by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            Column {
                TopAppBar(
                    title = {
                        Column {
                            Text("Offers", fontWeight = FontWeight.Bold)
                            Text(
                                "Browse all available offers and access tracking links",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    },
                    actions = {
                        IconButton(onClick = onNavigateToApprovals) {
                            Icon(Icons.Default.Approval, contentDescription = "Approvals")
                        }
                        IconButton(onClick = { showFilterSheet = true }) {
                            Icon(Icons.Default.FilterList, contentDescription = "Filter")
                        }
                    }
                )
                TabRow(
                    selectedTabIndex = if (uiState.tab == "regular") 0 else 1,
                    containerColor = MaterialTheme.colorScheme.surface,
                ) {
                    Tab(
                        selected = uiState.tab == "regular",
                        onClick = { viewModel.setTab("regular") },
                        text = { Text("🏷️ Regular Offers") }
                    )
                    Tab(
                        selected = uiState.tab == "inhouse",
                        onClick = { viewModel.setTab("inhouse") },
                        text = { Text("🏠 In-House Offers") }
                    )
                }
            }
        }
    ) { padding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
        ) {
            if (uiState.isLoading && uiState.offers.isEmpty()) {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            } else if (uiState.error != null && uiState.offers.isEmpty()) {
                Text(
                    text = uiState.error ?: "Unknown error",
                    color = MaterialTheme.colorScheme.error,
                    modifier = Modifier.align(Alignment.Center).padding(16.dp)
                )
            } else if (uiState.offers.isEmpty()) {
                Text(
                    text = "No offers match your filters.",
                    style = MaterialTheme.typography.bodyLarge,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.align(Alignment.Center)
                )
            } else {
                LazyColumn(
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    item {
                        Text(
                            text = "${uiState.offers.size} offers found",
                            style = MaterialTheme.typography.labelMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                    items(uiState.offers) { offer ->
                        ManagerOfferCard(
                            offer = offer,
                            trackingUrlBase = uiState.trackingUrlBase,
                            managedAffiliates = uiState.filters?.managedAffiliates ?: emptyList()
                        )
                    }
                }
            }
        }

        if (showFilterSheet) {
            ModalBottomSheet(
                onDismissRequest = { showFilterSheet = false },
                sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
            ) {
                ManagerOfferFilterContent(
                    uiState = uiState,
                    viewModel = viewModel,
                    onDismiss = { showFilterSheet = false }
                )
            }
        }
    }
}

@Composable
fun ManagerOfferCard(
    offer: ManagerOffer,
    trackingUrlBase: String,
    managedAffiliates: List<ManagedAffiliate>
) {
    var showLinkGenerator by remember { mutableStateOf(false) }
    var selectedAffiliate by remember { mutableStateOf<ManagedAffiliate?>(null) }
    val context = LocalContext.current

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            // Header: ID and Status
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "#${offer.id}",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Badge(containerColor = if (offer.status == "active") Color(0xFF4CAF50) else Color(0xFF9E9E9E)) {
                    Text(offer.status.uppercase(), modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp))
                }
            }
            
            Spacer(modifier = Modifier.height(8.dp))

            // Title and Details
            Text(
                text = offer.name,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                maxLines = 2,
                overflow = TextOverflow.Ellipsis
            )

            Spacer(modifier = Modifier.height(4.dp))

            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    text = offer.category ?: "Uncategorized",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                if (offer.offerType != null) {
                    Text(
                        text = " • ${offer.offerType}",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
                if (offer.isInhouse) {
                    Text(
                        text = " • In-House",
                        style = MaterialTheme.typography.bodySmall,
                        color = Color(0xFF7C3AED),
                        fontWeight = FontWeight.Bold
                    )
                }
            }

            Spacer(modifier = Modifier.height(12.dp))

            // Stats Row
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                StatItem("Payout", if (offer.payoutType == "RevShare") "${offer.payout}%" else "$${(( offer.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }")
                StatItem("Affiliates", offer.affCount.toString())
                FraudScoreBadge(offer.fraudScore, offer.fraudLevel)
            }

            Spacer(modifier = Modifier.height(16.dp))

            Divider(color = MaterialTheme.colorScheme.outlineVariant)

            // Affiliate Link Generator Toggle
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .clickable { showLinkGenerator = !showLinkGenerator }
                    .padding(vertical = 12.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    "Affiliate Link Generator",
                    style = MaterialTheme.typography.labelLarge,
                    color = MaterialTheme.colorScheme.primary
                )
                Icon(
                    imageVector = if (showLinkGenerator) Icons.Default.ExpandLess else Icons.Default.ExpandMore,
                    contentDescription = "Expand",
                    tint = MaterialTheme.colorScheme.primary
                )
            }

            // Expanded Link Generator
            AnimatedVisibility(visible = showLinkGenerator) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(MaterialTheme.colorScheme.surfaceVariant, RoundedCornerShape(8.dp))
                        .padding(12.dp)
                ) {
                    if (managedAffiliates.isEmpty()) {
                        Text(
                            "No managed affiliates available.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    } else {
                        Text(
                            "Select Affiliate",
                            style = MaterialTheme.typography.labelSmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        
                        var expanded by remember { mutableStateOf(false) }
                        
                        Box {
                            OutlinedButton(
                                onClick = { expanded = true },
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(8.dp)
                            ) {
                                Text(
                                    text = selectedAffiliate?.name ?: "— Select affiliate —",
                                    color = if (selectedAffiliate == null) MaterialTheme.colorScheme.onSurfaceVariant else MaterialTheme.colorScheme.onSurface
                                )
                            }
                            DropdownMenu(
                                expanded = expanded,
                                onDismissRequest = { expanded = false }
                            ) {
                                managedAffiliates.forEach { affiliate ->
                                    DropdownMenuItem(
                                        text = { Text("${affiliate.name} (${affiliate.affiliateCode})") },
                                        onClick = {
                                            selectedAffiliate = affiliate
                                            expanded = false
                                        }
                                    )
                                }
                            }
                        }

                        val generatedUrl = if (selectedAffiliate != null) {
                            "$trackingUrlBase${offer.id}?aff=${selectedAffiliate!!.affiliateCode}&sub1="
                        } else {
                            ""
                        }

                        Spacer(modifier = Modifier.height(8.dp))
                        
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            OutlinedTextField(
                                value = generatedUrl.ifEmpty { "Select affiliate above..." },
                                onValueChange = {},
                                readOnly = true,
                                textStyle = LocalTextStyle.current.copy(fontSize = 12.sp),
                                modifier = Modifier.weight(1f),
                                singleLine = true
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                            Button(
                                onClick = {
                                    if (generatedUrl.isNotEmpty()) {
                                        val clipboard = context.getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
                                        val clip = ClipData.newPlainText("Tracking Link", generatedUrl)
                                        clipboard.setPrimaryClip(clip)
                                        Toast.makeText(context, "Link Copied!", Toast.LENGTH_SHORT).show()
                                    }
                                },
                                enabled = generatedUrl.isNotEmpty(),
                                shape = RoundedCornerShape(8.dp)
                            ) {
                                Text("Copy")
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun FraudScoreBadge(score: Int, level: String) {
    val (bgColor, textColor) = when (level.lowercase()) {
        "low" -> Pair(Color(0xFFE8F5E9), Color(0xFF2E7D32))
        "medium" -> Pair(Color(0xFFFFF3E0), Color(0xFFEF6C00))
        "high" -> Pair(Color(0xFFFFEBEE), Color(0xFFC62828))
        else -> Pair(Color(0xFFF5F5F5), Color(0xFF616161))
    }
    
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(
            text = "Fraud Score",
            style = MaterialTheme.typography.labelSmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Spacer(modifier = Modifier.height(4.dp))
        Box(
            modifier = Modifier
                .clip(RoundedCornerShape(12.dp))
                .background(bgColor)
                .padding(horizontal = 8.dp, vertical = 2.dp)
        ) {
            Text(
                text = "$score - ${level.replaceFirstChar { it.uppercase() }}",
                style = MaterialTheme.typography.labelSmall,
                color = textColor,
                fontWeight = FontWeight.Bold
            )
        }
    }
}

@Composable
fun StatItem(label: String, value: String) {
    Column {
        Text(
            text = label,
            style = MaterialTheme.typography.labelSmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Text(
            text = value,
            style = MaterialTheme.typography.titleSmall,
            fontWeight = FontWeight.Bold
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerOfferFilterContent(
    uiState: ManagerOffersUiState,
    viewModel: ManagerOffersViewModel,
    onDismiss: () -> Unit
) {
    var searchQuery by remember { mutableStateOf(uiState.searchQuery) }
    var category by remember { mutableStateOf(uiState.category) }
    var payoutType by remember { mutableStateOf(uiState.payoutType) }
    var offerType by remember { mutableStateOf(uiState.offerType) }
    var status by remember { mutableStateOf(uiState.statusFilter) }

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(16.dp)
            .padding(bottom = 32.dp)
    ) {
        Text("Filter Offers", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(16.dp))

        OutlinedTextField(
            value = searchQuery,
            onValueChange = { searchQuery = it },
            label = { Text("Search Offers") },
            modifier = Modifier.fillMaxWidth(),
            singleLine = true
        )
        Spacer(modifier = Modifier.height(8.dp))

        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            // Category Dropdown
            var catExpanded by remember { mutableStateOf(false) }
            ExposedDropdownMenuBox(
                expanded = catExpanded,
                onExpandedChange = { catExpanded = it },
                modifier = Modifier.weight(1f)
            ) {
                OutlinedTextField(
                    value = category ?: "All Categories",
                    onValueChange = {},
                    readOnly = true,
                    label = { Text("Category") },
                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = catExpanded) },
                    modifier = Modifier.menuAnchor()
                )
                ExposedDropdownMenu(expanded = catExpanded, onDismissRequest = { catExpanded = false }) {
                    DropdownMenuItem(text = { Text("All Categories") }, onClick = { category = null; catExpanded = false })
                    uiState.filters?.categories?.forEach {
                        DropdownMenuItem(text = { Text(it) }, onClick = { category = it; catExpanded = false })
                    }
                }
            }

            // Payout Type Dropdown
            var ptExpanded by remember { mutableStateOf(false) }
            ExposedDropdownMenuBox(
                expanded = ptExpanded,
                onExpandedChange = { ptExpanded = it },
                modifier = Modifier.weight(1f)
            ) {
                OutlinedTextField(
                    value = payoutType ?: "All",
                    onValueChange = {},
                    readOnly = true,
                    label = { Text("Payout Type") },
                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = ptExpanded) },
                    modifier = Modifier.menuAnchor()
                )
                ExposedDropdownMenu(expanded = ptExpanded, onDismissRequest = { ptExpanded = false }) {
                    DropdownMenuItem(text = { Text("All") }, onClick = { payoutType = null; ptExpanded = false })
                    uiState.filters?.payoutTypes?.forEach {
                        DropdownMenuItem(text = { Text(it) }, onClick = { payoutType = it; ptExpanded = false })
                    }
                }
            }
        }
        
        Spacer(modifier = Modifier.height(8.dp))

        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            // Status Dropdown
            var statusExpanded by remember { mutableStateOf(false) }
            ExposedDropdownMenuBox(
                expanded = statusExpanded,
                onExpandedChange = { statusExpanded = it },
                modifier = Modifier.weight(1f)
            ) {
                OutlinedTextField(
                    value = status?.replaceFirstChar { it.uppercase() } ?: "All",
                    onValueChange = {},
                    readOnly = true,
                    label = { Text("Status") },
                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = statusExpanded) },
                    modifier = Modifier.menuAnchor()
                )
                ExposedDropdownMenu(expanded = statusExpanded, onDismissRequest = { statusExpanded = false }) {
                    DropdownMenuItem(text = { Text("All") }, onClick = { status = null; statusExpanded = false })
                    uiState.filters?.statuses?.forEach {
                        DropdownMenuItem(text = { Text(it.replaceFirstChar { c -> c.uppercase() }) }, onClick = { status = it; statusExpanded = false })
                    }
                }
            }
            
            // Offer Type Dropdown
            var otExpanded by remember { mutableStateOf(false) }
            ExposedDropdownMenuBox(
                expanded = otExpanded,
                onExpandedChange = { otExpanded = it },
                modifier = Modifier.weight(1f)
            ) {
                OutlinedTextField(
                    value = offerType ?: "All",
                    onValueChange = {},
                    readOnly = true,
                    label = { Text("Offer Type") },
                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = otExpanded) },
                    modifier = Modifier.menuAnchor()
                )
                ExposedDropdownMenu(expanded = otExpanded, onDismissRequest = { otExpanded = false }) {
                    DropdownMenuItem(text = { Text("All") }, onClick = { offerType = null; otExpanded = false })
                    uiState.filters?.offerTypes?.forEach {
                        DropdownMenuItem(text = { Text(it) }, onClick = { offerType = it; otExpanded = false })
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(24.dp))

        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
            OutlinedButton(
                onClick = {
                    viewModel.resetFilters()
                    onDismiss()
                },
                modifier = Modifier.weight(1f)
            ) {
                Text("Reset")
            }
            Button(
                onClick = {
                    viewModel.updateSearchQuery(searchQuery)
                    viewModel.updateFilter(
                        category = category,
                        payoutType = payoutType,
                        offerType = offerType,
                        statusFilter = status
                    )
                    viewModel.applyFilters()
                    onDismiss()
                },
                modifier = Modifier.weight(1f)
            ) {
                Text("Apply Filters")
            }
        }
    }
}
