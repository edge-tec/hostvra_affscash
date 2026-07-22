package net.affscash.android.ui.admin

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.AdminOffer
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI
import net.affscash.android.ui.dashboard.StatusBadge
import net.affscash.android.ui.offers.AdminOfferFilters
import net.affscash.android.ui.offers.AdminOffersUiState
import net.affscash.android.ui.offers.AdminOffersViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminOffersScreen(
    onNavigateToCreateOffer: () -> Unit,
    onNavigateToEditOffer: (Int) -> Unit,
    viewModel: AdminOffersViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val filters by viewModel.filters.collectAsState()
    val actionMessage by viewModel.actionMessage.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }
    
    var showFilters by remember { mutableStateOf(false) }

    LaunchedEffect(actionMessage) {
        actionMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearActionMessage()
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        floatingActionButton = {
            Surface(
                onClick = onNavigateToCreateOffer,
                shape = RoundedCornerShape(16.dp),
                color = Color.Transparent,
                shadowElevation = 6.dp
            ) {
                Box(
                    modifier = Modifier
                        .size(56.dp)
                        .background(PremiumUI.PrimaryGradient),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(Icons.Outlined.Add, contentDescription = "Create Offer", tint = Color.White, modifier = Modifier.size(26.dp))
                }
            }
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .background(PremiumUI.PageBackground)
                .padding(paddingValues)
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
                                Icons.Outlined.LocalOffer,
                                contentDescription = null,
                                tint = Color.White,
                                modifier = Modifier.size(22.dp)
                            )
                        }
                        Spacer(modifier = Modifier.width(10.dp))
                        Column {
                            Text(
                                text = "Manage Offers",
                                fontSize = 18.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF0F172A)
                            )
                            Text(
                                text = "Browse and manage offers",
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
                                contentDescription = "Filters",
                                tint = Color(0xFF4F46E5),
                                modifier = Modifier.size(20.dp)
                            )
                        }
                    }
                }
            }

            AnimatedVisibility(visible = showFilters) {
                AdminOffersFilterSection(
                    filters = filters,
                    onUpdateFilters = { viewModel.updateFilter(it) },
                    onApply = { viewModel.applyFilters() },
                    onClear = { viewModel.clearFilters() }
                )
            }

            when (val state = uiState) {
                is AdminOffersUiState.Loading -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = Color(0xFF4F46E5))
                    }
                }
                is AdminOffersUiState.Error -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text(text = "Error: ${state.message}", color = Color(0xFFEF4444))
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
                is AdminOffersUiState.Success -> {
                    val offers = state.data.data
                    if (offers.isNotEmpty()) {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(horizontal = 12.dp),
                            contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            items(offers) { offer ->
                                AdminOfferItem(
                                    offer = offer,
                                    onEdit = { onNavigateToEditOffer(offer.id) },
                                    onPauseActivate = { 
                                        val newStatus = if (offer.status == "active") "paused" else "active"
                                        viewModel.updateOfferStatus(offer.id, newStatus) 
                                    },
                                    onDelete = { viewModel.deleteOffer(offer.id) }
                                )
                            }
                        }
                    } else {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Text("No offers available", color = Color(0xFF64748B))
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun AdminOffersFilterSection(
    filters: AdminOfferFilters,
    onUpdateFilters: ((AdminOfferFilters) -> AdminOfferFilters) -> Unit,
    onApply: () -> Unit,
    onClear: () -> Unit
) {
    GlassCard(
        modifier = Modifier.padding(horizontal = 12.dp, vertical = 4.dp),
        elevation = 3.dp
    ) {
        Column(modifier = Modifier.fillMaxWidth()) {
            OutlinedTextField(
                value = filters.query,
                onValueChange = { q -> onUpdateFilters { it.copy(query = q) } },
                placeholder = { Text("Search Offers by name or ID", fontSize = 13.sp) },
                leadingIcon = { Icon(Icons.Outlined.Search, contentDescription = null, tint = Color(0xFF64748B)) },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                shape = RoundedCornerShape(12.dp),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor = Color(0xFF4F46E5),
                    unfocusedBorderColor = Color(0xFFE2E8F0),
                    focusedContainerColor = Color(0xFFF8FAFC),
                    unfocusedContainerColor = Color(0xFFF8FAFC)
                )
            )
            Spacer(modifier = Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedTextField(
                    value = filters.status,
                    onValueChange = { s -> onUpdateFilters { it.copy(status = s) } },
                    placeholder = { Text("Status", fontSize = 13.sp) },
                    modifier = Modifier.weight(1f),
                    singleLine = true,
                    shape = RoundedCornerShape(12.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = Color(0xFF4F46E5),
                        unfocusedBorderColor = Color(0xFFE2E8F0),
                        focusedContainerColor = Color(0xFFF8FAFC),
                        unfocusedContainerColor = Color(0xFFF8FAFC)
                    )
                )
                OutlinedTextField(
                    value = filters.category,
                    onValueChange = { c -> onUpdateFilters { it.copy(category = c) } },
                    placeholder = { Text("Category", fontSize = 13.sp) },
                    modifier = Modifier.weight(1f),
                    singleLine = true,
                    shape = RoundedCornerShape(12.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = Color(0xFF4F46E5),
                        unfocusedBorderColor = Color(0xFFE2E8F0),
                        focusedContainerColor = Color(0xFFF8FAFC),
                        unfocusedContainerColor = Color(0xFFF8FAFC)
                    )
                )
            }
            Spacer(modifier = Modifier.height(10.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End, verticalAlignment = Alignment.CenterVertically) {
                TextButton(onClick = onClear) {
                    Text("Clear", color = Color(0xFF64748B), fontWeight = FontWeight.Medium)
                }
                Spacer(modifier = Modifier.width(6.dp))
                Button(
                    onClick = onApply,
                    shape = PremiumUI.ButtonShape,
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                ) {
                    Text("Apply Filters", fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}

@Composable
fun AdminOfferItem(
    offer: AdminOffer,
    onEdit: () -> Unit,
    onPauseActivate: () -> Unit,
    onDelete: () -> Unit
) {
    GlassCard(elevation = 2.dp) {
        Column(modifier = Modifier.fillMaxWidth()) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.weight(1f)) {
                    Surface(
                        color = Color(0xFFEEF2FF),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text(
                            text = "#${offer.id}",
                            fontSize = 12.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = Color(0xFF4F46E5),
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                    Spacer(modifier = Modifier.width(8.dp))
                    Text(
                        text = offer.name, 
                        fontSize = 15.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF0F172A),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }
                
                Spacer(modifier = Modifier.width(6.dp))
                StatusBadge(status = offer.status)
            }

            Spacer(modifier = Modifier.height(8.dp))
            
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(text = "Advertiser: ", fontSize = 12.sp, color = Color(0xFF64748B))
                Text(text = offer.advName, fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFF334155))
            }
            
            offer.category?.let {
                Spacer(modifier = Modifier.height(2.dp))
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(text = "Category: ", fontSize = 12.sp, color = Color(0xFF64748B))
                    Text(text = it, fontSize = 12.sp, fontWeight = FontWeight.Medium, color = Color(0xFF4F46E5))
                }
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            HorizontalDivider(color = Color(0xFFF1F5F9))
            Spacer(modifier = Modifier.height(8.dp))
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Column {
                    Text(text = "Type: ${offer.payoutType}", fontSize = 11.sp, color = Color(0xFF64748B))
                    Text(
                        text = "Payout: $${(( offer.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                        fontSize = 14.sp,
                        fontWeight = FontWeight.ExtraBold,
                        color = Color(0xFF4F46E5)
                    )
                    Text(
                        text = "Revenue: $${(( offer.revenue )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                        fontSize = 11.sp,
                        color = Color(0xFF64748B)
                    )
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text(text = "Access: ${if (offer.requireApproval) "Approval" else "Public"}", fontSize = 11.sp, color = Color(0xFF64748B))
                    Text(text = "Daily Cap: ${if (offer.dailyCap > 0) offer.dailyCap else "Unlimited"}", fontSize = 11.sp, fontWeight = FontWeight.Medium, color = Color(0xFF334155))
                }
            }
            
            if (offer.geoTargeting.isNotEmpty()) {
                Spacer(modifier = Modifier.height(6.dp))
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(text = "GEOs: ", fontSize = 11.sp, color = Color(0xFF64748B))
                    Text(text = offer.geoTargeting.joinToString(", "), fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color(0xFF059669))
                }
            }

            Spacer(modifier = Modifier.height(10.dp))
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                OutlinedButton(
                    onClick = onEdit,
                    shape = RoundedCornerShape(10.dp),
                    border = BorderStroke(1.dp, Color(0xFFCBD5E1)),
                    contentPadding = PaddingValues(horizontal = 14.dp, vertical = 6.dp)
                ) {
                    Icon(Icons.Outlined.Edit, contentDescription = "Edit", modifier = Modifier.size(16.dp), tint = Color(0xFF334155))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Edit", color = Color(0xFF334155), fontWeight = FontWeight.SemiBold, fontSize = 12.sp)
                }

                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    IconButton(onClick = onPauseActivate) {
                        Icon(
                            imageVector = if (offer.status == "active") Icons.Outlined.PauseCircle else Icons.Outlined.PlayCircle, 
                            contentDescription = "Toggle Status",
                            tint = if (offer.status == "active") Color(0xFFF59E0B) else Color(0xFF10B981),
                            modifier = Modifier.size(24.dp)
                        )
                    }
                    IconButton(onClick = onDelete) {
                        Icon(
                            Icons.Outlined.Delete,
                            contentDescription = "Delete",
                            tint = Color(0xFFEF4444),
                            modifier = Modifier.size(24.dp)
                        )
                    }
                }
            }
        }
    }
}
