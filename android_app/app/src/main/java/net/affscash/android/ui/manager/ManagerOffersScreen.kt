package net.affscash.android.ui.manager

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
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
import net.affscash.android.ui.dashboard.PremiumUI

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
            Surface(
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.background,
                shadowElevation = 2.dp
            ) {
                Column {
                    Box(modifier = Modifier.fillMaxWidth().windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 16.dp, vertical = 12.dp),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text(
                                text = "Offers",
                                style = PremiumUI.HeaderStyle,
                                color = MaterialTheme.colorScheme.onSurface
                            )
                            Row(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                                IconButton(
                                    onClick = { showFilterSheet = true },
                                    modifier = Modifier.size(36.dp)
                                ) {
                                    Icon(Icons.Default.FilterList, contentDescription = "Filter", modifier = Modifier.size(20.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                }
                                IconButton(
                                    onClick = onNavigateToApprovals,
                                    modifier = Modifier.size(36.dp)
                                ) {
                                    Icon(Icons.Default.Approval, contentDescription = "Approvals", modifier = Modifier.size(20.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                }
                            }
                        }
                    }
                    // Chip tabs
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 12.dp, vertical = 8.dp)
                            .horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        listOf("regular" to "Regular", "inhouse" to "In-House").forEach { (key, label) ->
                            val isSelected = uiState.tab == key
                            val icon = if (key == "regular") Icons.Outlined.LocalOffer else Icons.Outlined.Home
                            Surface(
                                shape = RoundedCornerShape(8.dp),
                                color = if (isSelected) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                                modifier = Modifier.clickable { viewModel.setTab(key) }
                            ) {
                                Row(
                                    verticalAlignment = Alignment.CenterVertically,
                                    modifier = Modifier.padding(horizontal = 14.dp, vertical = 8.dp)
                                ) {
                                    Icon(icon, contentDescription = null, modifier = Modifier.size(16.dp), tint = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurfaceVariant)
                                    Spacer(modifier = Modifier.width(6.dp))
                                    Text(
                                        text = label,
                                        fontSize = 13.sp,
                                        color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurfaceVariant,
                                        fontWeight = if (isSelected) FontWeight.SemiBold else FontWeight.Medium
                                    )
                                }
                            }
                        }
                    }
                }
            }
        }
    ) { padding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .background(PremiumUI.PageBackground)
        ) {
            if (uiState.isLoading && uiState.offers.isEmpty()) {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            } else if (uiState.error != null && uiState.offers.isEmpty()) {
                Text(
                    text = uiState.error ?: "Unknown error",
                    color = MaterialTheme.colorScheme.error,
                    modifier = Modifier.align(Alignment.Center).padding(8.dp)
                )
            } else if (uiState.offers.isEmpty()) {
                Column(
                    modifier = Modifier.align(Alignment.Center),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Icon(Icons.Outlined.SearchOff, contentDescription = null, modifier = Modifier.size(48.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.5f))
                    Spacer(modifier = Modifier.height(8.dp))
                    Text(
                        text = "No offers match your filters.",
                        style = PremiumUI.SecondaryText,
                        fontSize = 14.sp
                    )
                }
            } else {
                LazyColumn(
                    contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    item {
                        Text(
                            text = "${uiState.offers.size} offers found",
                            style = PremiumUI.LabelSmall
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
        shape = PremiumUI.CardShape,
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxWidth()) {
            Column(modifier = Modifier.padding(14.dp)) {
                // Header: ID and Status
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "#${offer.id}",
                        style = PremiumUI.LabelSmall,
                        color = MaterialTheme.colorScheme.primary,
                        fontWeight = FontWeight.Bold
                    )
                    Surface(
                        shape = RoundedCornerShape(6.dp),
                        color = if (offer.status == "active") PremiumUI.StatusApprovedBg else Color(0xFFF3F4F6)
                    ) {
                        Text(
                            text = offer.status.uppercase(),
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                            color = if (offer.status == "active") PremiumUI.StatusApproved else Color(0xFF6B7280),
                            fontSize = 10.sp,
                            fontWeight = FontWeight.Bold
                        )
                    }
                }

                Spacer(modifier = Modifier.height(4.dp))

                // Title
                Text(
                    text = offer.name,
                    style = PremiumUI.DataBold,
                    fontSize = 14.sp,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis
                )

                Spacer(modifier = Modifier.height(6.dp))

                // Tags row
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    Surface(color = MaterialTheme.colorScheme.surfaceVariant, shape = RoundedCornerShape(4.dp)) {
                        Text(
                            text = offer.category ?: "Uncategorized",
                            fontSize = 10.sp,
                            modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                    if (offer.offerType != null) {
                        Surface(color = MaterialTheme.colorScheme.surfaceVariant, shape = RoundedCornerShape(4.dp)) {
                            Text(
                                text = offer.offerType,
                                fontSize = 10.sp,
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                    if (offer.isInhouse) {
                        Surface(color = Color(0xFF7C3AED).copy(alpha = 0.1f), shape = RoundedCornerShape(4.dp)) {
                            Text(
                                text = "In-House",
                                fontSize = 10.sp,
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                                color = Color(0xFF7C3AED),
                                fontWeight = FontWeight.Bold
                            )
                        }
                    }
                }

                Spacer(modifier = Modifier.height(12.dp))

                // Stats Row
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    StatItem(Icons.Default.AttachMoney, "Payout", if (offer.payoutType == "RevShare") "${offer.payout}%" else "$${(( offer.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }")
                    StatItem(Icons.Default.Group, "Affiliates", offer.affCount.toString())
                    FraudScoreBadge(offer.fraudScore, offer.fraudLevel)
                }

                Spacer(modifier = Modifier.height(12.dp))

                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))

                // Affiliate Link Generator Toggle
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable { showLinkGenerator = !showLinkGenerator }
                        .padding(vertical = 8.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        "Affiliate Link Generator",
                        style = PremiumUI.TitleMedium,
                        fontSize = 12.sp,
                        color = MaterialTheme.colorScheme.primary
                    )
                    Icon(
                        imageVector = if (showLinkGenerator) Icons.Default.ExpandLess else Icons.Default.ExpandMore,
                        contentDescription = "Expand",
                        tint = MaterialTheme.colorScheme.primary,
                        modifier = Modifier.size(20.dp)
                    )
                }

                // Expanded Link Generator
                AnimatedVisibility(visible = showLinkGenerator) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f), RoundedCornerShape(8.dp))
                            .padding(10.dp)
                    ) {
                        if (managedAffiliates.isEmpty()) {
                            Text(
                                "No managed affiliates available.",
                                style = PremiumUI.SecondaryText
                            )
                        } else {
                            Text(
                                "Select Affiliate",
                                style = PremiumUI.LabelSmall
                            )
                            Spacer(modifier = Modifier.height(4.dp))

                            var expanded by remember { mutableStateOf(false) }

                            Box {
                                OutlinedButton(
                                    onClick = { expanded = true },
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(8.dp),
                                    contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp)
                                ) {
                                    Text(
                                        text = selectedAffiliate?.name ?: "— Select affiliate —",
                                        fontSize = 12.sp,
                                        color = if (selectedAffiliate == null) MaterialTheme.colorScheme.onSurfaceVariant else MaterialTheme.colorScheme.onSurface
                                    )
                                }
                                DropdownMenu(
                                    expanded = expanded,
                                    onDismissRequest = { expanded = false }
                                ) {
                                    managedAffiliates.forEach { affiliate ->
                                        DropdownMenuItem(
                                            text = { Text("${affiliate.name} (${affiliate.affiliateCode})", fontSize = 13.sp) },
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

                            Spacer(modifier = Modifier.height(6.dp))

                            Row(verticalAlignment = Alignment.CenterVertically) {
                                OutlinedTextField(
                                    value = generatedUrl.ifEmpty { "Select affiliate above..." },
                                    onValueChange = {},
                                    readOnly = true,
                                    textStyle = LocalTextStyle.current.copy(fontSize = 11.sp),
                                    modifier = Modifier.weight(1f),
                                    singleLine = true,
                                    shape = RoundedCornerShape(8.dp)
                                )
                                Spacer(modifier = Modifier.width(6.dp))
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
                                    shape = RoundedCornerShape(8.dp),
                                    contentPadding = PaddingValues(horizontal = 14.dp, vertical = 8.dp)
                                ) {
                                    Text("Copy", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                                }
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
        Row(verticalAlignment = Alignment.CenterVertically) {
            Icon(Icons.Outlined.Security, contentDescription = null, modifier = Modifier.size(12.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(modifier = Modifier.width(4.dp))
            Text(
                text = "Fraud Score",
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        Spacer(modifier = Modifier.height(6.dp))
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
fun StatItem(icon: androidx.compose.ui.graphics.vector.ImageVector, label: String, value: String) {
    Column {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Icon(icon, contentDescription = null, modifier = Modifier.size(12.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(modifier = Modifier.width(3.dp))
            Text(
                text = label,
                style = PremiumUI.LabelSmall
            )
        }
        Spacer(modifier = Modifier.height(4.dp))
        Text(
            text = value,
            style = PremiumUI.DataBold
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
        Text("Filter Offers", style = PremiumUI.HeaderStyle, color = MaterialTheme.colorScheme.primary)
        Spacer(modifier = Modifier.height(4.dp))

        val textFieldColors = OutlinedTextFieldDefaults.colors(
            unfocusedBorderColor = Color.Transparent,
            focusedBorderColor = MaterialTheme.colorScheme.primary,
            unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f),
            focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f)
        )
        val textFieldShape = RoundedCornerShape(12.dp)

        OutlinedTextField(
            value = searchQuery,
            onValueChange = { searchQuery = it },
            label = { Text("Search Offers") },
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search", tint = MaterialTheme.colorScheme.primary) },
            modifier = Modifier.fillMaxWidth(),
            singleLine = true,
            shape = textFieldShape,
            colors = textFieldColors
        )
        Spacer(modifier = Modifier.height(4.dp))

        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
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
                    modifier = Modifier.menuAnchor(),
                    shape = textFieldShape,
                    colors = textFieldColors
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
                    modifier = Modifier.menuAnchor(),
                    shape = textFieldShape,
                    colors = textFieldColors
                )
                ExposedDropdownMenu(expanded = ptExpanded, onDismissRequest = { ptExpanded = false }) {
                    DropdownMenuItem(text = { Text("All") }, onClick = { payoutType = null; ptExpanded = false })
                    uiState.filters?.payoutTypes?.forEach {
                        DropdownMenuItem(text = { Text(it) }, onClick = { payoutType = it; ptExpanded = false })
                    }
                }
            }
        }
        
        Spacer(modifier = Modifier.height(4.dp))

        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
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
                    modifier = Modifier.menuAnchor(),
                    shape = textFieldShape,
                    colors = textFieldColors
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
                    modifier = Modifier.menuAnchor(),
                    shape = textFieldShape,
                    colors = textFieldColors
                )
                ExposedDropdownMenu(expanded = otExpanded, onDismissRequest = { otExpanded = false }) {
                    DropdownMenuItem(text = { Text("All") }, onClick = { offerType = null; otExpanded = false })
                    uiState.filters?.offerTypes?.forEach {
                        DropdownMenuItem(text = { Text(it) }, onClick = { offerType = it; otExpanded = false })
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(32.dp))

        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
            OutlinedButton(
                onClick = {
                    viewModel.resetFilters()
                    onDismiss()
                },
                modifier = Modifier.weight(1f).height(56.dp),
                shape = RoundedCornerShape(12.dp)
            ) {
                Text("Reset", fontSize = 16.sp, fontWeight = FontWeight.Bold)
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
                modifier = Modifier.weight(1f).height(56.dp),
                shape = RoundedCornerShape(12.dp)
            ) {
                Text("Apply Filters", fontSize = 16.sp, fontWeight = FontWeight.Bold)
            }
        }
    }
}
