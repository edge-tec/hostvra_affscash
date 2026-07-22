package net.affscash.android.ui.offers

import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Clear
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
import net.affscash.android.ui.dashboard.StatusBadge

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
                            .size(42.dp)
                            .clip(RoundedCornerShape(12.dp))
                            .background(PremiumUI.HeaderGradient),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            Icons.Outlined.Storefront,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(22.dp)
                        )
                    }
                    Spacer(modifier = Modifier.width(10.dp))
                    Column {
                        Text(
                            text = "In-House Offers",
                            fontSize = 18.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF0F172A)
                        )
                        Text(
                            text = "Exclusive in-house affiliate offers",
                            fontSize = 12.sp,
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
                    Box(
                        modifier = Modifier.padding(10.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            Icons.Outlined.FilterList,
                            contentDescription = "Filter",
                            tint = Color(0xFF4F46E5),
                            modifier = Modifier.size(20.dp)
                        )
                    }
                }
            }
        }

        // 3D Search & Filters Section
        GlassCard(
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 4.dp),
            elevation = 2.dp
        ) {
            Column(modifier = Modifier.fillMaxWidth()) {
                OutlinedTextField(
                    value = searchQuery,
                    onValueChange = { viewModel.searchQuery.value = it; viewModel.loadOffers() },
                    placeholder = { Text("Search in-house offers...", fontSize = 13.sp) },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    leadingIcon = { Icon(Icons.Outlined.Search, contentDescription = "Search", tint = Color(0xFF64748B)) },
                    trailingIcon = {
                        if (searchQuery.isNotEmpty()) {
                            IconButton(onClick = { viewModel.searchQuery.value = ""; viewModel.loadOffers() }) {
                                Icon(Icons.Default.Clear, contentDescription = "Clear", tint = Color(0xFF64748B))
                            }
                        }
                    },
                    shape = RoundedCornerShape(12.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = Color(0xFF4F46E5),
                        unfocusedBorderColor = Color(0xFFE2E8F0),
                        focusedContainerColor = Color(0xFFF8FAFC),
                        unfocusedContainerColor = Color(0xFFF8FAFC)
                    )
                )
                
                AnimatedVisibility(visible = showFilters) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 10.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
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
            when (val state = uiState) {
                is AffiliateInHouseOffersState.Loading -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = Color(0xFF4F46E5))
                    }
                }
                is AffiliateInHouseOffersState.Error -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text(state.message, color = Color(0xFFEF4444))
                            Spacer(modifier = Modifier.height(6.dp))
                            Button(
                                onClick = { viewModel.loadOffers() },
                                shape = PremiumUI.ButtonShape,
                                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                            ) {
                                Text("Retry")
                            }
                        }
                    }
                }
                is AffiliateInHouseOffersState.Success -> {
                    if (state.offers.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Text("No in-house offers match your filters.", color = Color(0xFF64748B))
                        }
                    } else {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(horizontal = 12.dp),
                            contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            items(state.offers) { offer ->
                                OfferListItem(
                                    offer = offer,
                                    isLoadingLink = trackingLinkLoading == offer.id,
                                    onClick = { onOfferClick(offer.id) },
                                    onApplyClick = { showApplyDialog = offer }
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}
