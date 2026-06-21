package net.affscash.android.ui.admin

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.filled.FilterList
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.ManagerOfferApprovalRequest

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminOfferApprovalsScreen(
    viewModel: AdminOfferApprovalsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    LaunchedEffect(uiState) {
        if (uiState is AdminOfferApprovalsUiState.Error) {
            Toast.makeText(context, (uiState as AdminOfferApprovalsUiState.Error).message, Toast.LENGTH_SHORT).show()
        }
    }

    var showFilters by remember { mutableStateOf(false) }

    if (showFilters && uiState is AdminOfferApprovalsUiState.Success) {
        val state = uiState as AdminOfferApprovalsUiState.Success
        ModalBottomSheet(
            onDismissRequest = { showFilters = false },
            sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true),
            dragHandle = { BottomSheetDefaults.DragHandle() },
            modifier = Modifier.fillMaxHeight(0.9f)
        ) {
            Column(
                modifier = Modifier.padding(12.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                Text("Filters", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                
                var searchQuery by remember { mutableStateOf(state.filterAff) }
                OutlinedTextField(
                    value = searchQuery,
                    onValueChange = { searchQuery = it },
                    placeholder = { Text("Search by name, email or code") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search", tint = MaterialTheme.colorScheme.primary) },
                    trailingIcon = {
                        if (searchQuery.isNotEmpty()) {
                            IconButton(onClick = { 
                                searchQuery = ""
                                viewModel.loadData(aff = "") 
                            }) {
                                Icon(Icons.Default.Clear, contentDescription = "Clear")
                            }
                        }
                    },
                    shape = MaterialTheme.shapes.medium,
                    colors = OutlinedTextFieldDefaults.colors(
                        unfocusedBorderColor = Color.Transparent,
                        focusedBorderColor = MaterialTheme.colorScheme.primary,
                        unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f),
                        focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f)
                    )
                )

                var expanded by remember { mutableStateOf(false) }
                ExposedDropdownMenuBox(
                    expanded = expanded,
                    onExpandedChange = { expanded = it },
                    modifier = Modifier.fillMaxWidth()
                ) {
                    val selectedOffer = state.allOffers.find { it.id == state.filterOfferId }
                    OutlinedTextField(
                        value = selectedOffer?.name ?: "All Offers",
                        onValueChange = {},
                        readOnly = true,
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                        modifier = Modifier.menuAnchor().fillMaxWidth(),
                        singleLine = true,
                        shape = MaterialTheme.shapes.medium,
                        colors = OutlinedTextFieldDefaults.colors(
                            unfocusedBorderColor = Color.Transparent,
                            focusedBorderColor = MaterialTheme.colorScheme.primary,
                            unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f),
                            focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f)
                        )
                    )
                    ExposedDropdownMenu(
                        expanded = expanded,
                        onDismissRequest = { expanded = false }
                    ) {
                        DropdownMenuItem(
                            text = { Text("All Offers") },
                            onClick = {
                                viewModel.loadData(offerId = null)
                                expanded = false
                            }
                        )
                        state.allOffers.forEach { offer ->
                            DropdownMenuItem(
                                text = { Text(offer.name) },
                                onClick = {
                                    viewModel.loadData(offerId = offer.id)
                                    expanded = false
                                }
                            )
                        }
                    }
                }

                Button(
                    onClick = { 
                        viewModel.loadData(aff = searchQuery)
                        showFilters = false
                    },
                    modifier = Modifier.fillMaxWidth().height(56.dp),
                    shape = MaterialTheme.shapes.medium
                ) {
                    Icon(Icons.Default.FilterList, contentDescription = "Apply Filters", modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Apply Filters")
                }
                
                Spacer(modifier = Modifier.height(8.dp))
            }
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Offer Approval Requests") },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    actionIconContentColor = MaterialTheme.colorScheme.onPrimary
                ),
                actions = {
                    IconButton(onClick = { showFilters = true }) {
                        Icon(Icons.Default.FilterList, contentDescription = "Filters")
                    }
                }
            )
        }
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
        ) {
            when (val state = uiState) {
                is AdminOfferApprovalsUiState.Loading -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator()
                    }
                }
                is AdminOfferApprovalsUiState.Error -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Text(state.message, color = MaterialTheme.colorScheme.error)
                    }
                }
                is AdminOfferApprovalsUiState.Success -> {
                    // Status Tabs
                    val tabs = listOf("pending" to "Pending", "approved" to "Approved", "rejected" to "Rejected", "all" to "All")
                    val selectedTabIndex = tabs.indexOfFirst { it.first == state.filterStatus }.coerceAtLeast(0)

                    ScrollableTabRow(
                        selectedTabIndex = selectedTabIndex,
                        edgePadding = 8.dp,
                        containerColor = MaterialTheme.colorScheme.surfaceVariant,
                        contentColor = MaterialTheme.colorScheme.primary
                    ) {
                        tabs.forEachIndexed { index, (key, title) ->
                            val badgeCount = if (key == "pending") state.pendingCount else null
                            Tab(
                                selected = selectedTabIndex == index,
                                onClick = { viewModel.loadData(status = key) },
                                text = {
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        Text(title)
                                        if (badgeCount != null && badgeCount > 0) {
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Badge { Text("$badgeCount") }
                                        }
                                    }
                                }
                            )
                        }
                    }

                    // Filters removed and moved to BottomSheet

                    // List
                    if (state.requests.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                Icon(Icons.Default.Search, contentDescription = null, modifier = Modifier.size(64.dp), tint = Color.Gray)
                                Spacer(modifier = Modifier.height(8.dp))
                                Text("No requests found", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                                Text("No approval requests at this time.", color = Color.Gray)
                            }
                        }
                    } else {
                        LazyColumn(
                            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            items(state.requests) { request ->
                                AdminApprovalRequestCard(
                                    request = request,
                                    onReview = { action -> viewModel.reviewRequest(request.affiliateId, request.offerId, action) }
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun AdminApprovalRequestCard(
    request: ManagerOfferApprovalRequest,
    onReview: (String) -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = request.offerName,
                        fontWeight = FontWeight.Bold,
                        style = MaterialTheme.typography.titleMedium
                    )
                    Text(text = "Payout: $${(( request.payoutAmount )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } } (${request.payoutType})", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                    if (request.offerCategory != null) {
                        Text(text = "Category: ${request.offerCategory}", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                    }
                }
                Spacer(modifier = Modifier.width(8.dp))
                AdminApprovalStatusBadge(status = request.status)
            }

            Spacer(modifier = Modifier.height(12.dp))
            HorizontalDivider()
            Spacer(modifier = Modifier.height(12.dp))

            Text("Affiliate Details", fontWeight = FontWeight.Bold, style = MaterialTheme.typography.bodyMedium)
            Text(text = "${request.affiliateName} (${request.affiliateCode})", style = MaterialTheme.typography.bodySmall)
            Text(text = request.affiliateEmail, style = MaterialTheme.typography.bodySmall, color = Color.Gray)
            
            Spacer(modifier = Modifier.height(8.dp))
            
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(text = "Clicks: ${request.totalClicks}", style = MaterialTheme.typography.bodySmall)
                Text(text = "Conversions: ${request.totalConversions}", style = MaterialTheme.typography.bodySmall)
            }

            if (!request.promotionDescription.isNullOrBlank()) {
                Spacer(modifier = Modifier.height(12.dp))
                Text("Promotion Plan:", fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodySmall)
                Text(text = request.promotionDescription, style = MaterialTheme.typography.bodySmall, color = Color.DarkGray)
            }

            if (request.status == "pending") {
                Spacer(modifier = Modifier.height(8.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    TextButton(onClick = { onReview("reject") }, colors = ButtonDefaults.textButtonColors(contentColor = MaterialTheme.colorScheme.error)) {
                        Text("Reject")
                    }
                    Spacer(modifier = Modifier.width(8.dp))
                    Button(onClick = { onReview("approve") }, colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4CAF50))) {
                        Text("Approve")
                    }
                }
            } else if (request.approvedAt != null) {
                Spacer(modifier = Modifier.height(12.dp))
                Text(text = "Actioned at: ${request.approvedAt}", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
            }
        }
    }
}

@Composable
fun AdminApprovalStatusBadge(status: String) {
    val (color, text) = when (status) {
        "approved" -> Color(0xFFE8F5E9) to Color(0xFF2E7D32)
        "pending" -> Color(0xFFFFF3E0) to Color(0xFFEF6C00)
        "rejected" -> Color(0xFFFFEBEE) to Color(0xFFC62828)
        else -> Color(0xFFF5F5F5) to Color(0xFF616161)
    }
    
    Box(
        modifier = Modifier
            .background(color = color, shape = MaterialTheme.shapes.small)
            .padding(horizontal = 8.dp, vertical = 4.dp)
    ) {
        Text(
            text = status.uppercase(),
            color = text,
            fontSize = 10.sp,
            fontWeight = FontWeight.Bold
        )
    }
}
