package net.affscash.android.ui.offers

import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
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
import net.affscash.android.data.model.Offer
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun OfferScreen(
    viewModel: AffiliateOffersViewModel = hiltViewModel(),
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
    var showFilters by remember { mutableStateOf(false) }
    
    val context = LocalContext.current

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(PremiumUI.PageBackground)
    ) {
        // 3D Glass Header Bar
        Surface(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 12.dp, vertical = 8.dp),
            shape = PremiumUI.CardShape,
            color = Color.White,
            shadowElevation = 2.dp,
            border = PremiumUI.GlassBorder
        ) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 14.dp, vertical = 12.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Box(
                        modifier = Modifier
                            .size(40.dp)
                            .clip(RoundedCornerShape(12.dp))
                            .background(PremiumUI.HeaderGradient),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            Icons.Outlined.LocalOffer,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(20.dp)
                        )
                    }
                    Spacer(modifier = Modifier.width(10.dp))
                    Column {
                        Text(
                            text = "Available Offers",
                            fontSize = 18.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF0F172A)
                        )
                        Text(
                            text = "Explore & request offer access",
                            fontSize = 11.sp,
                            color = Color(0xFF64748B)
                        )
                    }
                }

                Surface(
                    onClick = { showFilters = !showFilters },
                    shape = RoundedCornerShape(12.dp),
                    color = if (showFilters) Color(0xFFEEF2FF) else Color(0xFFF8FAFC),
                    border = BorderStroke(1.dp, Color(0xFFE2E8F0))
                ) {
                    Box(modifier = Modifier.padding(10.dp), contentAlignment = Alignment.Center) {
                        Icon(
                            Icons.Outlined.FilterList,
                            contentDescription = "Filters",
                            tint = Color(0xFF4F46E5),
                            modifier = Modifier.size(20.dp)
                        )
                    }
                }
            }
        }

        // Search Bar & Filter Accordion
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 12.dp, vertical = 2.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            OutlinedTextField(
                value = searchQuery,
                onValueChange = { viewModel.searchQuery.value = it; viewModel.loadOffers() },
                placeholder = { Text("Search offers by name, ID or category...", fontSize = 13.sp) },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search", tint = Color(0xFF64748B), modifier = Modifier.size(20.dp)) },
                shape = RoundedCornerShape(12.dp),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor = Color(0xFF4F46E5),
                    unfocusedBorderColor = Color(0xFFE2E8F0),
                    focusedContainerColor = Color.White,
                    unfocusedContainerColor = Color.White
                )
            )
            
            AnimatedVisibility(visible = showFilters) {
                GlassCard(elevation = 2.dp) {
                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
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
                }
            }
        }

        Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
            when (uiState) {
                is OfferState.Loading -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = Color(0xFF4F46E5))
                    }
                }
                is OfferState.Error -> {
                    val msg = (uiState as OfferState.Error).message
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text(msg, color = Color(0xFFEF4444))
                            Spacer(modifier = Modifier.height(6.dp))
                            Button(
                                onClick = { viewModel.loadOffers() },
                                shape = PremiumUI.ButtonShape,
                                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                            ) { Text("Retry") }
                        }
                    }
                }
                is OfferState.Success -> {
                    val offers = (uiState as OfferState.Success).offers
                    if (offers.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                Icon(Icons.Outlined.SearchOff, contentDescription = null, modifier = Modifier.size(48.dp), tint = Color(0xFF94A3B8))
                                Spacer(modifier = Modifier.height(6.dp))
                                Text("No offers found matching search.", fontSize = 14.sp, color = Color(0xFF64748B))
                            }
                        }
                    } else {
                        LazyColumn(
                            modifier = Modifier.fillMaxSize().padding(horizontal = 12.dp),
                            contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            items(offers) { offer ->
                                OfferItemCard3D(
                                    offer = offer,
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
                                    onApplyClick = { showApplyDialog = offer },
                                    isLinkLoading = trackingLinkLoading == offer.id
                                )
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
                title = { Text("Request Access to ${offer.name}", fontSize = 16.sp, fontWeight = FontWeight.Bold) },
                text = {
                    Column {
                        if (offer.requireApproval == 1) {
                            Text("This offer requires approval. Please describe how you plan to promote it:", fontSize = 12.sp, color = Color(0xFF475569))
                            Spacer(modifier = Modifier.height(6.dp))
                            OutlinedTextField(
                                value = promoDesc,
                                onValueChange = { promoDesc = it },
                                modifier = Modifier.fillMaxWidth().height(100.dp),
                                placeholder = { Text("E.g., Facebook ads, email list, etc.", fontSize = 12.sp) },
                                shape = RoundedCornerShape(10.dp)
                            )
                        } else {
                            Text("This offer is instantly approved. Click confirm to get access.", fontSize = 12.sp, color = Color(0xFF475569))
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
                        enabled = !applyLoading,
                        shape = PremiumUI.ButtonShape,
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                    ) {
                        if (applyLoading) {
                            CircularProgressIndicator(modifier = Modifier.size(18.dp), color = Color.White)
                        } else {
                            Text("Confirm", fontWeight = FontWeight.Bold)
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
                title = { Text("Your Tracking Link", fontSize = 16.sp, fontWeight = FontWeight.Bold) },
                text = {
                    Column {
                        OutlinedTextField(
                            value = link,
                            onValueChange = {},
                            readOnly = true,
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(10.dp)
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
                        },
                        shape = PremiumUI.ButtonShape,
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                    ) {
                        Text("Copy Link", fontWeight = FontWeight.Bold)
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

@Composable
fun OfferItemCard3D(
    offer: Offer,
    onClick: () -> Unit,
    onApplyClick: () -> Unit,
    isLinkLoading: Boolean
) {
    GlassCard(
        modifier = Modifier.fillMaxWidth(),
        elevation = 2.dp
    ) {
        Column(modifier = Modifier.fillMaxWidth()) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Surface(color = Color(0xFFEEF2FF), shape = RoundedCornerShape(4.dp)) {
                        Text(
                            text = "OFF-${String.format("%04d", offer.id)}",
                            modifier = Modifier.padding(horizontal = 5.dp, vertical = 1.dp),
                            fontSize = 10.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = Color(0xFF4F46E5)
                        )
                    }
                    Spacer(modifier = Modifier.height(2.dp))
                    Text(
                        text = offer.name,
                        fontWeight = FontWeight.Bold,
                        fontSize = 15.sp,
                        color = Color(0xFF0F172A),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }

                Text(
                    text = "$${"%.2f".format(offer.payout)}",
                    fontWeight = FontWeight.ExtraBold,
                    fontSize = 17.sp,
                    color = Color(0xFF059669)
                )
            }

            Spacer(modifier = Modifier.height(6.dp))

            // Tags Pill Row
            Row(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                if (offer.category != null) {
                    Surface(color = Color(0xFFF1F5F9), shape = RoundedCornerShape(6.dp)) {
                        Text(offer.category, modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), fontSize = 10.sp, color = Color(0xFF475569))
                    }
                }
                Surface(color = Color(0xFFEEF2FF), shape = RoundedCornerShape(6.dp)) {
                    Text(offer.payoutType.uppercase(), modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), fontSize = 10.sp, fontWeight = FontWeight.Bold, color = Color(0xFF4F46E5))
                }
            }

            if (!offer.description.isNullOrBlank()) {
                Spacer(modifier = Modifier.height(6.dp))
                Text(
                    text = offer.description,
                    fontSize = 11.sp,
                    color = Color(0xFF64748B),
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis
                )
            }

            Spacer(modifier = Modifier.height(8.dp))
            HorizontalDivider(color = Color(0xFFF1F5F9))
            Spacer(modifier = Modifier.height(6.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = offer.countries.takeIf { !it.isNullOrBlank() } ?: "Global Access",
                    fontSize = 11.sp,
                    color = Color(0xFF64748B),
                    maxLines = 1,
                    modifier = Modifier.weight(1f)
                )

                if (offer.accessStatus == "approved") {
                    Button(
                        onClick = onClick,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.height(32.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF059669)),
                        contentPadding = PaddingValues(horizontal = 10.dp)
                    ) {
                        if (isLinkLoading) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(14.dp))
                        } else {
                            Text("Get Tracking Link", fontSize = 11.sp, fontWeight = FontWeight.Bold)
                        }
                    }
                } else if (offer.accessStatus == "pending") {
                    Surface(color = Color(0xFFFEF3C7), shape = RoundedCornerShape(6.dp)) {
                        Text("PENDING APPROVAL", modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp), fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFFD97706))
                    }
                } else {
                    OutlinedButton(
                        onClick = onApplyClick,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.height(32.dp),
                        border = BorderStroke(1.dp, Color(0xFF818CF8)),
                        contentPadding = PaddingValues(horizontal = 10.dp)
                    ) {
                        Text("Request Access", fontSize = 11.sp, color = Color(0xFF4F46E5), fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}
