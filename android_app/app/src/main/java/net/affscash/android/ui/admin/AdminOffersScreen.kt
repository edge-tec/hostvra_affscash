package net.affscash.android.ui.admin

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.AdminOffer
import net.affscash.android.ui.offers.AdminOfferFilters
import net.affscash.android.ui.offers.AdminOffersUiState
import net.affscash.android.ui.offers.AdminOffersViewModel
import kotlinx.coroutines.launch

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
            FloatingActionButton(onClick = onNavigateToCreateOffer) {
                Icon(Icons.Default.Add, contentDescription = "Create Offer")
            }
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            Surface(
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.6f),
                shape = androidx.compose.foundation.shape.RoundedCornerShape(bottomStart = 24.dp, bottomEnd = 24.dp)
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(start = 24.dp, end = 24.dp, top = 24.dp, bottom = 24.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Surface(
                            shape = androidx.compose.foundation.shape.RoundedCornerShape(12.dp),
                            color = MaterialTheme.colorScheme.primary,
                            modifier = Modifier.size(48.dp)
                        ) {
                            Icon(
                                Icons.Default.LocalOffer,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.onPrimary,
                                modifier = Modifier.padding(12.dp)
                            )
                        }
                        Spacer(modifier = Modifier.width(16.dp))
                        Column {
                            Text(
                                text = "Manage Offers",
                                style = MaterialTheme.typography.headlineSmall,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onPrimaryContainer
                            )
                            Text(
                                text = "Browse and manage offers",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.8f)
                            )
                        }
                    }
                    IconButton(
                        onClick = { showFilters = !showFilters },
                        modifier = Modifier.background(MaterialTheme.colorScheme.surface.copy(alpha = 0.5f), androidx.compose.foundation.shape.RoundedCornerShape(12.dp))
                    ) {
                        Icon(Icons.Default.FilterList, contentDescription = "Filters", tint = MaterialTheme.colorScheme.primary)
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
                        CircularProgressIndicator()
                    }
                }
                is AdminOffersUiState.Error -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text(text = "Error: ${state.message}", color = MaterialTheme.colorScheme.error)
                            Spacer(modifier = Modifier.height(8.dp))
                            Button(onClick = { viewModel.loadOffers() }) {
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
                                .padding(horizontal = 16.dp),
                            contentPadding = PaddingValues(bottom = 80.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
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
                            Text("No offers available")
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
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 8.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            OutlinedTextField(
                value = filters.query,
                onValueChange = { q -> onUpdateFilters { it.copy(query = q) } },
                label = { Text("Search Offers") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true
            )
            Spacer(modifier = Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedTextField(
                    value = filters.status,
                    onValueChange = { s -> onUpdateFilters { it.copy(status = s) } },
                    label = { Text("Status") },
                    modifier = Modifier.weight(1f)
                )
                OutlinedTextField(
                    value = filters.category,
                    onValueChange = { c -> onUpdateFilters { it.copy(category = c) } },
                    label = { Text("Category") },
                    modifier = Modifier.weight(1f)
                )
            }
            Spacer(modifier = Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                TextButton(onClick = onClear) {
                    Text("Clear")
                }
                Spacer(modifier = Modifier.width(8.dp))
                Button(onClick = onApply) {
                    Text("Apply Filters")
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
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = androidx.compose.foundation.shape.RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "#${offer.id} - ${offer.name}", 
                    style = MaterialTheme.typography.titleMedium, 
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.weight(1f)
                )
                Badge(
                    containerColor = when(offer.status) {
                        "active" -> MaterialTheme.colorScheme.primary
                        "paused" -> MaterialTheme.colorScheme.error
                        else -> MaterialTheme.colorScheme.secondary
                    }
                ) {
                    Text(offer.status.uppercase(), modifier = Modifier.padding(horizontal = 4.dp))
                }
            }
            Spacer(modifier = Modifier.height(8.dp))
            
            Text(text = "Advertiser: ${offer.advName}", style = MaterialTheme.typography.bodyMedium)
            
            offer.category?.let {
                Text(text = "Category: $it", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.secondary)
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            Divider()
            Spacer(modifier = Modifier.height(8.dp))
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Column {
                    Text(text = "Type: ${offer.payoutType}", style = MaterialTheme.typography.bodySmall)
                    Text(text = "Payout: $${(( offer.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Bold)
                    Text(text = "Revenue: $${(( offer.revenue )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.secondary)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text(text = "Access: ${if (offer.requireApproval) "Approval" else "Public"}", style = MaterialTheme.typography.bodySmall)
                    Text(text = "Daily Cap: ${if (offer.dailyCap > 0) offer.dailyCap else "Unlimited"}", style = MaterialTheme.typography.bodySmall)
                }
            }
            
            if (offer.geoTargeting.isNotEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                Text(text = "GEOs: ${offer.geoTargeting.joinToString(", ")}", style = MaterialTheme.typography.bodySmall)
            }

            Spacer(modifier = Modifier.height(8.dp))
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                OutlinedButton(onClick = onEdit) {
                    Icon(Icons.Default.Edit, contentDescription = "Edit", modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Edit")
                }
                Row {
                    IconButton(onClick = onPauseActivate) {
                        Icon(
                            imageVector = if (offer.status == "active") Icons.Default.Pause else Icons.Default.PlayArrow, 
                            contentDescription = "Toggle Status",
                            tint = if (offer.status == "active") MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.primary
                        )
                    }
                    IconButton(onClick = onDelete) {
                        Icon(Icons.Default.Delete, contentDescription = "Delete", tint = MaterialTheme.colorScheme.error)
                    }
                }
            }
        }
    }
}
