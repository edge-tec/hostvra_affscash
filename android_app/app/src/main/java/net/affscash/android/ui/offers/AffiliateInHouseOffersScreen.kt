package net.affscash.android.ui.offers

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
import net.affscash.android.data.model.Offer
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.jsonArray
import kotlinx.serialization.json.jsonPrimitive

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AffiliateInHouseOffersScreen(
    viewModel: AffiliateInHouseOffersViewModel = hiltViewModel(),
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
                title = { Text("In-House Offers") },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            
            // Filters Section
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(12.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                OutlinedTextField(
                    value = searchQuery,
                    onValueChange = { viewModel.searchQuery.value = it; viewModel.loadOffers() },
                    placeholder = { Text("Search in-house offers...") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search") },
                    colors = OutlinedTextFieldDefaults.colors(
                        unfocusedContainerColor = MaterialTheme.colorScheme.surface,
                        focusedContainerColor = MaterialTheme.colorScheme.surface
                    ),
                    shape = RoundedCornerShape(12.dp)
                )
                
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    FilterDropdown(
                        label = "Category",
                        options = listOf("All Categories", "Dating", "Sweepstakes", "Nutra", "Gaming", "Finance"),
                        selected = category,
                        onSelected = { viewModel.category.value = it; viewModel.loadOffers() },
                        modifier = Modifier.weight(1f)
                    )
                    FilterDropdown(
                        label = "Type",
                        options = listOf("All Types", "CPA", "CPL", "CPS", "RevShare"),
                        selected = payoutType,
                        onSelected = { viewModel.payoutType.value = it; viewModel.loadOffers() },
                        modifier = Modifier.weight(1f)
                    )
                }

                FilterDropdown(
                    label = "Access",
                    options = listOf("All Offers", "Request Approval", "Instantly Approved"),
                    selected = accessFilter,
                    onSelected = { viewModel.accessFilter.value = it; viewModel.loadOffers() },
                    modifier = Modifier.fillMaxWidth()
                )
            }

            HorizontalDivider()

            Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
                when (uiState) {
                    is AffiliateInHouseOffersState.Loading -> {
                        CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                    }
                    is AffiliateInHouseOffersState.Error -> {
                        val msg = (uiState as AffiliateInHouseOffersState.Error).message
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
                    is AffiliateInHouseOffersState.Success -> {
                        val offers = (uiState as AffiliateInHouseOffersState.Success).offers
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

