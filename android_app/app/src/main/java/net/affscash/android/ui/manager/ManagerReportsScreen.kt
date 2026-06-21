package net.affscash.android.ui.manager

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
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
        "year" to "By Year",
        "offer" to "By Offer",
        "country" to "By Country",
        "sub" to "By Aff Sub",
        "affiliate" to "Affiliate Report",
        "click" to "Click Log",
        "conversion" to "Conversion Log",
        "sl_report" to "SmartLink Report"
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
            Surface(
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.6f),
                shape = androidx.compose.foundation.shape.RoundedCornerShape(bottomStart = 24.dp, bottomEnd = 24.dp)
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(start = 8.dp, end = 24.dp, top = 28.dp, bottom = 10.dp)
                ) {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                    Text(
                        text = "Reports",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                }
            }
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            // Expandable Filters Section
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
                        Text("Filters & Options", fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                        Icon(
                            if (filtersExpanded) Icons.Default.KeyboardArrowUp else Icons.Default.KeyboardArrowDown,
                            contentDescription = "Toggle Filters",
                            tint = MaterialTheme.colorScheme.primary
                        )
                    }

                    androidx.compose.animation.AnimatedVisibility(visible = filtersExpanded) {
                        Column(modifier = Modifier.padding(bottom = 12.dp)) {
                            // Report Filter Dropdown (By Day, Week, Month, Year)
                            var reportTypeExpanded by remember { mutableStateOf(false) }
                            val currentTabName = tabs.find { it.first == uiState.currentTab }?.second ?: "By Day"
                            Box(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
                                Surface(
                                    onClick = { reportTypeExpanded = true },
                                    modifier = Modifier.fillMaxWidth().height(48.dp),
                                    shape = RoundedCornerShape(10.dp),
                                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                ) {
                                    Row(modifier = Modifier.padding(horizontal = 12.dp), verticalAlignment = Alignment.CenterVertically) {
                                        Text(currentTabName, modifier = Modifier.weight(1f), fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                        Icon(Icons.Default.ArrowDropDown, contentDescription = null, tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                    }
                                }
                                DropdownMenu(expanded = reportTypeExpanded, onDismissRequest = { reportTypeExpanded = false }) {
                                    tabs.forEach { tabInfo ->
                                        DropdownMenuItem(
                                            text = { Text(tabInfo.second, fontSize = 13.sp) },
                                            onClick = { viewModel.setTab(tabInfo.first); reportTypeExpanded = false }
                                        )
                                    }
                                }
                            }

                            // Removed Metrics and View Dropdowns based on user request
                            Column {
                                // Date Range Dropdown
                                var dateExpanded by remember { mutableStateOf(false) }
                                Box(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
                                    Surface(
                                        onClick = { dateExpanded = true },
                                        modifier = Modifier.fillMaxWidth().height(48.dp),
                                        shape = RoundedCornerShape(10.dp),
                                        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                    ) {
                                        Row(modifier = Modifier.padding(horizontal = 12.dp), verticalAlignment = Alignment.CenterVertically) {
                                            Text("${uiState.fromDate} to ${uiState.toDate}", modifier = Modifier.weight(1f), fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                            Icon(Icons.Default.ArrowDropDown, contentDescription = null, tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                        }
                                    }
                                    DropdownMenu(expanded = dateExpanded, onDismissRequest = { dateExpanded = false }) {
                                        val dateRanges = listOf("Today", "Yesterday", "Last 7 Days", "This Month", "Last 30 Days")
                                        dateRanges.forEach { range ->
                                            DropdownMenuItem(
                                                text = { Text(range, fontSize = 13.sp) },
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

                                // Dropdowns Row 1: Affiliate & Offer
                                Row(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                    // Affiliate Dropdown
                                    var affExpanded by remember { mutableStateOf(false) }
                                    Box(modifier = Modifier.weight(1f)) {
                                        Surface(
                                            onClick = { affExpanded = true },
                                            modifier = Modifier.fillMaxWidth().height(48.dp),
                                            shape = RoundedCornerShape(10.dp),
                                            color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                        ) {
                                            Row(modifier = Modifier.padding(horizontal = 12.dp), verticalAlignment = Alignment.CenterVertically) {
                                                Text(affiliates.find { it.id == uiState.selectedAffiliateId }?.name ?: "All Managed", maxLines = 1, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.weight(1f))
                                                Icon(Icons.Default.ArrowDropDown, contentDescription = null, tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                            }
                                        }
                                        DropdownMenu(expanded = affExpanded, onDismissRequest = { affExpanded = false }) {
                                            DropdownMenuItem(text = { Text("All Managed", fontSize = 13.sp) }, onClick = { viewModel.setFilter(uiState.selectedOfferId, 0, uiState.selectedCountry, uiState.sub1Query); affExpanded = false })
                                            affiliates.forEach { a ->
                                                DropdownMenuItem(text = { Text("${a.name} (#${a.id})", fontSize = 13.sp) }, onClick = { viewModel.setFilter(uiState.selectedOfferId, a.id, uiState.selectedCountry, uiState.sub1Query); affExpanded = false })
                                            }
                                        }
                                    }

                                    // Offer Dropdown
                                    var offerExpanded by remember { mutableStateOf(false) }
                                    Box(modifier = Modifier.weight(1f)) {
                                        Surface(
                                            onClick = { offerExpanded = true },
                                            modifier = Modifier.fillMaxWidth().height(48.dp),
                                            shape = RoundedCornerShape(10.dp),
                                            color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                        ) {
                                            Row(modifier = Modifier.padding(horizontal = 12.dp), verticalAlignment = Alignment.CenterVertically) {
                                                Text(offers.find { it.id == uiState.selectedOfferId }?.name ?: "All Offers", maxLines = 1, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.weight(1f))
                                                Icon(Icons.Default.ArrowDropDown, contentDescription = null, tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                            }
                                        }
                                        DropdownMenu(expanded = offerExpanded, onDismissRequest = { offerExpanded = false }) {
                                            DropdownMenuItem(text = { Text("All Offers", fontSize = 13.sp) }, onClick = { viewModel.setFilter(0, uiState.selectedAffiliateId, uiState.selectedCountry, uiState.sub1Query); offerExpanded = false })
                                            offers.forEach { o ->
                                                DropdownMenuItem(text = { Text(o.name, fontSize = 13.sp) }, onClick = { viewModel.setFilter(o.id, uiState.selectedAffiliateId, uiState.selectedCountry, uiState.sub1Query); offerExpanded = false })
                                            }
                                        }
                                    }
                                }

                                // Dropdowns Row 2: Country & Sub1
                                Row(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp), horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                                    // Country Dropdown
                                    var countryExpanded by remember { mutableStateOf(false) }
                                    Box(modifier = Modifier.weight(1f)) {
                                        Surface(
                                            onClick = { countryExpanded = true },
                                            modifier = Modifier.fillMaxWidth().height(48.dp),
                                            shape = RoundedCornerShape(10.dp),
                                            color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                                        ) {
                                            Row(modifier = Modifier.padding(horizontal = 12.dp), verticalAlignment = Alignment.CenterVertically) {
                                                Text(uiState.selectedCountry.ifEmpty { "All Countries" }, maxLines = 1, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.weight(1f))
                                                Icon(Icons.Default.ArrowDropDown, contentDescription = null, tint = MaterialTheme.colorScheme.onSurfaceVariant)
                                            }
                                        }
                                        DropdownMenu(expanded = countryExpanded, onDismissRequest = { countryExpanded = false }) {
                                            DropdownMenuItem(text = { Text("All Countries", fontSize = 13.sp) }, onClick = { viewModel.setFilter(uiState.selectedOfferId, uiState.selectedAffiliateId, "", uiState.sub1Query); countryExpanded = false })
                                            countries.forEach { c ->
                                                DropdownMenuItem(text = { Text(c, fontSize = 13.sp) }, onClick = { viewModel.setFilter(uiState.selectedOfferId, uiState.selectedAffiliateId, c, uiState.sub1Query); countryExpanded = false })
                                            }
                                        }
                                    }

                                    // Sub1 Filter
                                    @OptIn(ExperimentalMaterial3Api::class)
                                    OutlinedTextField(
                                        value = uiState.sub1Query,
                                        onValueChange = { viewModel.setFilter(uiState.selectedOfferId, uiState.selectedAffiliateId, uiState.selectedCountry, it) },
                                        modifier = Modifier.weight(1f).height(48.dp),
                                        placeholder = { Text("Aff Sub 1", fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha=0.7f)) },
                                        singleLine = true,
                                        shape = RoundedCornerShape(10.dp),
                                        textStyle = LocalTextStyle.current.copy(fontSize = 13.sp),
                                        colors = OutlinedTextFieldDefaults.colors(
                                            unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                                            focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                                            unfocusedBorderColor = Color.Transparent,
                                            focusedBorderColor = MaterialTheme.colorScheme.primary
                                        )
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
                            LazyRow(
                                contentPadding = PaddingValues(8.dp),
                                horizontalArrangement = Arrangement.spacedBy(8.dp),
                                modifier = Modifier.fillMaxWidth()
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
                                item { 
                                    Text(
                                        text = "Performance ${tabs.find { it.first == uiState.currentTab }?.second?.replace("Performance", "") ?: ""}".trim(), 
                                        style = MaterialTheme.typography.titleMedium, 
                                        fontWeight = FontWeight.Bold, 
                                        modifier = Modifier.padding(vertical = 8.dp)
                                    ) 
                                }
                                if (rows.isEmpty()) item { Text("No performance data found.", modifier = Modifier.padding(start = 8.dp, bottom = 16.dp)) }
                                items(rows) { row -> PerformanceRowItem(row) }
                            }
                            uiState.reportResponse!!.clicks?.let { clicks ->
                                item { Text("Click Log", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, modifier = Modifier.padding(top = 16.dp, bottom = 8.dp)) }
                                if (clicks.isEmpty()) item { Text("No clicks found.", modifier = Modifier.padding(start = 8.dp, bottom = 16.dp)) }
                                items(clicks) { c -> ClickRowItem(c) }
                            }
                            uiState.reportResponse!!.conversions?.let { convs ->
                                item { Text("Conversion Log", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, modifier = Modifier.padding(top = 16.dp, bottom = 8.dp)) }
                                if (convs.isEmpty()) item { Text("No conversions found.", modifier = Modifier.padding(start = 8.dp, bottom = 16.dp)) }
                                items(convs) { cv -> ConversionRowItem(cv) }
                            }
                            uiState.reportResponse!!.slClicks?.let { sls ->
                                item { Text("Smartlink Click Log", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, modifier = Modifier.padding(top = 16.dp, bottom = 8.dp)) }
                                if (sls.isEmpty()) item { Text("No smartlink traffic found.", modifier = Modifier.padding(start = 8.dp, bottom = 16.dp)) }
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
        modifier = Modifier.width(120.dp).height(80.dp),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.4f)),
        elevation = CardDefaults.cardElevation(0.dp)
    ) {
        Column(
            modifier = Modifier.padding(12.dp).fillMaxSize(),
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
        Column(modifier = Modifier.padding(12.dp)) {
            Text(row.label, fontSize = 14.sp, fontWeight = FontWeight.Bold)
            Spacer(modifier = Modifier.height(6.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Clicks: ${row.clicks} / Unique: ${row.uclicks}", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text("Conversions: ${row.conv}", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("Payout: $${"%.2f".format(row.payout)}", fontSize = 13.sp, color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                    Text("App: ${row.approved} | Rej: ${row.rejected}", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            }
        }
    }
}

@Composable
fun ClickRowItem(click: ClickRow) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = androidx.compose.foundation.shape.RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(click.offerName ?: "Custom URL", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Text("Click ID: ${click.clickId}", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(modifier = Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Sub1: ${click.sub1 ?: "-"}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text("${click.country ?: "-"} | ${click.os ?: "-"} | ${click.browser ?: "-"}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Surface(
                        color = if (click.convStatus == "approved") Color(0xFFD1FAE5) else Color(0xFFF3F4F6),
                        shape = androidx.compose.foundation.shape.RoundedCornerShape(8.dp)
                    ) {
                        Text(
                            click.convStatus?.uppercase() ?: "NO CONVERSION", 
                            style = MaterialTheme.typography.labelSmall, 
                            fontWeight = FontWeight.Bold,
                            color = if (click.convStatus == "approved") Color(0xFF059669) else Color(0xFF6B7280),
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                    if (click.convPayout != null && click.convPayout > 0) {
                        Spacer(modifier = Modifier.height(4.dp))
                        Text("$${"%.2f".format(click.convPayout)}", style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Bold, color = Color(0xFF10B981))
                    }
                }
            }
        }
    }
}

@Composable
fun ConversionRowItem(conversion: ConversionRow) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = androidx.compose.foundation.shape.RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(conversion.offerName ?: "Unknown Offer", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Text("Click ID: ${conversion.clickId}", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Text("Date: ${conversion.convertedAt}", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(modifier = Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Sub1: ${conversion.sub1 ?: "-"}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text("${conversion.country ?: "-"} | ${conversion.os ?: "-"}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                Column(horizontalAlignment = Alignment.End) {
                    val statusColor = if (conversion.status == "approved") Color(0xFF059669) else Color(0xFFDC2626)
                    val statusBg = if (conversion.status == "approved") Color(0xFFD1FAE5) else Color(0xFFFEE2E2)
                    Surface(
                        color = statusBg,
                        shape = androidx.compose.foundation.shape.RoundedCornerShape(8.dp)
                    ) {
                        Text(
                            conversion.status.uppercase(), 
                            style = MaterialTheme.typography.labelSmall, 
                            color = statusColor, 
                            fontWeight = FontWeight.Bold,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                    Spacer(modifier = Modifier.height(4.dp))
                    Text("$${"%.2f".format(conversion.payout)}", style = MaterialTheme.typography.titleMedium, color = Color(0xFF10B981), fontWeight = FontWeight.ExtraBold)
                }
            }
        }
    }
}

@Composable
fun SmartlinkRowItem(click: SmartlinkClickRow) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = androidx.compose.foundation.shape.RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(click.smartlinkName ?: "Unknown SmartLink", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Text("Offer: ${click.offerName ?: "Custom URL"}", style = MaterialTheme.typography.bodySmall)
            Text("Click ID: ${click.clickId}", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(modifier = Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Sub1: ${click.sub1 ?: "-"}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text("Country: ${click.country ?: "-"}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Surface(
                        color = if (click.convStatus == "approved") Color(0xFFD1FAE5) else Color(0xFFF3F4F6),
                        shape = androidx.compose.foundation.shape.RoundedCornerShape(8.dp)
                    ) {
                        Text(
                            click.convStatus?.uppercase() ?: "NO CONVERSION", 
                            style = MaterialTheme.typography.labelSmall, 
                            fontWeight = FontWeight.Bold,
                            color = if (click.convStatus == "approved") Color(0xFF059669) else Color(0xFF6B7280),
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                    if (click.convPayout != null && click.convPayout > 0) {
                        Spacer(modifier = Modifier.height(4.dp))
                        Text("$${"%.2f".format(click.convPayout)}", style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Bold, color = Color(0xFF10B981))
                    }
                }
            }
        }
    }
}
