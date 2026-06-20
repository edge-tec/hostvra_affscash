package net.affscash.android.ui.dashboard

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowDropDown
import androidx.compose.material.icons.outlined.Article
import androidx.compose.material.icons.outlined.ChatBubbleOutline
import androidx.compose.material.icons.outlined.Notifications
import androidx.compose.material.icons.outlined.WarningAmber
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.text.ExperimentalTextApi
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.withStyle
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.R
import net.affscash.android.data.model.DashboardAnalyticsStatsResponse
import net.affscash.android.data.model.DashboardAnalyticsTrend
import net.affscash.android.data.model.DashboardTrendChartResponse
import net.affscash.android.data.model.DashboardHourlyResponse
import com.patrykandpatrick.vico.compose.axis.horizontal.rememberBottomAxis
import com.patrykandpatrick.vico.compose.axis.vertical.rememberStartAxis
import com.patrykandpatrick.vico.compose.chart.Chart
import com.patrykandpatrick.vico.compose.chart.line.lineChart
import com.patrykandpatrick.vico.compose.chart.column.columnChart
import com.patrykandpatrick.vico.core.chart.line.LineChart
import com.patrykandpatrick.vico.core.entry.ChartEntryModel
import com.patrykandpatrick.vico.core.entry.FloatEntry
import com.patrykandpatrick.vico.core.entry.entryModelOf

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DashboardScreen(
    viewModel: DashboardViewModel = hiltViewModel(),
    onNavigateToInvoices: () -> Unit = {},
    onNavigateToFraudAlerts: () -> Unit = {},
    onNavigateToChat: () -> Unit = {},
    onNavigateToNews: () -> Unit = {},
    onNavigateToNotifications: () -> Unit = {}
) {
    val uiState by viewModel.uiState.collectAsState()
    val filterOption by viewModel.filterOption.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { 
                    Image(
                        painter = painterResource(id = R.drawable.logo),
                        contentDescription = "AffsCash Logo",
                        modifier = Modifier.height(32.dp)
                    )
                },
                actions = {
                    val data = (uiState as? DashboardState.Success)?.data
                    val balance = data?.stats?.balance ?: 0.0
                    val counts = data?.dashboardResponse?.headerCounts
                    
                    // Balance Pill
                    Surface(
                        color = Color(0xFFDCFCE7),
                        shape = RoundedCornerShape(8.dp),
                        border = BorderStroke(1.dp, Color(0xFF86EFAC)),
                        modifier = Modifier.padding(end = 8.dp),
                        onClick = onNavigateToInvoices
                    ) {
                        Row(
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                "$ $balance", 
                                color = Color(0xFF15803D),
                                fontWeight = FontWeight.Bold,
                                style = MaterialTheme.typography.labelLarge
                            )
                            Spacer(modifier = Modifier.width(4.dp))
                            Icon(
                                Icons.Default.ArrowDropDown,
                                contentDescription = "Dropdown",
                                tint = Color(0xFF15803D),
                                modifier = Modifier.size(16.dp)
                            )
                        }
                    }

                    val context = androidx.compose.ui.platform.LocalContext.current

                    // News Icon
                    HeaderIconWithBadge(
                        icon = Icons.Outlined.Article,
                        count = counts?.unreadNews ?: 0,
                        badgeColor = Color(0xFF4F46E5),
                        onClick = onNavigateToNews
                    )

                    // Notifications Icon
                    HeaderIconWithBadge(
                        icon = Icons.Outlined.Notifications,
                        count = counts?.unreadNotifs ?: 0,
                        badgeColor = Color(0xFFEF4444),
                        onClick = onNavigateToNotifications
                    )

                    // Fraud Alerts Icon
                    HeaderIconWithBadge(
                        icon = Icons.Outlined.WarningAmber,
                        count = counts?.unreadAlerts ?: 0,
                        badgeColor = Color(0xFFEF4444),
                        onClick = onNavigateToFraudAlerts
                    )

                    // Chat Icon
                    HeaderIconWithBadge(
                        icon = Icons.Outlined.ChatBubbleOutline,
                        count = counts?.unreadChats ?: 0,
                        badgeColor = Color(0xFFEF4444),
                        onClick = onNavigateToChat
                    )
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = Color.Transparent,
                    titleContentColor = MaterialTheme.colorScheme.onSurface
                ),
                modifier = Modifier.background(PremiumUI.GlassPurple)
            )
        }
    ) { paddingValues ->
        Box(modifier = Modifier.padding(paddingValues).fillMaxSize().background(PremiumUI.BackgroundGradient)) {
            when (uiState) {
                is DashboardState.Loading -> {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
                is DashboardState.Error -> {
                    val msg = (uiState as DashboardState.Error).message
                    Column(
                        modifier = Modifier.align(Alignment.Center),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(msg, color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadDashboardData() }) {
                            Text("Retry")
                        }
                    }
                }
                is DashboardState.Success -> {
                    val data = (uiState as DashboardState.Success).data
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        contentPadding = PaddingValues(16.dp)
                    ) {
                        item {
                            Text(
                                "Analytics Dashboard",
                                style = MaterialTheme.typography.headlineSmall.copy(brush = PremiumUI.PrimaryGradient),
                                fontWeight = FontWeight.ExtraBold
                            )
                            Spacer(modifier = Modifier.height(16.dp))
                        }
                        
                        item {
                            PeriodTabs(filterOption) { period ->
                                viewModel.setFilterOption(period)
                            }
                            Spacer(modifier = Modifier.height(16.dp))
                        }

                        item {
                            KpiGrid(data.stats)
                            Spacer(modifier = Modifier.height(24.dp))
                        }

                        item {
                            Text("Performance Trend", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(8.dp))
                            LineChartCard(data.trend)
                            Spacer(modifier = Modifier.height(24.dp))
                        }

                        item {
                            Row(modifier = Modifier.fillMaxWidth()) {
                                Column(modifier = Modifier.weight(1f)) {
                                    Text("Devices", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                                    Spacer(modifier = Modifier.height(8.dp))
                                    PieChartCard(data.devices.labels, data.devices.data)
                                }
                                Spacer(modifier = Modifier.width(16.dp))
                                Column(modifier = Modifier.weight(1f)) {
                                    Text("Browsers", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                                    Spacer(modifier = Modifier.height(8.dp))
                                    PieChartCard(data.browsers.labels, data.browsers.data)
                                }
                            }
                            Spacer(modifier = Modifier.height(24.dp))
                        }

                        item {
                            Text("Hourly Traffic", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(8.dp))
                            BarChartCard(data.hourly.labels, data.hourly.data.map { it.toFloat() })
                            Spacer(modifier = Modifier.height(24.dp))
                        }

                        item {
                            Text("Traffic Sources", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(8.dp))
                            BarChartCard(data.sources.labels, data.sources.data.map { it.toFloat() })
                            Spacer(modifier = Modifier.height(24.dp))
                        }

                        item {
                            Text("Top Offers Ranking", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(8.dp))
                            OffersTable(data.offers.rows)
                            Spacer(modifier = Modifier.height(24.dp))
                        }

                        item {
                            Text("Top Countries", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(8.dp))
                            CountriesTable(data.countries.rows)
                            Spacer(modifier = Modifier.height(24.dp))
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun PeriodTabs(selectedPeriod: String, onPeriodSelected: (String) -> Unit) {
    val periods = listOf("Today", "Yesterday", "7D", "30D", "This Month", "Last Month")
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .horizontalScroll(rememberScrollState()),
        horizontalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        periods.forEach { period ->
            val isSelected = selectedPeriod == period
            Surface(
                shape = RoundedCornerShape(16.dp),
                color = if (isSelected) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant,
                modifier = Modifier.clickable { onPeriodSelected(period) }
            ) {
                Text(
                    text = period,
                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp),
                    color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurfaceVariant,
                    fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal
                )
            }
        }
    }
}

@Composable
fun KpiGrid(stats: DashboardAnalyticsStatsResponse) {
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            KpiCard(title = "Total Clicks", value = "${stats.clicks}", trend = stats.trend.clicks, modifier = Modifier.weight(1f))
            KpiCard(title = "Unique Clicks", value = "${stats.unique}", trend = null, modifier = Modifier.weight(1f))
        }
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            KpiCard(title = "Conversions", value = "${stats.conversions}", trend = stats.trend.conv, modifier = Modifier.weight(1f))
            KpiCard(title = "Revenue", value = "$${(( stats.revenue )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", trend = stats.trend.revenue, modifier = Modifier.weight(1f))
        }
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            KpiCard(title = "Conv. Rate", value = "${stats.cr}%", trend = null, modifier = Modifier.weight(1f))
            KpiCard(title = "Fraud Conv %", value = "${stats.fraudConvPct}%", trend = stats.trend.fraudConvPct, isInverseTrend = true, modifier = Modifier.weight(1f))
        }
    }
}

@Composable
fun KpiCard(title: String, value: String, trend: Double?, isInverseTrend: Boolean = false, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(title, style = MaterialTheme.typography.labelMedium, color = Color.Gray)
            Spacer(modifier = Modifier.height(4.dp))
            Text(value, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            
            if (trend != null) {
                Spacer(modifier = Modifier.height(4.dp))
                val isPositive = trend >= 0
                val color = if ((isPositive && !isInverseTrend) || (!isPositive && isInverseTrend)) Color(0xFF16A34A) else Color(0xFFDC2626)
                val arrow = if (isPositive) "▲" else "▼"
                Text(
                    text = "$arrow ${Math.abs(trend)}% vs prev",
                    color = color,
                    style = MaterialTheme.typography.labelSmall,
                    fontWeight = FontWeight.Bold
                )
            }
        }
    }
}

@Composable
fun LineChartCard(trendData: DashboardTrendChartResponse) {
    if (trendData.labels.isEmpty() || trendData.clicksData.isEmpty()) {
        Card(modifier = Modifier.fillMaxWidth().height(250.dp)) {
            Box(contentAlignment = Alignment.Center, modifier = Modifier.fillMaxSize()) {
                Text("No data available", color = Color.Gray)
            }
        }
        return
    }

    val entries = trendData.clicksData.mapIndexed { index, value ->
        FloatEntry(x = index.toFloat(), y = value.toFloat())
    }
    val model = entryModelOf(entries)

    Card(
        modifier = Modifier.fillMaxWidth().height(250.dp),
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
            Chart(
                chart = lineChart(),
                model = model,
                startAxis = rememberStartAxis(),
                bottomAxis = rememberBottomAxis(
                    valueFormatter = { value, _ -> 
                        val index = value.toInt()
                        if (index >= 0 && index < trendData.labels.size) trendData.labels[index] else ""
                    }
                ),
                modifier = Modifier.padding(16.dp).fillMaxSize()
            )
        }
    }
}

@Composable
fun BarChartCard(labels: List<String>, data: List<Float>) {
    if (labels.isEmpty() || data.isEmpty()) {
        Card(modifier = Modifier.fillMaxWidth().height(200.dp)) {
            Box(contentAlignment = Alignment.Center, modifier = Modifier.fillMaxSize()) {
                Text("No data available", color = Color.Gray)
            }
        }
        return
    }

    val entries = data.mapIndexed { index, value ->
        FloatEntry(x = index.toFloat(), y = value)
    }
    val model = entryModelOf(entries)

    Card(
        modifier = Modifier.fillMaxWidth().height(250.dp),
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
            Chart(
                chart = columnChart(),
                model = model,
                startAxis = rememberStartAxis(),
                bottomAxis = rememberBottomAxis(
                    valueFormatter = { value, _ -> 
                        val index = value.toInt()
                        if (index >= 0 && index < labels.size) labels[index] else ""
                    }
                ),
                modifier = Modifier.padding(16.dp).fillMaxSize()
            )
        }
    }
}

@Composable
fun PieChartCard(labels: List<String>, data: List<Int>) {
    Card(
        modifier = Modifier.fillMaxWidth().height(200.dp),
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
        if (labels.isEmpty() || data.isEmpty() || data.sum() == 0) {
            Box(contentAlignment = Alignment.Center, modifier = Modifier.fillMaxSize()) {
                Text("No data available", color = Color.Gray)
            }
            return@Card
        }

        val colors = listOf(Color(0xFF4F46E5), Color(0xFF10B981), Color(0xFFF59E0B), Color(0xFFEF4444), Color(0xFF8B5CF6))
        val total = data.sum().toFloat()
        
        Column(
            modifier = Modifier.fillMaxSize().padding(16.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Box(modifier = Modifier.size(100.dp)) {
                Canvas(modifier = Modifier.fillMaxSize()) {
                    var startAngle = -90f
                    data.forEachIndexed { index, value ->
                        val sweepAngle = (value / total) * 360f
                        drawArc(
                            color = colors[index % colors.size],
                            startAngle = startAngle,
                            sweepAngle = sweepAngle,
                            useCenter = false,
                            style = Stroke(width = 30f, cap = StrokeCap.Butt),
                            size = Size(size.width, size.height)
                        )
                        startAngle += sweepAngle
                    }
                }
            }
            Spacer(modifier = Modifier.height(16.dp))
            // Legend
            Row(
                modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.Center
            ) {
                labels.take(4).forEachIndexed { index, label ->
                    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(horizontal = 4.dp)) {
                        Box(modifier = Modifier.size(8.dp).background(colors[index % colors.size], CircleShape))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(label, fontSize = 10.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
                    }
                }
            }
            }
        }
    }
}

@Composable
fun OffersTable(offers: List<net.affscash.android.data.model.DashboardOfferRow>) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
            Column(modifier = Modifier.padding(8.dp)) {
                Row(modifier = Modifier.fillMaxWidth().padding(bottom = 8.dp)) {
                    Text("OFFER", modifier = Modifier.weight(2f), fontWeight = FontWeight.Bold, fontSize = 12.sp)
                    Text("CLICKS", modifier = Modifier.weight(1f), fontWeight = FontWeight.Bold, fontSize = 12.sp, textAlign = TextAlign.End)
                    Text("CONV", modifier = Modifier.weight(1f), fontWeight = FontWeight.Bold, fontSize = 12.sp, textAlign = TextAlign.End)
                    Text("REVENUE", modifier = Modifier.weight(1f), fontWeight = FontWeight.Bold, fontSize = 12.sp, textAlign = TextAlign.End)
                }
                Divider()
                if (offers.isEmpty()) {
                    Text("No data available", modifier = Modifier.padding(16.dp), color = Color.Gray)
                } else {
                    offers.forEach { offer ->
                        Row(
                            modifier = Modifier.fillMaxWidth().padding(vertical = 12.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(offer.name, modifier = Modifier.weight(2f), fontSize = 12.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
                            Text("${offer.clicks}", modifier = Modifier.weight(1f), fontSize = 12.sp, textAlign = TextAlign.End)
                            Text("${offer.conv}", modifier = Modifier.weight(1f), fontSize = 12.sp, textAlign = TextAlign.End, color = Color(0xFF8B5CF6))
                            Text("$${(( offer.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", modifier = Modifier.weight(1f), fontSize = 12.sp, textAlign = TextAlign.End, color = Color(0xFF16A34A))
                        }
                        Divider(color = Color.LightGray.copy(alpha = 0.5f))
                    }
                }
            }
        }
    }
}

@Composable
fun CountriesTable(countries: List<net.affscash.android.data.model.DashboardCountryRow>) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
            Column(modifier = Modifier.padding(8.dp)) {
                Row(modifier = Modifier.fillMaxWidth().padding(bottom = 8.dp)) {
                    Text("COUNTRY", modifier = Modifier.weight(2f), fontWeight = FontWeight.Bold, fontSize = 12.sp)
                    Text("CLICKS", modifier = Modifier.weight(1f), fontWeight = FontWeight.Bold, fontSize = 12.sp, textAlign = TextAlign.End)
                    Text("UNIQUE", modifier = Modifier.weight(1f), fontWeight = FontWeight.Bold, fontSize = 12.sp, textAlign = TextAlign.End)
                    Text("CONV", modifier = Modifier.weight(1f), fontWeight = FontWeight.Bold, fontSize = 12.sp, textAlign = TextAlign.End)
                }
                Divider()
                if (countries.isEmpty()) {
                    Text("No data available", modifier = Modifier.padding(16.dp), color = Color.Gray)
                } else {
                    countries.forEach { row ->
                        Row(
                            modifier = Modifier.fillMaxWidth().padding(vertical = 12.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(row.country.ifEmpty { "Unknown" }, modifier = Modifier.weight(2f), fontSize = 12.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
                            Text("${row.clicks}", modifier = Modifier.weight(1f), fontSize = 12.sp, textAlign = TextAlign.End)
                            Text("${row.unique}", modifier = Modifier.weight(1f), fontSize = 12.sp, textAlign = TextAlign.End)
                            Text("${row.conv}", modifier = Modifier.weight(1f), fontSize = 12.sp, textAlign = TextAlign.End, color = Color(0xFF8B5CF6))
                        }
                        Divider(color = Color.LightGray.copy(alpha = 0.5f))
                    }
                }
            }
        }
    }
}

@Composable
fun HeaderIconWithBadge(
    icon: ImageVector,
    count: Int,
    badgeColor: Color,
    onClick: () -> Unit
) {
    Box(
        modifier = Modifier
            .padding(horizontal = 4.dp)
            .size(36.dp)
            .clickable(onClick = onClick),
        contentAlignment = Alignment.Center
    ) {
        Icon(
            imageVector = icon,
            contentDescription = "Header Icon",
            tint = MaterialTheme.colorScheme.onSurfaceVariant
        )
        if (count > 0) {
            Box(
                modifier = Modifier
                    .align(Alignment.TopEnd)
                    .offset(x = 6.dp, y = (-6).dp)
                    .defaultMinSize(minWidth = 18.dp, minHeight = 18.dp)
                    .background(badgeColor, RoundedCornerShape(9.dp))
                    .padding(horizontal = 4.dp, vertical = 2.dp),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = if (count > 99) "99+" else count.toString(),
                    color = Color.White,
                    fontSize = 10.sp,
                    fontWeight = FontWeight.Bold,
                    maxLines = 1
                )
            }
        }
    }
}
