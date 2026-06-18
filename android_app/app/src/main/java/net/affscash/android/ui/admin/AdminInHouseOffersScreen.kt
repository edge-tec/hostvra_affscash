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
import kotlinx.coroutines.launch

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
            FloatingActionButton(onClick = onNavigateToCreateOffer) {
                Icon(Icons.Default.Add, contentDescription = "Create In-House Offer")
            }
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            // Header Row
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column {
                    Text(
                        text = "In-House Offers",
                        style = MaterialTheme.typography.headlineMedium
                    )
                    Text(
                        text = "Manage your own offers — no external advertiser needed",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.secondary
                    )
                }
                IconButton(onClick = { showFilters = !showFilters }) {
                    Icon(Icons.Default.FilterList, contentDescription = "Filters")
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
                        CircularProgressIndicator()
                    }
                }
                is AdminInHouseOffersUiState.Error -> {
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
                is AdminInHouseOffersUiState.Success -> {
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
fun AdminInHouseOffersFilterSection(
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
        Column(modifier = Modifier.padding(16.dp)) {
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
            Spacer(modifier = Modifier.height(16.dp))
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
