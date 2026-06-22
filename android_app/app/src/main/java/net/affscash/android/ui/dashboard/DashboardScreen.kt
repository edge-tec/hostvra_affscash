package net.affscash.android.ui.dashboard

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
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
import net.affscash.android.ui.dashboard.PremiumUI
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
import net.affscash.android.data.local.NotificationBadgeManager

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
            Surface(
                modifier = Modifier.fillMaxWidth().background(PremiumUI.PastelHeader),
                color = Color.Transparent
            ) {
                Column {
                    Spacer(modifier = Modifier.windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top)))
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp, vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Image(
                            painter = painterResource(id = R.drawable.logo),
                            contentDescription = "AffsCash Logo",
                            modifier = Modifier.height(44.dp).padding(start = 4.dp)
                        )
                        Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                    val data = (uiState as? DashboardState.Success)?.data
                    val balance = data?.stats?.balance ?: 0.0
                    val counts = data?.dashboardResponse?.headerCounts
                    
                    // Balance Pill
                    Surface(
                        color = Color(0x59FFFFFF),
                        shape = PremiumUI.CardShape,
                        border = BorderStroke(1.dp, Color(0x4DFFFFFF)),
                        modifier = Modifier.padding(end = 4.dp),
                        onClick = onNavigateToInvoices
                    ) {
                        Row(
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                "$ $balance", 
                                color = Color(0xFF059669),
                                fontWeight = FontWeight.ExtraBold,
                                fontSize = 12.sp
                            )
                            Spacer(modifier = Modifier.width(2.dp))
                            Icon(
                                Icons.Default.ArrowDropDown,
                                contentDescription = "Dropdown",
                                tint = Color(0xFF059669),
                                modifier = Modifier.size(14.dp)
                            )
                        }
                    }

                    val badgeManager = remember { net.affscash.android.AffscashApp.getBadgeManager() }

                    // News Icon (no live sync yet)
                    HeaderIconWithBadge(
                        icon = Icons.Outlined.Article,
                        count = counts?.unreadNews ?: 0,
                        badgeColor = Color(0xFF4F46E5),
                        onClick = onNavigateToNews
                    )

                    // Notifications Icon
                    val liveNotifsCount = if (badgeManager != null) {
                        badgeManager.unreadNotifs.collectAsState().value
                    } else {
                        counts?.unreadNotifs ?: 0
                    }
                    HeaderIconWithBadge(
                        icon = Icons.Outlined.Notifications,
                        count = liveNotifsCount,
                        badgeColor = Color(0xFFEF4444),
                        onClick = onNavigateToNotifications
                    )

                    // Fraud Alerts Icon
                    val liveAlertsCount = if (badgeManager != null) {
                        badgeManager.unreadAlerts.collectAsState().value
                    } else {
                        counts?.unreadAlerts ?: 0
                    }
                    HeaderIconWithBadge(
                        icon = Icons.Outlined.WarningAmber,
                        count = liveAlertsCount,
                        badgeColor = Color(0xFFEF4444),
                        onClick = onNavigateToFraudAlerts
                    )

                    // Chat Icon
                    val liveChatsCount = if (badgeManager != null) {
                        badgeManager.unreadChats.collectAsState().value
                    } else {
                        counts?.unreadChats ?: 0
                    }
                    HeaderIconWithBadge(
                        icon = Icons.Outlined.ChatBubbleOutline,
                        count = liveChatsCount,
                        badgeColor = Color(0xFFEF4444),
                        onClick = onNavigateToChat
                    )
                }
                } // Close Column
            }
        }
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
                        Spacer(modifier = Modifier.height(4.dp))
                        Button(onClick = { viewModel.loadDashboardData() }) {
                            Text("Retry")
                        }
                    }
                }
                is DashboardState.Success -> {
                    val data = (uiState as DashboardState.Success).data
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp)
                    ) {
                        item {
                            Text(
                                "Analytics Dashboard",
                                style = MaterialTheme.typography.headlineSmall.copy(brush = PremiumUI.PrimaryGradient),
                                fontWeight = FontWeight.ExtraBold
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                        }
                        
                        item {
                            PeriodTabs(filterOption) { period ->
                                viewModel.setFilterOption(period)
                            }
                            Spacer(modifier = Modifier.height(4.dp))
                        }

                        item {
                            KpiGrid(data.stats)
                            Spacer(modifier = Modifier.height(6.dp))
                        }

                        item {
                            Text("Performance Trend", style = PremiumUI.TitleMedium)
                            Spacer(modifier = Modifier.height(2.dp))
                            LineChartCard(data.trend)
                            Spacer(modifier = Modifier.height(6.dp))
                        }

                        item {
                            Row(modifier = Modifier.fillMaxWidth()) {
                                Column(modifier = Modifier.weight(1f)) {
                                    Text("Devices", style = PremiumUI.TitleMedium)
                                    Spacer(modifier = Modifier.height(2.dp))
                                    PieChartCard(data.devices.labels, data.devices.data)
                                }
                                Spacer(modifier = Modifier.width(6.dp))
                                Column(modifier = Modifier.weight(1f)) {
                                    Text("Browsers", style = PremiumUI.TitleMedium)
                                    Spacer(modifier = Modifier.height(2.dp))
                                    PieChartCard(data.browsers.labels, data.browsers.data)
                                }
                            }
                            Spacer(modifier = Modifier.height(6.dp))
                        }

                        item {
                            Text("Hourly Traffic", style = PremiumUI.TitleMedium)
                            Spacer(modifier = Modifier.height(2.dp))
                            BarChartCard(data.hourly.labels, data.hourly.data.map { it.toFloat() })
                            Spacer(modifier = Modifier.height(6.dp))
                        }

                        item {
                            Text("Traffic Sources", style = PremiumUI.TitleMedium)
                            Spacer(modifier = Modifier.height(2.dp))
                            BarChartCard(data.sources.labels, data.sources.data.map { it.toFloat() })
                            Spacer(modifier = Modifier.height(6.dp))
                        }

                        item {
                            Text("Top Offers Ranking", style = PremiumUI.TitleMedium)
                            Spacer(modifier = Modifier.height(2.dp))
                            OffersTable(data.offers.rows)
                            Spacer(modifier = Modifier.height(6.dp))
                        }

                        item {
                            Text("Top Countries", style = PremiumUI.TitleMedium)
                            Spacer(modifier = Modifier.height(2.dp))
                            CountriesTable(data.countries.rows)
                            Spacer(modifier = Modifier.height(6.dp))
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun PeriodTabs(selectedPeriod: String, onPeriodSelected: (String) -> Unit) {
    val periods = listOf("Today", "Yesterday", "7D", "Last 15D", "30D", "This Month", "Last Month")
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .horizontalScroll(rememberScrollState()),
        horizontalArrangement = Arrangement.spacedBy(4.dp)
    ) {
        periods.forEach { period ->
            val isSelected = selectedPeriod == period
            Surface(
                shape = RoundedCornerShape(6.dp),
                color = if (isSelected) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                modifier = Modifier.clickable { onPeriodSelected(period) }
            ) {
                Text(
                    text = period,
                    modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                    fontSize = 12.sp,
                    color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurfaceVariant,
                    fontWeight = if (isSelected) FontWeight.SemiBold else FontWeight.Medium
                )
            }
        }
    }
}

@Composable
fun KpiGrid(stats: DashboardAnalyticsStatsResponse) {
    Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(6.dp)) {
            KpiCard(title = "Total Clicks", value = "${stats.clicks}", trend = stats.trend.clicks, modifier = Modifier.weight(1f))
            KpiCard(title = "Unique Clicks", value = "${stats.unique}", trend = null, modifier = Modifier.weight(1f))
        }
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(6.dp)) {
            KpiCard(title = "Conversions", value = "${stats.conversions}", trend = stats.trend.conv, modifier = Modifier.weight(1f))
            KpiCard(title = "Revenue", value = "$${(( stats.revenue )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", trend = stats.trend.revenue, modifier = Modifier.weight(1f))
        }
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(6.dp)) {
            KpiCard(title = "Conv. Rate", value = "${stats.cr}%", trend = null, modifier = Modifier.weight(1f))
            KpiCard(title = "Fraud Conv %", value = "${stats.fraudConvPct}%", trend = stats.trend.fraudConvPct, isInverseTrend = true, modifier = Modifier.weight(1f))
        }
    }
}

@Composable
fun KpiCard(title: String, value: String, trend: Double?, isInverseTrend: Boolean = false, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
            Column(modifier = Modifier.padding(10.dp)) {
                Text(title, style = PremiumUI.SecondaryText)
                Spacer(modifier = Modifier.height(2.dp))
                Text(value, style = PremiumUI.DataBold)
                
                if (trend != null) {
                    Spacer(modifier = Modifier.height(4.dp))
                    val isPositive = trend >= 0
                    val color = if ((isPositive && !isInverseTrend) || (!isPositive && isInverseTrend)) Color(0xFF10B981) else Color(0xFFEF4444)
                    val bgColor = if ((isPositive && !isInverseTrend) || (!isPositive && isInverseTrend)) Color(0xFFD1FAE5) else Color(0xFFFEE2E2)
                    val arrow = if (isPositive) "▲" else "▼"
                    
                    Surface(color = bgColor, shape = RoundedCornerShape(4.dp)) {
                        Text(
                            text = "$arrow ${Math.abs(trend)}%",
                            color = color,
                            fontSize = 10.sp,
                            fontWeight = FontWeight.Bold,
                            modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp)
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun LineChartCard(trendData: DashboardTrendChartResponse) {
    if (trendData.labels.isEmpty() || trendData.clicksData.isEmpty()) {
        Card(modifier = Modifier.fillMaxWidth().height(130.dp)) {
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
        modifier = Modifier.fillMaxWidth().height(130.dp),
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
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
                modifier = Modifier.padding(4.dp).fillMaxSize()
            )
        }
    }
}

@Composable
fun BarChartCard(labels: List<String>, data: List<Float>) {
    if (labels.isEmpty() || data.isEmpty()) {
        Card(modifier = Modifier.fillMaxWidth().height(130.dp)) {
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
        modifier = Modifier.fillMaxWidth().height(130.dp),
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
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
                modifier = Modifier.padding(4.dp).fillMaxSize()
            )
        }
    }
}

@Composable
fun PieChartCard(labels: List<String>, data: List<Int>) {
    Card(
        modifier = Modifier.fillMaxWidth().height(140.dp),
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
        if (labels.isEmpty() || data.isEmpty() || data.sum() == 0) {
            Box(contentAlignment = Alignment.Center, modifier = Modifier.fillMaxSize()) {
                Text("No data", color = Color.Gray)
            }
            return@Card
        }

        val colors = listOf(Color(0xFF4F46E5), Color(0xFF10B981), Color(0xFFF59E0B), Color(0xFFEF4444), Color(0xFF8B5CF6))
        val total = data.sum().toFloat()
        
        Column(
            modifier = Modifier.fillMaxSize().padding(4.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Box(modifier = Modifier.size(80.dp)) {
                Canvas(modifier = Modifier.fillMaxSize()) {
                    var startAngle = -90f
                    data.forEachIndexed { index, value ->
                        val sweepAngle = (value / total) * 360f
                        drawArc(
                            color = colors[index % colors.size],
                            startAngle = startAngle,
                            sweepAngle = sweepAngle,
                            useCenter = false,
                            style = Stroke(width = 20f, cap = StrokeCap.Butt),
                            size = Size(size.width, size.height)
                        )
                        startAngle += sweepAngle
                    }
                }
            }
            Spacer(modifier = Modifier.height(6.dp))
            Row(
                modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.Center
            ) {
                labels.take(4).forEachIndexed { index, label ->
                    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(horizontal = 4.dp)) {
                        Box(modifier = Modifier.size(6.dp).background(colors[index % colors.size], CircleShape))
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
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
            Column(modifier = Modifier.padding(8.dp)) {
                if (offers.isEmpty()) {
                    Text("No offers available", modifier = Modifier.padding(4.dp), color = Color.Gray, fontSize = 13.sp)
                } else {
                    offers.forEachIndexed { index, offer ->
                        Row(
                            modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text(offer.name, modifier = Modifier.weight(1f), style = PremiumUI.DataBold, maxLines = 1, overflow = TextOverflow.Ellipsis)
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Text("C: ${offer.clicks} | Cv: ${offer.conv}", style = PremiumUI.SecondaryText)
                                Spacer(modifier = Modifier.width(8.dp))
                                Text("$${(( offer.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontWeight = FontWeight.Bold, color = Color(0xFF10B981), fontSize = 13.sp)
                            }
                        }
                        if (index < offers.lastIndex) {
                            Divider(color = Color.LightGray.copy(alpha = 0.3f), thickness = 0.5.dp)
                        }
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
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
            Column(modifier = Modifier.padding(8.dp)) {
                if (countries.isEmpty()) {
                    Text("No countries data available", modifier = Modifier.padding(4.dp), color = Color.Gray, fontSize = 13.sp)
                } else {
                    countries.forEachIndexed { index, row ->
                        Row(
                            modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text(row.country.ifEmpty { "Unknown" }, modifier = Modifier.weight(1f), style = PremiumUI.DataBold, maxLines = 1, overflow = TextOverflow.Ellipsis)
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Text("C: ${row.clicks} | U: ${row.unique}", style = PremiumUI.SecondaryText)
                                Spacer(modifier = Modifier.width(8.dp))
                                Text("Cv: ${row.conv}", fontWeight = FontWeight.Bold, color = Color(0xFF4F46E5), fontSize = 13.sp)
                            }
                        }
                        if (index < countries.lastIndex) {
                            Divider(color = Color.LightGray.copy(alpha = 0.3f), thickness = 0.5.dp)
                        }
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
            .size(30.dp)
            .clickable(onClick = onClick),
        contentAlignment = Alignment.Center
    ) {
        Icon(
            imageVector = icon,
            contentDescription = "Header Icon",
            tint = Color.White
        )
        if (count > 0) {
            Box(
                modifier = Modifier
                    .align(Alignment.TopEnd)
                    .offset(x = 4.dp, y = (-4).dp)
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
