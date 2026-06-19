package net.affscash.android.ui.manager

import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.FilterList
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.ManagerFraudConversion

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerFraudReportsScreen(
    onNavigateBack: () -> Unit = {},
    viewModel: ManagerFraudReportsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    var showFilters by remember { mutableStateOf(false) }

    if (showFilters) {
        FraudReportFilterSheet(
            uiState = uiState,
            onUpdateFilters = { from, to, clickId, status, aff, affCode, offer, sMin, sMax, sort ->
                viewModel.updateFilters(from, to, clickId, status, aff, affCode, offer, sMin, sMax, sort)
            },
            onDismiss = { showFilters = false }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Fraud Reports", fontSize = 18.sp) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { showFilters = true }) {
                        Icon(Icons.Default.FilterList, contentDescription = "Filters")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    navigationIconContentColor = MaterialTheme.colorScheme.onPrimary,
                    actionIconContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            // Search and Filter Bar removed to use BottomSheet
            // Loading / Error / Data
            Box(modifier = Modifier.fillMaxSize().weight(1f)) {
                if (uiState.isLoading) {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                } else if (uiState.error != null) {
                    Column(
                        modifier = Modifier.align(Alignment.Center),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(uiState.error!!, color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadReport() }) {
                            Text("Retry")
                        }
                    }
                } else if (uiState.response != null) {
                    Column(modifier = Modifier.fillMaxSize()) {
                        // Totals summary boxes (horizontally scrollable)
                        uiState.response!!.totals?.let { totals ->
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .horizontalScroll(rememberScrollState())
                                    .padding(horizontal = 8.dp),
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                SummaryCard("TOTAL", totals.total.toString(), Color.Black)
                                SummaryCard("APPROVED", totals.approved.toString(), Color(0xFF10B981))
                                SummaryCard("PENDING", totals.pending.toString(), Color(0xFFF59E0B))
                                SummaryCard("BLOCKED", totals.blocked.toString(), Color(0xFFDC2626))
                                SummaryCard("FRAUD FLAGGED", totals.fraudFlagged.toString(), Color(0xFFDC2626))
                                SummaryCard("PAYOUT", "$${"%.2f".format(totals.payout)}", Color.Black)
                            }
                        }
                        
                        Spacer(modifier = Modifier.height(8.dp))

                        // Filtered conversions
                        val filteredConversions = uiState.response!!.conversions.filter { cv ->
                            val matchStatus = uiState.statusFilter == "All Statuses" || uiState.statusFilter.isEmpty() || cv.status.equals(uiState.statusFilter, ignoreCase = true)
                            val qClickId = uiState.clickId.lowercase()
                            val matchClickId = qClickId.isEmpty() || cv.clickId.lowercase().contains(qClickId) || cv.conversionId.lowercase().contains(qClickId)
                            val qAffiliate = uiState.affiliate.lowercase()
                            val matchAffiliate = qAffiliate.isEmpty() || qAffiliate == "all affiliates" || cv.affName.lowercase().contains(qAffiliate)
                            val qAffCode = uiState.affCode.lowercase()
                            val matchAffCode = qAffCode.isEmpty() || cv.affiliateCode.lowercase().contains(qAffCode)
                            val qOffer = uiState.offer.lowercase()
                            val matchOffer = qOffer.isEmpty() || qOffer == "all offers" || (cv.offerName ?: "").lowercase().contains(qOffer)
                            val score = cv.ipqsScore ?: 0
                            val scoreMin = uiState.scoreMin.toIntOrNull()
                            val matchScoreMin = scoreMin == null || score >= scoreMin
                            val scoreMax = uiState.scoreMax.toIntOrNull()
                            val matchScoreMax = scoreMax == null || score <= scoreMax
                            
                            matchStatus && matchClickId && matchAffiliate && matchAffCode && matchOffer && matchScoreMin && matchScoreMax
                        }.sortedWith { a, b ->
                            when (uiState.sortBy) {
                                "Score High to Low" -> (b.ipqsScore ?: 0).compareTo(a.ipqsScore ?: 0)
                                "Score Low to High" -> (a.ipqsScore ?: 0).compareTo(b.ipqsScore ?: 0)
                                else -> b.convertedAt.compareTo(a.convertedAt)
                            }
                        }

                        LazyColumn(modifier = Modifier.fillMaxSize()) {
                            if (filteredConversions.isEmpty()) {
                                item {
                                    Text(
                                        "No conversions match the filters.",
                                        modifier = Modifier.padding(16.dp),
                                        color = Color.Gray
                                    )
                                }
                            } else {
                                items(filteredConversions) { cv ->
                                    ManagerFraudConversionItem(cv)
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
fun SummaryCard(title: String, value: String, valueColor: Color) {
    Card(modifier = Modifier.width(100.dp)) {
        Column(
            modifier = Modifier.padding(8.dp).fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(value, fontSize = 15.sp, fontWeight = FontWeight.Bold, color = valueColor)
            Text(title, fontSize = 12.sp, color = Color.Gray, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
fun ManagerFraudConversionItem(cv: ManagerFraudConversion) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 8.dp, vertical = 4.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            // Header: Conv ID & Date
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(cv.conversionId.take(16) + "...", fontWeight = FontWeight.Bold, fontSize = 12.sp, color = MaterialTheme.colorScheme.primary)
                Text(cv.convertedAt, fontSize = 10.sp, color = Color.Gray)
            }
            
            Spacer(modifier = Modifier.height(4.dp))
            
            // Affiliate & Offer
            Text("Affiliate: ${cv.affName} (${cv.affiliateCode})", fontSize = 12.sp, fontWeight = FontWeight.Bold)
            Text("Offer: ${cv.offerName ?: "Unknown"}", fontSize = 11.sp)
            
            Spacer(modifier = Modifier.height(4.dp))
            
            // IP & Click ID
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("IP: ${cv.ipAddress ?: "-"}", fontSize = 11.sp, color = Color.Blue)
                Text("Click ID: ${cv.clickId.take(12)}...", fontSize = 10.sp, color = Color.Gray)
            }
            
            Spacer(modifier = Modifier.height(4.dp))
            
            // Device info
            Text("${cv.deviceType ?: "Unknown"} · ${cv.osVersion ?: "Unknown OS"}", fontSize = 11.sp, color = Color.DarkGray)
            Text(cv.userAgent?.take(50) ?: "Unknown UA", fontSize = 10.sp, color = Color.Gray)
            
            Spacer(modifier = Modifier.height(8.dp))
            
            // Metrics row
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column {
                    Text("Payout", fontSize = 13.sp, color = Color.Gray)
                    Text("$${"%.2f".format(cv.payout)}", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                }
                
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text("IPQS Score", fontSize = 13.sp, color = Color.Gray)
                    val score = cv.ipqsScore ?: 0
                    val scoreColor = if (score > 80) Color.Red else if (score > 50) Color(0xFFF59E0B) else Color(0xFF10B981)
                    Text("$score", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = scoreColor)
                }
                
                Column(horizontalAlignment = Alignment.End) {
                    Text("Status", fontSize = 13.sp, color = Color.Gray)
                    val statusColor = if (cv.status == "rejected") Color.Red else if (cv.status == "approved") Color(0xFF10B981) else Color(0xFFF59E0B)
                    Text(cv.status.uppercase(), fontSize = 12.sp, fontWeight = FontWeight.Bold, color = statusColor)
                }
            }
            
            // Rejection reason if any
            if (cv.status == "rejected" && cv.rejectionReason.isNotEmpty()) {
                Spacer(modifier = Modifier.height(4.dp))
                Text("Reason: ${cv.rejectionReason}", fontSize = 10.sp, color = Color.Red)
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FraudReportFilterSheet(
    uiState: ManagerFraudReportsUiState,
    onUpdateFilters: (String, String, String, String, String, String, String, String, String, String) -> Unit,
    onDismiss: () -> Unit
) {
    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
    
    val affiliates = remember(uiState.response) {
        val list = uiState.response?.conversions?.map { it.affName }?.distinct()?.sorted() ?: emptyList()
        listOf("All Affiliates") + list
    }
    
    val offers = remember(uiState.response) {
        val list = uiState.response?.conversions?.mapNotNull { it.offerName }?.distinct()?.sorted() ?: emptyList()
        listOf("All Offers") + list
    }

    val statuses = listOf("All Statuses", "Pending", "Approved", "Rejected")
    val sortOptions = listOf("Date Desc", "Date Asc", "Score High to Low", "Score Low to High")

    ModalBottomSheet(
        onDismissRequest = onDismiss,
        sheetState = sheetState,
        dragHandle = { BottomSheetDefaults.DragHandle() },
        modifier = Modifier.fillMaxHeight(0.9f)
    ) {
        Column(modifier = Modifier.padding(16.dp).verticalScroll(rememberScrollState())) {
            Text("Filters", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            Spacer(modifier = Modifier.height(16.dp))
            
            Text("Date Range", style = MaterialTheme.typography.labelMedium)
            ScrollableRow(listOf("Today", "Yesterday", "Last 7 Days", "Last 15 Days", "This Month", "Last Month", "Last 90 Days", "This Year", "Last Year")) { range ->
                FilterChip(selected = false, onClick = {}, label = { Text(range) })
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                StyledTextField(value = uiState.fromDate, onValueChange = { onUpdateFilters(it, uiState.toDate, uiState.clickId, uiState.statusFilter, uiState.affiliate, uiState.affCode, uiState.offer, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "From Date", modifier = Modifier.weight(1f))
                StyledTextField(value = uiState.toDate, onValueChange = { onUpdateFilters(uiState.fromDate, it, uiState.clickId, uiState.statusFilter, uiState.affiliate, uiState.affCode, uiState.offer, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "To Date", modifier = Modifier.weight(1f))
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                StyledTextField(value = uiState.clickId, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, it, uiState.statusFilter, uiState.affiliate, uiState.affCode, uiState.offer, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "Click ID", modifier = Modifier.weight(1f))
                DropdownFilterField(value = uiState.statusFilter, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, it, uiState.affiliate, uiState.affCode, uiState.offer, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "Status", options = statuses, modifier = Modifier.weight(1f))
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                DropdownFilterField(value = uiState.affiliate, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, uiState.statusFilter, it, uiState.affCode, uiState.offer, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "Affiliate", options = affiliates, modifier = Modifier.weight(1f))
                StyledTextField(value = uiState.affCode, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, uiState.statusFilter, uiState.affiliate, it, uiState.offer, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "Aff Code", modifier = Modifier.weight(1f))
            }

            Spacer(modifier = Modifier.height(12.dp))
            DropdownFilterField(value = uiState.offer, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, uiState.statusFilter, uiState.affiliate, uiState.affCode, it, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "Offer", options = offers, modifier = Modifier.fillMaxWidth())

            Spacer(modifier = Modifier.height(12.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                StyledTextField(value = uiState.scoreMin, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, uiState.statusFilter, uiState.affiliate, uiState.affCode, uiState.offer, it, uiState.scoreMax, uiState.sortBy) }, label = "Score Min", modifier = Modifier.weight(1f))
                StyledTextField(value = uiState.scoreMax, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, uiState.statusFilter, uiState.affiliate, uiState.affCode, uiState.offer, uiState.scoreMin, it, uiState.sortBy) }, label = "Score Max", modifier = Modifier.weight(1f))
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            DropdownFilterField(value = uiState.sortBy, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, uiState.statusFilter, uiState.affiliate, uiState.affCode, uiState.offer, uiState.scoreMin, uiState.scoreMax, it) }, label = "Sort By", options = sortOptions, modifier = Modifier.fillMaxWidth())
            
            Spacer(modifier = Modifier.height(24.dp))
            Button(onClick = onDismiss, modifier = Modifier.fillMaxWidth().height(56.dp), shape = MaterialTheme.shapes.medium) {
                Text("Apply Filters")
            }
            Spacer(modifier = Modifier.height(24.dp))
        }
    }
}

@Composable
fun StyledTextField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    modifier: Modifier = Modifier
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label) },
        modifier = modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.medium,
        singleLine = true,
        colors = OutlinedTextFieldDefaults.colors(
            unfocusedBorderColor = Color.Transparent,
            focusedBorderColor = MaterialTheme.colorScheme.primary,
            unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f),
            focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f)
        )
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DropdownFilterField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    options: List<String>,
    modifier: Modifier = Modifier
) {
    var expanded by remember { mutableStateOf(false) }
    ExposedDropdownMenuBox(
        expanded = expanded,
        onExpandedChange = { expanded = it },
        modifier = modifier
    ) {
        OutlinedTextField(
            value = value,
            onValueChange = {},
            readOnly = true,
            label = { Text(label) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            modifier = Modifier.menuAnchor().fillMaxWidth(),
            shape = MaterialTheme.shapes.medium,
            singleLine = true,
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
            options.forEach { option ->
                DropdownMenuItem(
                    text = { Text(option) },
                    onClick = {
                        onValueChange(option)
                        expanded = false
                    }
                )
            }
        }
    }
}

@Composable
fun ScrollableRow(items: List<String>, content: @Composable (String) -> Unit) {
    Row(modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        items.forEach { item ->
            content(item)
        }
    }
}
