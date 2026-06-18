package net.affscash.android.ui.manager

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.ArrowDropDown
import androidx.compose.material.icons.filled.KeyboardArrowDown
import androidx.compose.material.icons.filled.KeyboardArrowUp
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.*
import java.text.SimpleDateFormat
import java.util.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerReportsScreen(
    onNavigateBack: () -> Unit = {},
    viewModel: ManagerReportsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    val tabs = listOf(
        "day" to "By Day",
        "week" to "By Week",
        "month" to "By Month",
        "year" to "By Year"
    )

    val metricOptions = listOf(
        "Clicks", "Conversions", "Approved", "Rejected", "Fraud Clicks", "Approved Payout"
    )

    val viewOptions = listOf(
        "Chart View", "Summary View", "Detailed Report"
    )

    val filters = uiState.filtersResponse
    val offers = filters?.offers ?: emptyList()
    val countries = filters?.countries ?: emptyList()
    val affiliates = filters?.affiliates ?: emptyList()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Reports") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            // Expandable Filters Section
            var filtersExpanded by remember { mutableStateOf(false) }
            Card(
                modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp, vertical = 4.dp),
                elevation = CardDefaults.cardElevation(2.dp),
                shape = androidx.compose.foundation.shape.RoundedCornerShape(8.dp)
            ) {
                Column {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { filtersExpanded = !filtersExpanded }
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text("Filters & Options", fontWeight = FontWeight.Bold)
                        Icon(
                            if (filtersExpanded) Icons.Default.KeyboardArrowUp else Icons.Default.KeyboardArrowDown,
                            contentDescription = "Toggle Filters"
                        )
                    }

                    androidx.compose.animation.AnimatedVisibility(visible = filtersExpanded) {
                        Column(modifier = Modifier.padding(bottom = 12.dp)) {
                            // Report Filter Dropdown (By Day, Week, Month, Year)
                            var reportTypeExpanded by remember { mutableStateOf(false) }
                            val currentTabName = tabs.find { it.first == uiState.currentTab }?.second ?: "By Day"
                            Box(modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp, vertical = 4.dp)) {
                                OutlinedButton(
                                    onClick = { reportTypeExpanded = true },
                                    modifier = Modifier.fillMaxWidth(),
                                    contentPadding = PaddingValues(horizontal = 16.dp)
                                ) {
                                    Text(currentTabName, modifier = Modifier.weight(1f))
                                    Icon(Icons.Default.ArrowDropDown, contentDescription = null)
                                }
                                DropdownMenu(expanded = reportTypeExpanded, onDismissRequest = { reportTypeExpanded = false }) {
                                    tabs.forEach { tabInfo ->
                                        DropdownMenuItem(
                                            text = { Text(tabInfo.second) },
                                            onClick = { viewModel.setTab(tabInfo.first); reportTypeExpanded = false }
                                        )
                                    }
                                }
                            }

                            // Removed Metrics and View Dropdowns based on user request
                            Column(modifier = Modifier.padding(horizontal = 8.dp)) {
                                // Date Range Dropdown
                                var dateExpanded by remember { mutableStateOf(false) }
                                Box(modifier = Modifier.fillMaxWidth()) {
                                    OutlinedButton(
                                        onClick = { dateExpanded = true },
                                        modifier = Modifier.fillMaxWidth(),
                                        contentPadding = PaddingValues(horizontal = 16.dp)
                                    ) {
                                        Text("${uiState.fromDate} to ${uiState.toDate}", modifier = Modifier.weight(1f))
                                        Icon(Icons.Default.ArrowDropDown, contentDescription = null)
                                    }
                                    DropdownMenu(expanded = dateExpanded, onDismissRequest = { dateExpanded = false }) {
                                        val dateRanges = listOf("Today", "Yesterday", "Last 7 Days", "This Month", "Last 30 Days")
                                        dateRanges.forEach { range ->
                                            DropdownMenuItem(
                                                text = { Text(range) },
                                                onClick = {
                                                    val cal = Calendar.getInstance()
                                                    val sdf = SimpleDateFormat("yyyy-MM-dd", Locale.US)
                                                    val to = sdf.format(cal.time)
                                                    val from = when (range) {
                                                        "Today" -> to
                                                        "Yesterday" -> { cal.add(Calendar.DAY_OF_YEAR, -1); sdf.format(cal.time).also { cal.add(Calendar.DAY_OF_YEAR, 1) } }
                                                        "Last 7 Days" -> { cal.add(Calendar.DAY_OF_YEAR, -7); sdf.format(cal.time).also { cal.add(Calendar.DAY_OF_YEAR, 7) } }
                                                        "This Month" -> { cal.set(Calendar.DAY_OF_MONTH, 1); sdf.format(cal.time) }
                                                        "Last 30 Days" -> { cal.add(Calendar.DAY_OF_YEAR, -30); sdf.format(cal.time).also { cal.add(Calendar.DAY_OF_YEAR, 30) } }
                                                        else -> to
                                                    }
                                                    if (range == "Yesterday") {
                                                        viewModel.setDateRange(from, from)
                                                    } else {
                                                        viewModel.setDateRange(from, to)
                                                    }
                                                    dateExpanded = false
                                                }
                                            )
                                        }
                                    }
                                }

                                Spacer(modifier = Modifier.height(8.dp))

                                // Dropdowns Row 1: Affiliate & Offer
                                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                    // Affiliate Dropdown
                                    var affExpanded by remember { mutableStateOf(false) }
                                    Box(modifier = Modifier.weight(1f)) {
                                        OutlinedButton(
                                            onClick = { affExpanded = true },
                                            modifier = Modifier.fillMaxWidth(),
                                            contentPadding = PaddingValues(horizontal = 8.dp)
                                        ) {
                                            Text(affiliates.find { it.id == uiState.selectedAffiliateId }?.name ?: "All Managed", maxLines = 1, modifier = Modifier.weight(1f))
                                            Icon(Icons.Default.ArrowDropDown, contentDescription = null)
                                        }
                                        DropdownMenu(expanded = affExpanded, onDismissRequest = { affExpanded = false }) {
                                            DropdownMenuItem(text = { Text("All Managed") }, onClick = { viewModel.setFilter(uiState.selectedOfferId, 0, uiState.selectedCountry, uiState.sub1Query); affExpanded = false })
                                            affiliates.forEach { a ->
                                                DropdownMenuItem(text = { Text("${a.name} (#${a.id})") }, onClick = { viewModel.setFilter(uiState.selectedOfferId, a.id, uiState.selectedCountry, uiState.sub1Query); affExpanded = false })
                                            }
                                        }
                                    }

                                    // Offer Dropdown
                                    var offerExpanded by remember { mutableStateOf(false) }
                                    Box(modifier = Modifier.weight(1f)) {
                                        OutlinedButton(
                                            onClick = { offerExpanded = true },
                                            modifier = Modifier.fillMaxWidth(),
                                            contentPadding = PaddingValues(horizontal = 8.dp)
                                        ) {
                                            Text(offers.find { it.id == uiState.selectedOfferId }?.name ?: "All Offers", maxLines = 1, modifier = Modifier.weight(1f))
                                            Icon(Icons.Default.ArrowDropDown, contentDescription = null)
                                        }
                                        DropdownMenu(expanded = offerExpanded, onDismissRequest = { offerExpanded = false }) {
                                            DropdownMenuItem(text = { Text("All Offers") }, onClick = { viewModel.setFilter(0, uiState.selectedAffiliateId, uiState.selectedCountry, uiState.sub1Query); offerExpanded = false })
                                            offers.forEach { o ->
                                                DropdownMenuItem(text = { Text(o.name) }, onClick = { viewModel.setFilter(o.id, uiState.selectedAffiliateId, uiState.selectedCountry, uiState.sub1Query); offerExpanded = false })
                                            }
                                        }
                                    }
                                }

                                Spacer(modifier = Modifier.height(8.dp))

                                // Dropdowns Row 2: Country & Sub1
                                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                                    // Country Dropdown
                                    var countryExpanded by remember { mutableStateOf(false) }
                                    Box(modifier = Modifier.weight(1f)) {
                                        OutlinedButton(
                                            onClick = { countryExpanded = true },
                                            modifier = Modifier.fillMaxWidth(),
                                            contentPadding = PaddingValues(horizontal = 8.dp)
                                        ) {
                                            Text(uiState.selectedCountry.ifEmpty { "All Countries" }, maxLines = 1, modifier = Modifier.weight(1f))
                                            Icon(Icons.Default.ArrowDropDown, contentDescription = null)
                                        }
                                        DropdownMenu(expanded = countryExpanded, onDismissRequest = { countryExpanded = false }) {
                                            DropdownMenuItem(text = { Text("All Countries") }, onClick = { viewModel.setFilter(uiState.selectedOfferId, uiState.selectedAffiliateId, "", uiState.sub1Query); countryExpanded = false })
                                            countries.forEach { c ->
                                                DropdownMenuItem(text = { Text(c) }, onClick = { viewModel.setFilter(uiState.selectedOfferId, uiState.selectedAffiliateId, c, uiState.sub1Query); countryExpanded = false })
                                            }
                                        }
                                    }

                                    // Sub1 Filter
                                    OutlinedTextField(
                                        value = uiState.sub1Query,
                                        onValueChange = { viewModel.setFilter(uiState.selectedOfferId, uiState.selectedAffiliateId, uiState.selectedCountry, it) },
                                        modifier = Modifier.weight(1f).height(50.dp),
                                        placeholder = { Text("Aff Sub 1") },
                                        singleLine = true
                                    )
                                }
                            }
                        }
                    }
                }
            }

            Box(modifier = Modifier.fillMaxSize().weight(1f)) {
                if (uiState.isLoadingReport || uiState.isLoadingFilters) {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                } else if (uiState.reportError != null) {
                    Column(
                        modifier = Modifier.align(Alignment.Center),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(uiState.reportError!!, color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadReport() }) {
                            Text("Retry")
                        }
                    }
                } else if (uiState.reportResponse != null) {
                    Column(modifier = Modifier.fillMaxSize()) {
                        // Totals Cards
                        if (uiState.reportResponse!!.totals != null) {
                            val t = uiState.reportResponse!!.totals!!
                            LazyVerticalGrid(
                                columns = GridCells.Fixed(2),
                                contentPadding = PaddingValues(8.dp),
                                horizontalArrangement = Arrangement.spacedBy(8.dp),
                                verticalArrangement = Arrangement.spacedBy(8.dp),
                                modifier = Modifier.fillMaxWidth().height(250.dp) // Fixed height for totals
                            ) {
                                item { SummaryCard("CLICKS", "${t.clicks}", "Unique: ${t.uclicks}") }
                                item { SummaryCard("CONVERSIONS", "${t.conv}", "") }
                                item { SummaryCard("APPROVED", "${t.approved}", "", Color(0xFF10B981)) }
                                item { SummaryCard("REJECTED", "${t.rejected}", "", Color(0xFFEF4444)) }
                                item { SummaryCard("FRAUD CLICKS", "${t.fraud}", "High risk", Color(0xFFEF4444)) }
                                item { SummaryCard("APPROVED PAYOUT", "$${"%.2f".format(t.payout)}", "Approved only", Color(0xFF10B981)) }
                            }
                        }

                        // Detailed Report Lists
                        LazyColumn(
                            contentPadding = PaddingValues(8.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp),
                            modifier = Modifier.fillMaxWidth().weight(1f)
                        ) {
                            uiState.reportResponse!!.rows?.let { rows ->
                                if (rows.isEmpty()) item { Text("No data found.", modifier = Modifier.padding(16.dp)) }
                                items(rows) { row -> PerformanceRowItem(row) }
                            }
                            uiState.reportResponse!!.clicks?.let { clicks ->
                                if (clicks.isEmpty()) item { Text("No clicks found.", modifier = Modifier.padding(16.dp)) }
                                items(clicks) { c -> ClickRowItem(c) }
                            }
                            uiState.reportResponse!!.conversions?.let { convs ->
                                if (convs.isEmpty()) item { Text("No conversions found.", modifier = Modifier.padding(16.dp)) }
                                items(convs) { cv -> ConversionRowItem(cv) }
                            }
                            uiState.reportResponse!!.slClicks?.let { sls ->
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
                    Text("Clicks: ${row.clicks} / Unique: ${row.uclicks}", fontSize = 12.sp)
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
                    Text(click.convStatus ?: "no conversion", fontSize = 12.sp, color = if (click.convStatus == "approved") Color(0xFF10B981) else Color.Gray)
                    if (click.convPayout != null && click.convPayout > 0) {
                        Text("$${"%.2f".format(click.convPayout)}", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}

@Composable
fun ConversionRowItem(conversion: ConversionRow) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(conversion.offerName ?: "Unknown Offer", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Text("Click ID: ${conversion.clickId}", fontSize = 10.sp, color = Color.Gray)
            Text("Date: ${conversion.convertedAt}", fontSize = 10.sp, color = Color.Gray)
            Spacer(modifier = Modifier.height(4.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Sub1: ${conversion.sub1 ?: "-"}", fontSize = 12.sp)
                    Text("${conversion.country ?: "-"} | ${conversion.os ?: "-"}", fontSize = 12.sp)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text(conversion.status.uppercase(), fontSize = 12.sp, color = if (conversion.status == "approved") Color(0xFF10B981) else Color(0xFFEF4444), fontWeight = FontWeight.Bold)
                    Text("$${"%.2f".format(conversion.payout)}", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}

@Composable
fun SmartlinkRowItem(click: SmartlinkClickRow) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(click.smartlinkName ?: "Unknown SmartLink", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Text("Offer: ${click.offerName ?: "Custom URL"}", fontSize = 12.sp)
            Text("Click ID: ${click.clickId}", fontSize = 10.sp, color = Color.Gray)
            Spacer(modifier = Modifier.height(4.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Sub1: ${click.sub1 ?: "-"}", fontSize = 12.sp)
                    Text("Country: ${click.country ?: "-"}", fontSize = 12.sp)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text(click.convStatus ?: "no conversion", fontSize = 12.sp, color = if (click.convStatus == "approved") Color(0xFF10B981) else Color.Gray)
                    if (click.convPayout != null && click.convPayout > 0) {
                        Text("$${"%.2f".format(click.convPayout)}", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}
