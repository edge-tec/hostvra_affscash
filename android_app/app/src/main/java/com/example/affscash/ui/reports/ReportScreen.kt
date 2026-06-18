package com.example.affscash.ui.reports

import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.affscash.data.model.*
import java.text.SimpleDateFormat
import java.util.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ReportScreen(
    onNavigateToFraudReport: () -> Unit = {},
    viewModel: ReportViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val selectedTab by viewModel.selectedTab.collectAsState()
    val fromDate by viewModel.fromDate.collectAsState()
    val toDate by viewModel.toDate.collectAsState()
    val selectedOfferId by viewModel.selectedOfferId.collectAsState()
    val selectedCountry by viewModel.selectedCountry.collectAsState()
    val sub1Filter by viewModel.sub1Filter.collectAsState()

    val offers by viewModel.availableOffers.collectAsState()
    val countries by viewModel.availableCountries.collectAsState()

    val tabs = listOf(
        "day" to "By Day",
        "offer" to "By Offer",
        "country" to "By Country",
        "sub" to "By Aff Sub",
        "click" to "Click Log",
        "conversion" to "Conversions",
        "sl_report" to "SmartLink Report"
    )

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Reports") },
                actions = {
                    IconButton(onClick = onNavigateToFraudReport) {
                        Icon(Icons.Default.Warning, contentDescription = "Fraud Report")
                    }
                }
            )
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            ScrollableTabRow(
                selectedTabIndex = tabs.indexOfFirst { it.first == selectedTab }.coerceAtLeast(0),
                edgePadding = 8.dp
            ) {
                tabs.forEach { tabInfo ->
                    Tab(
                        selected = selectedTab == tabInfo.first,
                        onClick = { viewModel.updateTab(tabInfo.first) },
                        text = { Text(tabInfo.second) }
                    )
                }
            }

            // Filters Section
            Column(modifier = Modifier.padding(8.dp)) {
                // Date Chips
                Row(
                    modifier = Modifier.horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    val dateRanges = listOf("Today", "Yesterday", "Last 7 Days", "This Month")
                    dateRanges.forEach { range ->
                        FilterChip(
                            selected = false, // Simplified
                            onClick = {
                                val cal = Calendar.getInstance()
                                val sdf = SimpleDateFormat("yyyy-MM-dd", Locale.US)
                                val to = sdf.format(cal.time)
                                val from = when (range) {
                                    "Today" -> to
                                    "Yesterday" -> { cal.add(Calendar.DAY_OF_YEAR, -1); sdf.format(cal.time).also { cal.add(Calendar.DAY_OF_YEAR, 1) } }
                                    "Last 7 Days" -> { cal.add(Calendar.DAY_OF_YEAR, -7); sdf.format(cal.time).also { cal.add(Calendar.DAY_OF_YEAR, 7) } }
                                    "This Month" -> { cal.set(Calendar.DAY_OF_MONTH, 1); sdf.format(cal.time) }
                                    else -> to
                                }
                                if (range == "Yesterday") {
                                    viewModel.setDateRange(from, from)
                                } else {
                                    viewModel.setDateRange(from, to)
                                }
                            },
                            label = { Text(range) }
                        )
                    }
                    Text(text = "$fromDate to $toDate", modifier = Modifier.align(Alignment.CenterVertically), fontSize = 12.sp)
                }

                Spacer(modifier = Modifier.height(8.dp))

                // Dropdowns
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    // Offer Dropdown
                    var offerExpanded by remember { mutableStateOf(false) }
                    Box(modifier = Modifier.weight(1f)) {
                        OutlinedButton(
                            onClick = { offerExpanded = true },
                            modifier = Modifier.fillMaxWidth(),
                            contentPadding = PaddingValues(horizontal = 8.dp)
                        ) {
                            Text(offers.find { it.id == selectedOfferId }?.name ?: "All My Offers", maxLines = 1)
                            Icon(Icons.Default.ArrowDropDown, contentDescription = null)
                        }
                        DropdownMenu(expanded = offerExpanded, onDismissRequest = { offerExpanded = false }) {
                            DropdownMenuItem(text = { Text("All My Offers") }, onClick = { viewModel.selectedOfferId.value = null; viewModel.loadReports(); offerExpanded = false })
                            offers.forEach { o ->
                                DropdownMenuItem(text = { Text(o.name) }, onClick = { viewModel.selectedOfferId.value = o.id; viewModel.loadReports(); offerExpanded = false })
                            }
                        }
                    }

                    // Country Dropdown
                    var countryExpanded by remember { mutableStateOf(false) }
                    Box(modifier = Modifier.weight(1f)) {
                        OutlinedButton(
                            onClick = { countryExpanded = true },
                            modifier = Modifier.fillMaxWidth(),
                            contentPadding = PaddingValues(horizontal = 8.dp)
                        ) {
                            Text(selectedCountry ?: "All Countries", maxLines = 1)
                            Icon(Icons.Default.ArrowDropDown, contentDescription = null)
                        }
                        DropdownMenu(expanded = countryExpanded, onDismissRequest = { countryExpanded = false }) {
                            DropdownMenuItem(text = { Text("All Countries") }, onClick = { viewModel.selectedCountry.value = null; viewModel.loadReports(); countryExpanded = false })
                            countries.forEach { c ->
                                DropdownMenuItem(text = { Text(c) }, onClick = { viewModel.selectedCountry.value = c; viewModel.loadReports(); countryExpanded = false })
                            }
                        }
                    }
                }
                
                Spacer(modifier = Modifier.height(8.dp))
                
                // Sub1 Filter
                Row(modifier = Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                    OutlinedTextField(
                        value = sub1Filter,
                        onValueChange = { viewModel.sub1Filter.value = it },
                        modifier = Modifier.weight(1f).height(50.dp),
                        placeholder = { Text("Aff Sub 1") },
                        singleLine = true
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                    Button(onClick = { viewModel.loadReports() }, modifier = Modifier.height(50.dp)) {
                        Text("Apply")
                    }
                }
            }

            Box(modifier = Modifier.fillMaxSize().weight(1f)) {
                when (val state = uiState) {
                    is ReportState.Loading -> {
                        CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                    }
                    is ReportState.Error -> {
                        Column(
                            modifier = Modifier.align(Alignment.Center),
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            Text(state.message, color = MaterialTheme.colorScheme.error)
                            Spacer(modifier = Modifier.height(8.dp))
                            Button(onClick = { viewModel.loadReports() }) {
                                Text("Retry")
                            }
                        }
                    }
                    is ReportState.Success -> {
                        Column {
                            // Totals Cards (only for performance tabs)
                            if (state.response.totals != null) {
                                val t = state.response.totals
                                LazyVerticalGrid(
                                    columns = GridCells.Fixed(2),
                                    contentPadding = PaddingValues(8.dp),
                                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                                    verticalArrangement = Arrangement.spacedBy(8.dp),
                                    modifier = Modifier.heightIn(max = 280.dp)
                                ) {
                                    item { SummaryCard("CLICKS", "${t.clicks}", "Unique: ${t.uclicks}") }
                                    item { SummaryCard("CONVERSIONS", "${t.conv}", "") }
                                    item { SummaryCard("APPROVED", "${t.approved}", "", Color(0xFF10B981)) }
                                    item { SummaryCard("REJECTED", "${t.rejected}", "", Color(0xFFEF4444)) }
                                    item { SummaryCard("FRAUD", "${t.fraud}", "High risk", Color(0xFFEF4444)) }
                                    item { SummaryCard("PAYOUT", "$${"%.2f".format(t.payout)}", "Approved only", Color(0xFF10B981)) }
                                }
                            }

                            // Dynamic Lists
                            LazyColumn(
                                contentPadding = PaddingValues(8.dp),
                                verticalArrangement = Arrangement.spacedBy(8.dp),
                                modifier = Modifier.fillMaxSize()
                            ) {
                                state.response.rows?.let { rows ->
                                    if (rows.isEmpty()) item { Text("No data found.", modifier = Modifier.padding(16.dp)) }
                                    items(rows) { row -> PerformanceRowItem(row) }
                                }
                                state.response.clicks?.let { clicks ->
                                    if (clicks.isEmpty()) item { Text("No clicks found.", modifier = Modifier.padding(16.dp)) }
                                    items(clicks) { c -> ClickRowItem(c) }
                                }
                                state.response.conversions?.let { convs ->
                                    if (convs.isEmpty()) item { Text("No conversions found.", modifier = Modifier.padding(16.dp)) }
                                    items(convs) { cv -> ConversionRowItem(cv) }
                                }
                                state.response.slClicks?.let { sls ->
                                    if (sls.isEmpty()) item { Text("No smartlink traffic found.", modifier = Modifier.padding(16.dp)) }
                                    items(sls) { sl -> SmartlinkRowItem(sl) }
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
fun SummaryCard(title: String, value: String, subtitle: String, valueColor: Color = Color.Black) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(title, fontSize = 10.sp, fontWeight = FontWeight.Bold, color = Color.Gray)
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                text = value,
                fontSize = if (value.length > 7) 16.sp else 20.sp,
                fontWeight = FontWeight.Bold,
                color = valueColor,
                maxLines = 1,
                overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis
            )
            if (subtitle.isNotEmpty()) {
                Spacer(modifier = Modifier.height(2.dp))
                Text(subtitle, fontSize = 10.sp, color = Color.Gray)
            }
        }
    }
}

@Composable
fun PerformanceRowItem(row: ReportRow) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(row.label, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Spacer(modifier = Modifier.height(4.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Clicks: ${row.clicks}", fontSize = 12.sp)
                    Text("Conversions: ${row.conv}", fontSize = 12.sp)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("Payout: $${"%.2f".format(row.payout)}", fontSize = 12.sp, color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                    Text("App: ${row.approved} | Rej: ${row.rejected}", fontSize = 12.sp)
                }
            }
        }
    }
}

@Composable
fun ClickRowItem(click: ClickRow) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(click.offerName ?: "Custom URL", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Text("Click ID: ${click.clickId}", fontSize = 10.sp, color = Color.Gray)
            Spacer(modifier = Modifier.height(4.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Sub1: ${click.sub1 ?: "-"}", fontSize = 12.sp)
                    Text("${click.country ?: "-"} | ${click.os ?: "-"} | ${click.browser ?: "-"}", fontSize = 12.sp)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text((click.convStatus ?: "no conv").uppercase(), fontSize = 12.sp, color = if (click.convStatus == "approved") Color(0xFF10B981) else Color(0xFFEF4444), fontWeight = FontWeight.Bold)
                    if (click.convPayout != null && click.convPayout > 0) {
                        Text("$${"%.2f".format(click.convPayout)}", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}

@Composable
fun ConversionRowItem(conv: ConversionRow) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(conv.offerName ?: "Unknown", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Text("Conv ID: ${conv.conversionId}", fontSize = 10.sp, color = Color.Gray)
            Spacer(modifier = Modifier.height(4.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Date: ${conv.convertedAt}", fontSize = 12.sp)
                    Text("${conv.country ?: "-"} | ${conv.sub1 ?: "-"}", fontSize = 12.sp)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text(conv.status, fontSize = 12.sp, fontWeight = FontWeight.Bold, color = when(conv.status) {
                        "approved" -> Color(0xFF10B981)
                        "rejected" -> Color(0xFFEF4444)
                        else -> Color(0xFFF59E0B)
                    })
                    Text("$${conv.payout}", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}

@Composable
fun SmartlinkRowItem(sl: SmartlinkClickRow) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(sl.smartlinkName ?: "Unknown Smartlink", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Text("Routed to: ${sl.offerName ?: "Custom URL"}", fontSize = 12.sp)
            Spacer(modifier = Modifier.height(4.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("${sl.country ?: "-"} | Sub1: ${sl.sub1 ?: "-"}", fontSize = 12.sp)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text(sl.convStatus?.takeIf { it.isNotBlank() } ?: "No conv", fontSize = 12.sp)
                    if (sl.convPayout != null && sl.convPayout > 0) {
                        Text("$${sl.convPayout}", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}
