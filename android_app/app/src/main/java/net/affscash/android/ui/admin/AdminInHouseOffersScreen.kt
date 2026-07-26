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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.AdminOffer
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI
import net.affscash.android.ui.dashboard.StatusBadge
import net.affscash.android.ui.offers.AdminOfferFilters

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminInHouseOffersScreen(
    onNavigateToCreateOffer: () -> Unit,
    onNavigateToEditOffer: (Int) -> Unit,
    viewModel: AdminInHouseOffersViewModel = hiltViewModel()
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
                    net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.Add, contentDescription = "Create In-House Offer", tint = Color.White, modifier = Modifier.size(26.dp))
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
                            net.affscash.android.ui.dashboard.GradientIcon(
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
                                text = "Manage your own in-house offers",
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
                            net.affscash.android.ui.dashboard.GradientIcon(
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
                AdminInHouseOffersFilterSection(
                    filters = filters,
                    onUpdateFilters = { viewModel.updateFilter(it) },
                    onApply = { viewModel.applyFilters() },
                    onClear = { viewModel.clearFilters() }
                )
            }

            when (val state = uiState) {
                is AdminInHouseOffersUiState.Loading -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = Color(0xFF4F46E5))
                    }
                }
                is AdminInHouseOffersUiState.Error -> {
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
                is AdminInHouseOffersUiState.Success -> {
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
                            Text("No in-house offers available", color = Color(0xFF64748B))
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun AdminInHouseOffersFilterSection(
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
                leadingIcon = { net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.Search, contentDescription = null, tint = Color(0xFF64748B)) },
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
