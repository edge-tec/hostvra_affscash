package com.example.affscash.ui.offers

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.affscash.data.model.Offer
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.jsonArray
import kotlinx.serialization.json.jsonPrimitive

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun OfferScreen(
    viewModel: OfferViewModel = hiltViewModel(),
    onOfferClick: (Int) -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val searchQuery by viewModel.searchQuery.collectAsState()
    val category by viewModel.category.collectAsState()
    val payoutType by viewModel.payoutType.collectAsState()
    val offerType by viewModel.offerType.collectAsState()
    val accessFilter by viewModel.accessFilter.collectAsState()

    var showApplyDialog by remember { mutableStateOf<Offer?>(null) }
    var promoDesc by remember { mutableStateOf("") }
    var applyLoading by remember { mutableStateOf(false) }
    
    var showTrackingLinkDialog by remember { mutableStateOf<String?>(null) }
    var trackingLinkLoading by remember { mutableStateOf<Int?>(null) }
    
    val context = LocalContext.current

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Available Offers") },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            
            // Filters Section
            LazyRow(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                item {
                    OutlinedTextField(
                        value = searchQuery,
                        onValueChange = { viewModel.searchQuery.value = it; viewModel.loadOffers() },
                        placeholder = { Text("Search...") },
                        modifier = Modifier.width(150.dp),
                        singleLine = true,
                        colors = OutlinedTextFieldDefaults.colors(
                            unfocusedContainerColor = MaterialTheme.colorScheme.surface,
                            focusedContainerColor = MaterialTheme.colorScheme.surface
                        )
                    )
                }
                item {
                    FilterDropdown(
                        label = "Category",
                        options = listOf("All Categories", "Dating", "Sweepstakes", "Nutra", "Gaming", "Finance"),
                        selected = category,
                        onSelected = { viewModel.category.value = it; viewModel.loadOffers() }
                    )
                }
                item {
                    FilterDropdown(
                        label = "Type",
                        options = listOf("All Types", "CPA", "CPL", "CPS", "RevShare"),
                        selected = payoutType,
                        onSelected = { viewModel.payoutType.value = it; viewModel.loadOffers() }
                    )
                }
                item {
                    FilterDropdown(
                        label = "Access",
                        options = listOf("All Offers", "Request Approval", "Instantly Approved"),
                        selected = accessFilter,
                        onSelected = { viewModel.accessFilter.value = it; viewModel.loadOffers() }
                    )
                }
            }

            Divider()

            Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
                when (uiState) {
                    is OfferState.Loading -> {
                        CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                    }
                    is OfferState.Error -> {
                        val msg = (uiState as OfferState.Error).message
                        Column(
                            modifier = Modifier.align(Alignment.Center),
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            Text(msg, color = MaterialTheme.colorScheme.error)
                            Spacer(modifier = Modifier.height(8.dp))
                            Button(onClick = { viewModel.loadOffers() }) {
                                Text("Retry")
                            }
                        }
                    }
                    is OfferState.Success -> {
                        val offers = (uiState as OfferState.Success).offers
                        if (offers.isEmpty()) {
                            Text("No offers available.", modifier = Modifier.align(Alignment.Center))
                        } else {
                            LazyColumn(
                                contentPadding = PaddingValues(16.dp),
                                verticalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                items(offers) { offer ->
                                    OfferListItem(
                                        offer = offer,
                                        isLoadingLink = trackingLinkLoading == offer.id,
                                        onClick = { 
                                            trackingLinkLoading = offer.id
                                            viewModel.getOfferDetails(offer.id) { success, link ->
                                                trackingLinkLoading = null
                                                if (success && link != null) {
                                                    showTrackingLinkDialog = link
                                                } else {
                                                    Toast.makeText(context, link ?: "Failed to get tracking link", Toast.LENGTH_SHORT).show()
                                                }
                                            }
                                        },
                                        onApplyClick = { showApplyDialog = offer }
                                    )
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Apply Dialog
        showApplyDialog?.let { offer ->
            AlertDialog(
                onDismissRequest = { showApplyDialog = null; promoDesc = "" },
                title = { Text("Request Access to ${offer.name}") },
                text = {
                    Column {
                        if (offer.requireApproval == 1) {
                            Text("This offer requires approval. Please describe how you plan to promote it:", style = MaterialTheme.typography.bodyMedium)
                            Spacer(modifier = Modifier.height(8.dp))
                            OutlinedTextField(
                                value = promoDesc,
                                onValueChange = { promoDesc = it },
                                modifier = Modifier.fillMaxWidth().height(100.dp),
                                placeholder = { Text("E.g., Facebook ads, email list, etc.") },
                                maxLines = 4
                            )
                        } else {
                            Text("This offer is instantly approved. Click confirm to get access.", style = MaterialTheme.typography.bodyMedium)
                        }
                    }
                },
                confirmButton = {
                    Button(
                        onClick = {
                            applyLoading = true
                            viewModel.applyOffer(offer.id, promoDesc) { success, msg ->
                                applyLoading = false
                                showApplyDialog = null
                                promoDesc = ""
                                Toast.makeText(context, msg, Toast.LENGTH_SHORT).show()
                            }
                        },
                        enabled = !applyLoading
                    ) {
                        if (applyLoading) {
                            CircularProgressIndicator(modifier = Modifier.size(24.dp), color = MaterialTheme.colorScheme.onPrimary)
                        } else {
                            Text("Confirm")
                        }
                    }
                },
                dismissButton = {
                    TextButton(onClick = { showApplyDialog = null; promoDesc = "" }) {
                        Text("Cancel")
                    }
                }
            )
        }

        // Tracking Link Dialog
        showTrackingLinkDialog?.let { link ->
            AlertDialog(
                onDismissRequest = { showTrackingLinkDialog = null },
                title = { Text("Your Tracking Link") },
                text = {
                    Column {
                        OutlinedTextField(
                            value = link,
                            onValueChange = {},
                            readOnly = true,
                            modifier = Modifier.fillMaxWidth()
                        )
                    }
                },
                confirmButton = {
                    Button(
                        onClick = {
                            val clipboard = context.getSystemService(android.content.Context.CLIPBOARD_SERVICE) as android.content.ClipboardManager
                            val clip = android.content.ClipData.newPlainText("Tracking Link", link)
                            clipboard.setPrimaryClip(clip)
                            Toast.makeText(context, "Link copied to clipboard", Toast.LENGTH_SHORT).show()
                            showTrackingLinkDialog = null
                        }
                    ) {
                        Text("Copy Link")
                    }
                },
                dismissButton = {
                    TextButton(onClick = { showTrackingLinkDialog = null }) {
                        Text("Close")
                    }
                }
            )
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FilterDropdown(
    label: String,
    options: List<String>,
    selected: String,
    onSelected: (String) -> Unit
) {
    var expanded by remember { mutableStateOf(false) }

    ExposedDropdownMenuBox(
        expanded = expanded,
        onExpandedChange = { expanded = !expanded }
    ) {
        OutlinedTextField(
            value = selected,
            onValueChange = {},
            readOnly = true,
            label = { Text(label) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            modifier = Modifier.menuAnchor().width(160.dp),
            colors = ExposedDropdownMenuDefaults.outlinedTextFieldColors(
                unfocusedContainerColor = MaterialTheme.colorScheme.surface,
                focusedContainerColor = MaterialTheme.colorScheme.surface
            )
        )
        ExposedDropdownMenu(
            expanded = expanded,
            onDismissRequest = { expanded = false }
        ) {
            options.forEach { option ->
                DropdownMenuItem(
                    text = { Text(option) },
                    onClick = {
                        onSelected(option)
                        expanded = false
                    }
                )
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun OfferListItem(offer: Offer, isLoadingLink: Boolean, onClick: () -> Unit, onApplyClick: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.Top) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = "OFF-${offer.id.toString().padStart(4, '0')}",
                        color = MaterialTheme.colorScheme.primary,
                        fontWeight = FontWeight.Bold,
                        fontSize = 12.sp
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(offer.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                    
                    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(top = 4.dp)) {
                        offer.category?.let {
                            Text(it, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            Text(" • ", fontSize = 12.sp)
                        }
                        
                        Badge(containerColor = MaterialTheme.colorScheme.secondaryContainer) {
                            Text(offer.payoutType.uppercase(), color = MaterialTheme.colorScheme.onSecondaryContainer, modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp))
                        }
                        
                        if (offer.offerType != null) {
                            Spacer(modifier = Modifier.width(4.dp))
                            Badge(containerColor = MaterialTheme.colorScheme.tertiaryContainer) {
                                Text(offer.offerType.uppercase(), color = MaterialTheme.colorScheme.onTertiaryContainer, modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp))
                            }
                        }
                    }
                }
                
                Column(horizontalAlignment = Alignment.End) {
                    Text(
                        text = "$${String.format("%.2f", offer.payout)}",
                        style = MaterialTheme.typography.titleLarge,
                        color = Color(0xFF10B981), // Green color matching web
                        fontWeight = FontWeight.Bold
                    )
                }
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            
            offer.description?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, maxLines = 2, overflow = TextOverflow.Ellipsis, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Spacer(modifier = Modifier.height(12.dp))
            }
            
            Divider(color = MaterialTheme.colorScheme.surfaceVariant)
            Spacer(modifier = Modifier.height(12.dp))
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Geo and Devices
                Column {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Public, contentDescription = "GEO", modifier = Modifier.size(16.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(modifier = Modifier.width(4.dp))
                        val geos = parseJsonArray(offer.countries)
                        if (geos.isEmpty()) Text("Global", fontSize = 12.sp)
                        else Text(geos.take(3).joinToString(", ") + if (geos.size > 3) " +${geos.size-3}" else "", fontSize = 12.sp)
                    }
                    Spacer(modifier = Modifier.height(4.dp))
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Devices, contentDescription = "Devices", modifier = Modifier.size(16.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(modifier = Modifier.width(4.dp))
                        val devs = parseJsonArray(offer.devices)
                        if (devs.isEmpty()) Text("All Devices", fontSize = 12.sp)
                        else Text(devs.joinToString(", "), fontSize = 12.sp)
                    }
                }
                
                // Status and Action
                Column(horizontalAlignment = Alignment.End) {
                    val statusText = when(offer.accessStatus) {
                        "approved" -> "APPROVED"
                        "pending" -> "PENDING"
                        "rejected" -> "REJECTED"
                        else -> "NOT APPLIED"
                    }
                    val statusColor = when(offer.accessStatus) {
                        "approved" -> Color(0xFF10B981)
                        "pending" -> Color(0xFFF59E0B)
                        "rejected" -> Color(0xFFEF4444)
                        else -> MaterialTheme.colorScheme.onSurfaceVariant
                    }
                    
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Box(modifier = Modifier.size(8.dp).background(statusColor, RoundedCornerShape(50)))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(statusText, fontSize = 10.sp, fontWeight = FontWeight.Bold, color = statusColor)
                    }
                    
                    Spacer(modifier = Modifier.height(8.dp))
                    
                    if (offer.accessStatus == "approved") {
                        Button(
                            onClick = onClick,
                            enabled = !isLoadingLink,
                            colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary),
                            contentPadding = PaddingValues(horizontal = 12.dp, vertical = 4.dp),
                            modifier = Modifier.height(32.dp)
                        ) {
                            if (isLoadingLink) {
                                CircularProgressIndicator(modifier = Modifier.size(16.dp), color = MaterialTheme.colorScheme.onPrimary, strokeWidth = 2.dp)
                            } else {
                                Text("Get Link", fontSize = 12.sp)
                            }
                        }
                    } else if (offer.accessStatus == null || offer.accessStatus == "removed") {
                        Button(
                            onClick = onApplyClick,
                            colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.secondary),
                            contentPadding = PaddingValues(horizontal = 12.dp, vertical = 4.dp),
                            modifier = Modifier.height(32.dp)
                        ) {
                            Text("Request Access", fontSize = 12.sp)
                        }
                    }
                }
            }
        }
    }
}

fun parseJsonArray(jsonStr: String?): List<String> {
    if (jsonStr.isNullOrBlank()) return emptyList()
    return try {
        val jsonArray = Json.parseToJsonElement(jsonStr).jsonArray
        jsonArray.map { it.jsonPrimitive.content }
    } catch (e: Exception) {
        emptyList()
    }
}
