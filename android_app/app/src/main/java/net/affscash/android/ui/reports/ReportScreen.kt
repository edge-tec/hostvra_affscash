package net.affscash.android.ui.reports

import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
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
import net.affscash.android.data.model.*
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
    val selectedCity by viewModel.selectedCity.collectAsState()
    val sub1Filter by viewModel.sub1Filter.collectAsState()

    val offers by viewModel.availableOffers.collectAsState()
    val countries by viewModel.availableCountries.collectAsState()
    val cities by viewModel.availableCities.collectAsState()

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
            Surface(
                modifier = Modifier.fillMaxWidth().statusBarsPadding(),
                color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.6f),
                shape = RoundedCornerShape(bottomStart = 24.dp, bottomEnd = 24.dp)
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(start = 16.dp, end = 8.dp, top = 8.dp, bottom = 8.dp)
                ) {
                    Text(
                        text = "Reports",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                    IconButton(onClick = onNavigateToFraudReport) {
                        Icon(Icons.Default.Warning, contentDescription = "Fraud Report", tint = MaterialTheme.colorScheme.onSurface)
                    }
                }
            }
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            var filtersExpanded by remember { mutableStateOf(false) }
            Card(
                modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 8.dp),
                elevation = CardDefaults.cardElevation(0.dp),
                colors = CardDefaults.cardColors(containerColor = Color.Transparent)
            ) {
                Column {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { filtersExpanded = !filtersExpanded }
                            .padding(vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text("Filters & Options", fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleSmall)
                        Icon(
                            if (filtersExpanded) Icons.Default.KeyboardArrowUp else Icons.Default.KeyboardArrowDown,
                            contentDescription = "Toggle Filters",
                            tint = MaterialTheme.colorScheme.primary
                        )
                    }

                    androidx.compose.animation.AnimatedVisibility(visible = filtersExpanded) {
                        Column(modifier = Modifier.padding(bottom = 16.dp, top = 8.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                            
                            Row(modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                var reportTypeExpanded by remember { mutableStateOf(false) }
                                val currentTabName = tabs.find { it.first == selectedTab }?.second ?: "Report Type"
                                Box(modifier = Modifier.weight(1f)) {
                                    Surface(
                                        onClick = { reportTypeExpanded = true },
                                        modifier = Modifier.fillMaxWidth().height(48.dp),
                                        shape = RoundedCornerShape(10.dp),
                                        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                    ) {
                                        Row(modifier = Modifier.padding(horizontal = 8.dp), verticalAlignment = Alignment.CenterVertically) {
                                            Icon(Icons.Default.List, contentDescription = null, modifier = Modifier.size(16.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Text(currentTabName, modifier = Modifier.weight(1f), maxLines = 1, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                            Icon(Icons.Default.ArrowDropDown, contentDescription = null, tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                        }
                                    }
                                    DropdownMenu(expanded = reportTypeExpanded, onDismissRequest = { reportTypeExpanded = false }) {
                                        tabs.forEach { tabInfo ->
                                            DropdownMenuItem(
                                                text = { Text(tabInfo.second, fontSize = 12.sp) },
                                                onClick = { viewModel.updateTab(tabInfo.first); reportTypeExpanded = false }
                                            )
                                        }
                                    }
                                }

                                var dateExpanded by remember { mutableStateOf(false) }
                                Box(modifier = Modifier.weight(1f)) {
                                    Surface(
                                        onClick = { dateExpanded = true },
                                        modifier = Modifier.fillMaxWidth().height(48.dp),
                                        shape = RoundedCornerShape(10.dp),
                                        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                    ) {
                                        Row(modifier = Modifier.padding(horizontal = 8.dp), verticalAlignment = Alignment.CenterVertically) {
                                            Icon(Icons.Default.DateRange, contentDescription = null, modifier = Modifier.size(16.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Text("$fromDate", modifier = Modifier.weight(1f), maxLines = 1, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                            Icon(Icons.Default.ArrowDropDown, contentDescription = null, tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                        }
                                    }
                                    DropdownMenu(expanded = dateExpanded, onDismissRequest = { dateExpanded = false }) {
                                        val dateRanges = listOf("Today", "Yesterday", "Last 7 Days", "This Month")
                                        dateRanges.forEach { range ->
                                            DropdownMenuItem(
                                                text = { Text(range, fontSize = 12.sp) },
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
                                                    dateExpanded = false
                                                }
                                            )
                                        }
                                    }
                                }
                            }

                            Row(modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                var offerExpanded by remember { mutableStateOf(false) }
                                Box(modifier = Modifier.weight(1f)) {
                                    Surface(
                                        onClick = { offerExpanded = true },
                                        modifier = Modifier.fillMaxWidth().height(48.dp),
                                        shape = RoundedCornerShape(10.dp),
                                        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                    ) {
                                        Row(modifier = Modifier.padding(horizontal = 8.dp), verticalAlignment = Alignment.CenterVertically) {
                                            Icon(Icons.Default.ShoppingCart, contentDescription = null, modifier = Modifier.size(16.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Text(offers.find { it.id == selectedOfferId }?.name ?: "All Offers", maxLines = 1, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.weight(1f))
                                            Icon(Icons.Default.ArrowDropDown, contentDescription = null, tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                        }
                                    }
                                    DropdownMenu(expanded = offerExpanded, onDismissRequest = { offerExpanded = false }) {
                                        DropdownMenuItem(text = { Text("All My Offers", fontSize = 12.sp) }, onClick = { viewModel.selectedOfferId.value = null; viewModel.loadReports(); offerExpanded = false })
                                        offers.forEach { o ->
                                            DropdownMenuItem(text = { Text(o.name, fontSize = 12.sp) }, onClick = { viewModel.selectedOfferId.value = o.id; viewModel.loadReports(); offerExpanded = false })
                                        }
                                    }
                                }

                                var countryExpanded by remember { mutableStateOf(false) }
                                Box(modifier = Modifier.weight(1f)) {
                                    Surface(
                                        onClick = { countryExpanded = true },
                                        modifier = Modifier.fillMaxWidth().height(48.dp),
                                        shape = RoundedCornerShape(10.dp),
                                        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                    ) {
                                        Row(modifier = Modifier.padding(horizontal = 8.dp), verticalAlignment = Alignment.CenterVertically) {
                                            Icon(Icons.Default.Place, contentDescription = null, modifier = Modifier.size(16.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Text(selectedCountry ?: "All Geo", maxLines = 1, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.weight(1f))
                                            Icon(Icons.Default.ArrowDropDown, contentDescription = null, tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                        }
                                    }
                                    DropdownMenu(expanded = countryExpanded, onDismissRequest = { countryExpanded = false }) {
                                        DropdownMenuItem(text = { Text("All Countries", fontSize = 12.sp) }, onClick = { viewModel.selectedCountry.value = null; viewModel.loadReports(); countryExpanded = false })
                                        countries.forEach { c ->
                                            DropdownMenuItem(text = { Text(c, fontSize = 12.sp) }, onClick = { viewModel.selectedCountry.value = c; viewModel.loadReports(); countryExpanded = false })
                                        }
                                    }
                                }
                            }
                            
                            Row(modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                var cityExpanded by remember { mutableStateOf(false) }
                                Box(modifier = Modifier.weight(1f)) {
                                    Surface(
                                        onClick = { cityExpanded = true },
                                        modifier = Modifier.fillMaxWidth().height(48.dp),
                                        shape = RoundedCornerShape(10.dp),
                                        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                    ) {
                                        Row(modifier = Modifier.padding(horizontal = 8.dp), verticalAlignment = Alignment.CenterVertically) {
                                            Icon(Icons.Default.Place, contentDescription = null, modifier = Modifier.size(16.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Text(selectedCity ?: "All Cities", maxLines = 1, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.weight(1f))
                                            Icon(Icons.Default.ArrowDropDown, contentDescription = null, tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                        }
                                    }
                                    DropdownMenu(expanded = cityExpanded, onDismissRequest = { cityExpanded = false }) {
                                        DropdownMenuItem(text = { Text("All Cities", fontSize = 12.sp) }, onClick = { viewModel.selectedCity.value = null; viewModel.loadReports(); cityExpanded = false })
                                        cities.forEach { c ->
                                            DropdownMenuItem(text = { Text(c, fontSize = 12.sp) }, onClick = { viewModel.selectedCity.value = c; viewModel.loadReports(); cityExpanded = false })
                                        }
                                    }
                                }

                                OutlinedTextField(
                                    value = sub1Filter,
                                    onValueChange = { viewModel.sub1Filter.value = it },
                                    modifier = Modifier.weight(1f).height(48.dp),
                                    placeholder = { Text("Aff Sub 1", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha=0.7f)) },
                                    singleLine = true,
                                    shape = RoundedCornerShape(10.dp),
                                    textStyle = LocalTextStyle.current.copy(fontSize = 12.sp),
                                    colors = TextFieldDefaults.colors(
                                        focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                                        unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                                        focusedIndicatorColor = Color.Transparent,
                                        unfocusedIndicatorColor = Color.Transparent
                                    )
                                )
                            }
                            
                            Button(
                                onClick = { viewModel.loadReports() }, 
                                modifier = Modifier.fillMaxWidth().height(48.dp),
                                shape = RoundedCornerShape(10.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary)
                            ) {
                                Icon(Icons.Default.Search, contentDescription = null, modifier = Modifier.size(18.dp))
                                Spacer(modifier = Modifier.width(4.dp))
                                Text("Apply Filters", fontWeight = FontWeight.Bold)
                            }
                        }
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
                            Spacer(modifier = Modifier.height(4.dp))
                            Button(onClick = { viewModel.loadReports() }) {
                                Text("Retry")
                            }
                        }
                    }
                    is ReportState.Success -> {
                        Column {
                            if (state.response.totals != null) {
                                val t = state.response.totals
                                LazyRow(
                                    contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp),
                                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    item { SummaryCard("CLICKS", "${t.clicks}", "Unique: ${t.uclicks}") }
                                    item { SummaryCard("CONVERSIONS", "${t.conv}", "") }
                                    item { SummaryCard("APPROVED", "${t.approved}", "", Color(0xFF10B981)) }
                                    item { SummaryCard("REJECTED", "${t.rejected}", "", Color(0xFFEF4444)) }
                                    item { SummaryCard("FRAUD", "${t.fraud}", "High risk", Color(0xFFEF4444)) }
                                    item { SummaryCard("PAYOUT", "$${"%.2f".format(t.payout)}", "Approved only", Color(0xFF10B981)) }
                                }
                            }

                            LazyColumn(
                                contentPadding = PaddingValues(bottom = 16.dp),
                                verticalArrangement = Arrangement.spacedBy(8.dp),
                                modifier = Modifier.fillMaxSize()
                            ) {
                                state.response.rows?.let { rows ->
                                    if (rows.isEmpty()) item { Text("No data found.", modifier = Modifier.padding(8.dp)) }
                                    items(rows) { row -> PerformanceRowItem(row) }
                                }
                                state.response.clicks?.let { clicks ->
                                    if (clicks.isEmpty()) item { Text("No clicks found.", modifier = Modifier.padding(8.dp)) }
                                    items(clicks) { c -> ClickRowItem(c) }
                                }
                                state.response.conversions?.let { convs ->
                                    if (convs.isEmpty()) item { Text("No conversions found.", modifier = Modifier.padding(8.dp)) }
                                    items(convs) { cv -> ConversionRowItem(cv) }
                                }
                                state.response.slClicks?.let { sls ->
                                    if (sls.isEmpty()) item { Text("No smartlink traffic found.", modifier = Modifier.padding(8.dp)) }
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
        modifier = Modifier.width(120.dp).height(80.dp),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.4f)),
        elevation = CardDefaults.cardElevation(0.dp)
    ) {
        Column(
            modifier = Modifier.padding(8.dp).fillMaxSize(),
            verticalArrangement = Arrangement.Center
        ) {
            Text(title, fontSize = 9.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(modifier = Modifier.height(2.dp))
            Text(
                text = value,
                fontSize = 18.sp,
                fontWeight = FontWeight.ExtraBold,
                color = valueColor,
                maxLines = 1,
                overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis
            )
            if (subtitle.isNotEmpty()) {
                Text(subtitle, fontSize = 9.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
        }
    }
}

@Composable
fun PerformanceRowItem(row: ReportRow) {
    Card(
        modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Column(modifier = Modifier.padding(8.dp)) {
            Text(row.label, fontSize = 13.sp, fontWeight = FontWeight.Bold)
            Spacer(modifier = Modifier.height(6.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Clicks: ${row.clicks} / Unique: ${row.uclicks}", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text("Conversions: ${row.conv}", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("Payout: $${"%.2f".format(row.payout)}", fontSize = 12.sp, color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                    Text("App: ${row.approved} | Rej: ${row.rejected}", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            }
        }
    }
}

@Composable
fun ClickRowItem(click: ClickRow) {
    Card(
        modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(8.dp)) {
            Text(click.offerName ?: "Custom URL", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
            Text("Click ID: ${click.clickId}", fontSize = 10.sp, color = Color.Gray)
            Spacer(modifier = Modifier.height(4.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(modifier = Modifier.weight(1f).padding(end = 8.dp)) {
                    Text("Sub1: ${click.sub1 ?: "-"}", fontSize = 12.sp, maxLines = 1, overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis)
                    Text("${click.country ?: "-"} | ${click.os ?: "-"} | ${click.browser ?: "-"}", fontSize = 12.sp, maxLines = 1, overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis)
                }
                Column(horizontalAlignment = Alignment.End) {
                    val statusColor = if (click.convStatus == "approved") Color(0xFF10B981) else Color(0xFFEF4444)
                    val statusBg = statusColor.copy(alpha = 0.15f)
                    Surface(
                        color = statusBg,
                        shape = androidx.compose.foundation.shape.RoundedCornerShape(8.dp),
                        modifier = Modifier.padding(bottom = 4.dp)
                    ) {
                        Text(
                            (click.convStatus ?: "no conv").uppercase(), 
                            fontSize = 10.sp, 
                            fontWeight = FontWeight.Bold, 
                            color = statusColor,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                    if (click.convPayout != null && click.convPayout > 0) {
                        Text("$${"%.2f".format(click.convPayout)}", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}

@Composable
fun ConversionRowItem(conv: ConversionRow) {
    var expandIds by remember { mutableStateOf(false) }
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = androidx.compose.foundation.shape.RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(8.dp)) {
            Text(conv.offerName ?: "Unknown", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
            
            Column(modifier = Modifier.clickable { expandIds = !expandIds }.fillMaxWidth()) {
                val convIdText = if (expandIds) conv.conversionId else if (conv.conversionId.length > 12) conv.conversionId.take(12) + "..." else conv.conversionId
                Text("Conv ID: $convIdText", fontSize = 10.sp, color = Color.Gray, fontFamily = androidx.compose.ui.text.font.FontFamily.Monospace)
                
                val clickIdText = if (expandIds) conv.clickId else if (conv.clickId.length > 12) conv.clickId.take(12) + "..." else conv.clickId
                Text("Click ID: $clickIdText", fontSize = 10.sp, color = Color.Gray, fontFamily = androidx.compose.ui.text.font.FontFamily.Monospace)
            }
            
            Spacer(modifier = Modifier.height(4.dp))
            
            // New Location / Device Info
            val country = conv.country?.takeIf { it.isNotBlank() && it.lowercase() != "unknown" } ?: "Unknown Country"
            val state = conv.region?.takeIf { it.isNotBlank() && it.lowercase() != "unknown" } ?: "Unknown State"
            val city = conv.city?.takeIf { it.isNotBlank() && it.lowercase() != "unknown" }
            val ipAddress = conv.ipAddress?.takeIf { it.isNotBlank() } ?: "Unknown IP"
            
            val locStr = listOfNotNull(country, state, city).joinToString(" | ")
            Text("$ipAddress | $locStr", fontSize = 11.sp, color = Color.Gray)
            
            val devInfo = listOfNotNull(
                conv.deviceType?.replaceFirstChar { it.uppercase() }?.takeIf { it.isNotBlank() },
                conv.os?.takeIf { it.isNotBlank() },
                conv.browser?.takeIf { it.isNotBlank() }
            ).joinToString(" | ")
            if (devInfo.isNotEmpty()) {
                Text(devInfo, fontSize = 11.sp, color = Color.Gray)
            }
            Spacer(modifier = Modifier.height(4.dp))
            
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(modifier = Modifier.weight(1f).padding(end = 8.dp)) {
                    Text("Date: ${conv.convertedAt}", fontSize = 12.sp)
                    if (!conv.sub1.isNullOrBlank()) {
                        Text("Sub1: ${conv.sub1}", fontSize = 12.sp, maxLines = 1, overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis)
                    }
                }
                Column(horizontalAlignment = Alignment.End) {
                    val statusColor = when(conv.status) {
                        "approved" -> Color(0xFF10B981)
                        "rejected" -> Color(0xFFEF4444)
                        else -> Color(0xFFF59E0B)
                    }
                    val statusBg = statusColor.copy(alpha = 0.15f)
                    
                    Surface(
                        color = statusBg,
                        shape = androidx.compose.foundation.shape.RoundedCornerShape(8.dp),
                        modifier = Modifier.padding(bottom = 4.dp)
                    ) {
                        Text(
                            conv.status.uppercase(), 
                            fontSize = 10.sp, 
                            fontWeight = FontWeight.Bold, 
                            color = statusColor,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                    Text("$${(( conv.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}

@Composable
fun SmartlinkRowItem(sl: SmartlinkClickRow) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = androidx.compose.foundation.shape.RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(8.dp)) {
            Text(sl.smartlinkName ?: "Unknown Smartlink", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
            Text("Routed to: ${sl.offerName ?: "Custom URL"}", fontSize = 12.sp)
            Spacer(modifier = Modifier.height(4.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(modifier = Modifier.weight(1f).padding(end = 8.dp)) {
                    Text("${sl.country ?: "-"} | Sub1: ${sl.sub1 ?: "-"}", fontSize = 12.sp, maxLines = 1, overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis)
                }
                Column(horizontalAlignment = Alignment.End) {
                    val statusText = sl.convStatus?.takeIf { it.isNotBlank() } ?: "No conv"
                    val statusColor = if (statusText.lowercase() == "approved") Color(0xFF10B981) else Color(0xFFEF4444)
                    val statusBg = statusColor.copy(alpha = 0.15f)
                    Surface(
                        color = statusBg,
                        shape = androidx.compose.foundation.shape.RoundedCornerShape(8.dp),
                        modifier = Modifier.padding(bottom = 4.dp)
                    ) {
                        Text(
                            statusText.uppercase(), 
                            fontSize = 10.sp, 
                            fontWeight = FontWeight.Bold, 
                            color = statusColor,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                    if (sl.convPayout != null && sl.convPayout > 0) {
                        Text("$${(( sl.convPayout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}
