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
import androidx.compose.material.icons.filled.AdsClick
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.LocalOffer
import androidx.compose.material.icons.filled.Router
import androidx.compose.material.icons.filled.Smartphone
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
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale
import androidx.compose.foundation.clickable

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
            Surface(
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.6f),
                shape = androidx.compose.foundation.shape.RoundedCornerShape(bottomStart = 24.dp, bottomEnd = 24.dp)
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(start = 8.dp, end = 16.dp, top = 28.dp, bottom = 10.dp),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        IconButton(onClick = onNavigateBack) {
                            Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                        }
                        Text(
                            text = "Fraud Reports",
                            style = MaterialTheme.typography.titleLarge,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                    }
                    IconButton(onClick = { showFilters = true }) {
                        Icon(Icons.Default.FilterList, contentDescription = "Filters", tint = MaterialTheme.colorScheme.primary)
                    }
                }
            }
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
                                SummaryCard("TOTAL", totals.total.toString(), MaterialTheme.colorScheme.primary, MaterialTheme.colorScheme.primaryContainer)
                                SummaryCard("APPROVED", totals.approved.toString(), Color(0xFF059669), Color(0xFFD1FAE5))
                                SummaryCard("PENDING", totals.pending.toString(), Color(0xFFD97706), Color(0xFFFEF3C7))
                                SummaryCard("BLOCKED", totals.blocked.toString(), MaterialTheme.colorScheme.error, MaterialTheme.colorScheme.errorContainer)
                                SummaryCard("FRAUD FLAGGED", totals.fraudFlagged.toString(), MaterialTheme.colorScheme.error, MaterialTheme.colorScheme.errorContainer)
                                SummaryCard("PAYOUT", "$${"%.2f".format(totals.payout)}", MaterialTheme.colorScheme.primary, MaterialTheme.colorScheme.primaryContainer)
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
                            
                            val qFromDate = uiState.fromDate
                            val qToDate = uiState.toDate
                            val datePart = cv.convertedAt.take(10)
                            val matchDate = (qFromDate.isEmpty() || datePart >= qFromDate) &&
                                            (qToDate.isEmpty() || datePart <= qToDate)
                            
                            matchStatus && matchClickId && matchAffiliate && matchAffCode && matchOffer && matchScoreMin && matchScoreMax && matchDate
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
fun SummaryCard(title: String, value: String, valueColor: Color, containerColor: Color) {
    Card(
        modifier = Modifier.width(110.dp).height(70.dp),
        colors = CardDefaults.cardColors(containerColor = containerColor),
        shape = androidx.compose.foundation.shape.RoundedCornerShape(12.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(
            modifier = Modifier.padding(8.dp).fillMaxSize(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Text(value, fontSize = 18.sp, fontWeight = FontWeight.ExtraBold, color = valueColor)
            Spacer(modifier = Modifier.height(2.dp))
            Text(title, fontSize = 10.sp, color = valueColor.copy(alpha = 0.8f), fontWeight = FontWeight.Bold, maxLines = 1)
        }
    }
}

@Composable
fun ManagerFraudConversionItem(cv: ManagerFraudConversion) {
    var expandClickId by remember { mutableStateOf(false) }

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 6.dp),
        shape = androidx.compose.foundation.shape.RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            // Header
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.weight(1f)) {
                    Icon(Icons.Default.AdsClick, contentDescription = null, modifier = Modifier.size(14.dp), tint = Color.Gray)
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(
                        cv.conversionId.take(12) + "...", 
                        fontSize = 12.sp, 
                        fontFamily = androidx.compose.ui.text.font.FontFamily.Monospace,
                        color = MaterialTheme.colorScheme.onSurface,
                        fontWeight = FontWeight.Bold
                    )
                }
                Text(
                    cv.convertedAt, 
                    fontSize = 10.sp, 
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            Spacer(modifier = Modifier.height(4.dp))
            
            // Affiliate
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Default.Person, contentDescription = null, modifier = Modifier.size(12.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                Spacer(modifier = Modifier.width(4.dp))
                Text(
                    "Affiliate: ${cv.affName} (${cv.affiliateCode})", 
                    fontSize = 12.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 1,
                    overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis
                )
            }
            Spacer(modifier = Modifier.height(2.dp))
            
            // Offer
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Default.LocalOffer, contentDescription = null, modifier = Modifier.size(12.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                Spacer(modifier = Modifier.width(4.dp))
                Text(
                    "Offer: ${cv.offerName ?: "Unknown"}", 
                    fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 1,
                    overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis
                )
            }

            Spacer(modifier = Modifier.height(6.dp))

            // IP & Loc
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.Router, contentDescription = null, modifier = Modifier.size(12.dp), tint = Color(0xFF3B82F6))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("IP: ${cv.ipAddress ?: "-"}", fontSize = 11.sp, color = Color(0xFF3B82F6))
                }
                
                val country = cv.country?.takeIf { it.isNotBlank() && it.lowercase() != "unknown" } ?: "Unknown Country"
                val state = cv.region?.takeIf { it.isNotBlank() && it.lowercase() != "unknown" } ?: "Unknown State"
                val city = cv.city?.takeIf { it.isNotBlank() && it.lowercase() != "unknown" }
                val locStr = listOfNotNull(country, state, city).joinToString(" | ")
                Text(locStr, fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            
            Spacer(modifier = Modifier.height(2.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.Smartphone, contentDescription = null, modifier = Modifier.size(12.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("${cv.deviceType ?: "Unknown"} · ${cv.osVersion ?: "Unknown OS"}", fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }

                Text(
                    text = "Click ID: " + if (expandClickId) cv.clickId else if (cv.clickId.length > 10) cv.clickId.take(10) + "..." else cv.clickId,
                    fontSize = 10.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.clickable { expandClickId = !expandClickId },
                    fontFamily = androidx.compose.ui.text.font.FontFamily.Monospace
                )
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.3f))
            Spacer(modifier = Modifier.height(8.dp))
            
            // Metrics row
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Status
                val statusColor = if (cv.status == "rejected") Color(0xFFEF4444) else if (cv.status == "approved") Color(0xFF059669) else Color(0xFFF59E0B)
                val statusBg = if (cv.status == "rejected") Color(0xFFFEE2E2) else if (cv.status == "approved") Color(0xFFD1FAE5) else Color(0xFFFEF3C7)
                
                Surface(
                    color = statusBg,
                    shape = androidx.compose.foundation.shape.RoundedCornerShape(6.dp)
                ) {
                    Text(
                        cv.status.uppercase(), 
                        fontSize = 10.sp, 
                        fontWeight = FontWeight.Bold, 
                        color = statusColor,
                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 3.dp)
                    )
                }
                
                // IPQS Score
                val score = cv.ipqsScore ?: 0
                val scoreColor = if (score > 80) Color(0xFFEF4444) else if (score > 50) Color(0xFFF59E0B) else Color(0xFF10B981)
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text("IPQS Score: ", fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text("$score", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = scoreColor)
                }

                // Payout
                Text("$${"%.2f".format(cv.payout)}", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
            }
            
            // Rejection reason if any
            if (cv.status == "rejected" && cv.rejectionReason.isNotEmpty()) {
                Spacer(modifier = Modifier.height(6.dp))
                Surface(
                    color = Color(0xFFFEF2F2),
                    shape = androidx.compose.foundation.shape.RoundedCornerShape(6.dp),
                    border = androidx.compose.foundation.BorderStroke(1.dp, Color(0xFFFECACA)),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("Reason: ${cv.rejectionReason}", fontSize = 10.sp, color = Color(0xFFEF4444), modifier = Modifier.padding(6.dp))
                }
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
        Column(modifier = Modifier.padding(horizontal = 20.dp, vertical = 12.dp).verticalScroll(rememberScrollState())) {
            Text("Filters", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface)
            Spacer(modifier = Modifier.height(16.dp))
            
            Text("Date Range", fontSize = 12.sp, fontWeight = FontWeight.Medium, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(modifier = Modifier.height(8.dp))
            ScrollableRow(listOf("Today", "Yesterday", "Last 7 Days", "Last 15 Days", "This Month", "Last Month", "Last 90 Days", "This Year", "Last Year")) { range ->
                FilterChip(
                    selected = false,
                    onClick = {
                        val (from, to) = getPresetDateRange(range)
                        onUpdateFilters(from, to, uiState.clickId, uiState.statusFilter, uiState.affiliate, uiState.affCode, uiState.offer, uiState.scoreMin, uiState.scoreMax, uiState.sortBy)
                    },
                    label = { Text(range, fontSize = 12.sp) },
                    shape = androidx.compose.foundation.shape.RoundedCornerShape(8.dp)
                )
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                StyledTextField(value = uiState.fromDate, onValueChange = { onUpdateFilters(it, uiState.toDate, uiState.clickId, uiState.statusFilter, uiState.affiliate, uiState.affCode, uiState.offer, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "From Date", modifier = Modifier.weight(1f))
                StyledTextField(value = uiState.toDate, onValueChange = { onUpdateFilters(uiState.fromDate, it, uiState.clickId, uiState.statusFilter, uiState.affiliate, uiState.affCode, uiState.offer, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "To Date", modifier = Modifier.weight(1f))
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                StyledTextField(value = uiState.clickId, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, it, uiState.statusFilter, uiState.affiliate, uiState.affCode, uiState.offer, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "Click ID", modifier = Modifier.weight(1f))
                DropdownFilterField(value = uiState.statusFilter, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, it, uiState.affiliate, uiState.affCode, uiState.offer, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "Status", options = statuses, modifier = Modifier.weight(1f))
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                DropdownFilterField(value = uiState.affiliate, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, uiState.statusFilter, it, uiState.affCode, uiState.offer, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "Affiliate", options = affiliates, modifier = Modifier.weight(1f))
                StyledTextField(value = uiState.affCode, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, uiState.statusFilter, uiState.affiliate, it, uiState.offer, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "Aff Code", modifier = Modifier.weight(1f))
            }

            Spacer(modifier = Modifier.height(8.dp))
            DropdownFilterField(value = uiState.offer, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, uiState.statusFilter, uiState.affiliate, uiState.affCode, it, uiState.scoreMin, uiState.scoreMax, uiState.sortBy) }, label = "Offer", options = offers, modifier = Modifier.fillMaxWidth())

            Spacer(modifier = Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                StyledTextField(value = uiState.scoreMin, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, uiState.statusFilter, uiState.affiliate, uiState.affCode, uiState.offer, it, uiState.scoreMax, uiState.sortBy) }, label = "Score Min", modifier = Modifier.weight(1f))
                StyledTextField(value = uiState.scoreMax, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, uiState.statusFilter, uiState.affiliate, uiState.affCode, uiState.offer, uiState.scoreMin, it, uiState.sortBy) }, label = "Score Max", modifier = Modifier.weight(1f))
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            DropdownFilterField(value = uiState.sortBy, onValueChange = { onUpdateFilters(uiState.fromDate, uiState.toDate, uiState.clickId, uiState.statusFilter, uiState.affiliate, uiState.affCode, uiState.offer, uiState.scoreMin, uiState.scoreMax, it) }, label = "Sort By", options = sortOptions, modifier = Modifier.fillMaxWidth())
            
            Spacer(modifier = Modifier.height(24.dp))
            Button(
                onClick = onDismiss, 
                modifier = Modifier.fillMaxWidth().height(48.dp), 
                shape = androidx.compose.foundation.shape.RoundedCornerShape(10.dp)
            ) {
                Text("Apply Filters", fontSize = 14.sp, fontWeight = FontWeight.Bold)
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
    TextField(
        value = value,
        onValueChange = onValueChange,
        placeholder = { Text(label, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha=0.6f)) },
        textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp),
        modifier = modifier.fillMaxWidth().height(52.dp),
        shape = androidx.compose.foundation.shape.RoundedCornerShape(10.dp),
        singleLine = true,
        colors = TextFieldDefaults.colors(
            unfocusedIndicatorColor = Color.Transparent,
            focusedIndicatorColor = Color.Transparent,
            unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f),
            focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
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
        TextField(
            value = value.ifEmpty { label },
            onValueChange = {},
            readOnly = true,
            textStyle = androidx.compose.ui.text.TextStyle(
                fontSize = 13.sp, 
                color = if (value.isEmpty()) MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha=0.6f) else MaterialTheme.colorScheme.onSurface
            ),
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            modifier = Modifier.menuAnchor().fillMaxWidth().height(52.dp),
            shape = androidx.compose.foundation.shape.RoundedCornerShape(10.dp),
            singleLine = true,
            colors = TextFieldDefaults.colors(
                unfocusedIndicatorColor = Color.Transparent,
                focusedIndicatorColor = Color.Transparent,
                unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f),
                focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
            )
        )
        ExposedDropdownMenu(
            expanded = expanded,
            onDismissRequest = { expanded = false }
        ) {
            options.forEach { option ->
                DropdownMenuItem(
                    text = { Text(option, fontSize = 13.sp) },
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

fun getPresetDateRange(preset: String): Pair<String, String> {
    val format = SimpleDateFormat("yyyy-MM-dd", Locale.US)
    val cal = Calendar.getInstance()
    
    val to = format.format(cal.time)
    var from = to
    
    when (preset) {
        "Today" -> {
            // from = to
        }
        "Yesterday" -> {
            cal.add(Calendar.DAY_OF_YEAR, -1)
            from = format.format(cal.time)
            val toDate = format.format(cal.time)
            return Pair(from, toDate)
        }
        "Last 7 Days" -> {
            cal.add(Calendar.DAY_OF_YEAR, -7)
            from = format.format(cal.time)
        }
        "Last 15 Days" -> {
            cal.add(Calendar.DAY_OF_YEAR, -15)
            from = format.format(cal.time)
        }
        "This Month" -> {
            cal.set(Calendar.DAY_OF_MONTH, 1)
            from = format.format(cal.time)
        }
        "Last Month" -> {
            cal.add(Calendar.MONTH, -1)
            cal.set(Calendar.DAY_OF_MONTH, 1)
            from = format.format(cal.time)
            cal.set(Calendar.DAY_OF_MONTH, cal.getActualMaximum(Calendar.DAY_OF_MONTH))
            val toDate = format.format(cal.time)
            return Pair(from, toDate)
        }
        "Last 90 Days" -> {
            cal.add(Calendar.DAY_OF_YEAR, -90)
            from = format.format(cal.time)
        }
        "This Year" -> {
            cal.set(Calendar.DAY_OF_YEAR, 1)
            from = format.format(cal.time)
        }
        "Last Year" -> {
            cal.add(Calendar.YEAR, -1)
            cal.set(Calendar.DAY_OF_YEAR, 1)
            from = format.format(cal.time)
            cal.set(Calendar.DAY_OF_YEAR, cal.getActualMaximum(Calendar.DAY_OF_YEAR))
            val toDate = format.format(cal.time)
            return Pair(from, toDate)
        }
    }
    return Pair(from, to)
}
