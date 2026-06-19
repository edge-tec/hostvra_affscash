package net.affscash.android.ui.manager

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowDropDown
import androidx.compose.material.icons.outlined.ChatBubbleOutline
import androidx.compose.material.icons.outlined.Notifications
import androidx.compose.material.icons.outlined.WarningAmber
import androidx.compose.material.icons.outlined.Analytics
import androidx.compose.material.icons.outlined.People
import androidx.compose.material.icons.outlined.Security
import androidx.compose.material.icons.outlined.MonetizationOn
import androidx.compose.material.icons.outlined.AdsClick
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.geometry.Size
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.R
import net.affscash.android.ui.dashboard.ManagerDashboardViewModel
import net.affscash.android.ui.dashboard.HeaderIconWithBadge
import com.patrykandpatrick.vico.compose.axis.horizontal.rememberBottomAxis
import com.patrykandpatrick.vico.compose.axis.vertical.rememberStartAxis
import com.patrykandpatrick.vico.compose.chart.Chart
import com.patrykandpatrick.vico.compose.chart.line.lineChart
import com.patrykandpatrick.vico.compose.chart.column.columnChart
import com.patrykandpatrick.vico.core.chart.line.LineChart
import com.patrykandpatrick.vico.core.entry.entryModelOf
import com.patrykandpatrick.vico.core.entry.FloatEntry
import com.patrykandpatrick.vico.core.entry.ChartEntryModel
import java.text.SimpleDateFormat
import java.util.Locale
import java.util.Date

import kotlin.math.abs

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerDashboardScreen(
    viewModel: ManagerDashboardViewModel = hiltViewModel(),
    onNavigateToInvoices: () -> Unit = {},
    onNavigateToFraudAlerts: () -> Unit = {},
    onNavigateToChat: () -> Unit = {},
    onNavigateToNotifications: () -> Unit = {}
) {
    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            Surface(
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.6f),
                shape = RoundedCornerShape(bottomStart = 24.dp, bottomEnd = 24.dp)
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(start = 24.dp, end = 24.dp, top = 24.dp, bottom = 16.dp)
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Image(
                            painter = painterResource(id = R.drawable.logo),
                            contentDescription = "AffsCash Logo",
                            modifier = Modifier.height(36.dp)
                        )
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            val stats = uiState.stats
                            val balance = stats?.commissionBalance ?: 0.0
                            val counts = stats?.headerCounts
                            
                            // Balance Pill
                            Surface(
                                color = Color(0xFF10B981).copy(alpha = 0.15f),
                                shape = RoundedCornerShape(12.dp),
                                modifier = Modifier.padding(end = 8.dp),
                                onClick = onNavigateToInvoices
                            ) {
                                Row(
                                    modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Icon(
                                        Icons.Outlined.MonetizationOn,
                                        contentDescription = null,
                                        tint = Color(0xFF10B981),
                                        modifier = Modifier.size(16.dp)
                                    )
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text(
                                        "$$balance", 
                                        color = Color(0xFF10B981),
                                        fontWeight = FontWeight.ExtraBold,
                                        style = MaterialTheme.typography.labelLarge
                                    )
                                }
                            }

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
                        }
                    }
                }
            }
        }
    ) { paddingValues ->
        Box(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            LazyColumn(
                modifier = Modifier.fillMaxSize(),
                contentPadding = PaddingValues(16.dp)
            ) {
                item {
                    Text(
                        text = "Analytics Dashboard",
                        style = MaterialTheme.typography.headlineMedium,
                        fontWeight = FontWeight.ExtraBold
                    )
                    Spacer(modifier = Modifier.height(16.dp))
                }

                item {
                    PeriodTabs(uiState.selectedPeriod) { period ->
                        viewModel.setPeriod(period)
                    }
                    Spacer(modifier = Modifier.height(16.dp))
                }

                if (uiState.isLoadingStats) {
                    item {
                        CircularProgressIndicator(modifier = Modifier.padding(16.dp))
                    }
                } else if (uiState.error != null) {
                    item {
                        Text("Error: ${uiState.error}", color = MaterialTheme.colorScheme.error)
                        Button(onClick = { viewModel.setPeriod(uiState.selectedPeriod) }) {
                            Text("Retry")
                        }
                    }
                } else {
                    uiState.stats?.let { stats ->
                        item {
                            KpiGrid(stats)
                            Spacer(modifier = Modifier.height(24.dp))
                        }
                    }
                }

                item {
                    Text(
                        text = "Performance Trend",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold
                    )
                    Spacer(modifier = Modifier.height(16.dp))
                }

                item {
                    if (uiState.isLoadingTrend) {
                        CircularProgressIndicator()
                    } else if (uiState.trend != null) {
                        val trendData = uiState.trend!!
                        if (trendData.labels.isNotEmpty() && trendData.clicksData.isNotEmpty()) {
                            val entries = trendData.clicksData.mapIndexed { index, value ->
                                FloatEntry(x = index.toFloat(), y = value.toFloat())
                            }
                            val model = entryModelOf(entries)
                            Card(
                                modifier = Modifier.fillMaxWidth().height(300.dp),
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                            ) {
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
                                    modifier = Modifier.fillMaxSize().padding(16.dp)
                                )
                            }
                        } else {
                            Text("No trend data available for this period.")
                        }
                    }
                }

                item {
                    if (uiState.isLoadingExtra) {
                        CircularProgressIndicator(modifier = Modifier.padding(vertical = 16.dp))
                    } else if (uiState.extraData != null) {
                        val extra = uiState.extraData!!
                        
                        // Hourly Traffic
                        if (extra.hourly?.labels?.isNotEmpty() == true) {
                            Text("Hourly Traffic", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(8.dp))
                            BarChartCard(extra.hourly.labels, extra.hourly.data.map { it.toFloat() })
                            Spacer(modifier = Modifier.height(24.dp))
                        }

                        // Conversion Status
                        if (extra.convStatus?.labels?.isNotEmpty() == true) {
                            Text("Conversion Status", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(8.dp))
                            val customColors = extra.convStatus.colors.map { 
                                try { Color(android.graphics.Color.parseColor(it)) } catch(e: Exception) { Color.Gray } 
                            }
                            PieChartCard(extra.convStatus.labels, extra.convStatus.data, customColors)
                            Spacer(modifier = Modifier.height(24.dp))
                        }

                        // Top Countries
                        if (extra.countries.isNotEmpty()) {
                            Text("Top Countries", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(8.dp))
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                            ) {
                                Column(modifier = Modifier.padding(16.dp)) {
                                    extra.countries.take(5).forEach { c ->
                                        Row(
                                            modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Text(c.country.ifBlank { "Unknown" }, fontWeight = FontWeight.Medium)
                                            Text("C: ${c.clicks} | Cv: ${c.conv}", color = Color.Gray)
                                        }
                                        HorizontalDivider(color = MaterialTheme.colorScheme.surfaceVariant)
                                    }
                                }
                            }
                            Spacer(modifier = Modifier.height(24.dp))
                        }

                        // Breakdowns (Device, Browser, OS)
                        Text("Traffic Breakdown", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                        Spacer(modifier = Modifier.height(8.dp))
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            if (extra.devices?.labels?.isNotEmpty() == true) {
                                Column(modifier = Modifier.weight(1f)) {
                                    Text("Devices", style = MaterialTheme.typography.labelLarge)
                                    PieChartCard(extra.devices.labels, extra.devices.data)
                                }
                            }
                            if (extra.browsers?.labels?.isNotEmpty() == true) {
                                Column(modifier = Modifier.weight(1f)) {
                                    Text("Browsers", style = MaterialTheme.typography.labelLarge)
                                    PieChartCard(extra.browsers.labels, extra.browsers.data)
                                }
                            }
                        }
                        Spacer(modifier = Modifier.height(8.dp))
                        Row(modifier = Modifier.fillMaxWidth()) {
                            if (extra.os?.labels?.isNotEmpty() == true) {
                                Column(modifier = Modifier.fillMaxWidth(0.5f)) {
                                    Text("OS", style = MaterialTheme.typography.labelLarge)
                                    PieChartCard(extra.os.labels, extra.os.data)
                                }
                            }
                        }
                        Spacer(modifier = Modifier.height(24.dp))
                        
                        // Top Offers & Top Affiliates
                        if (extra.offers.isNotEmpty()) {
                            Text("Top Offers", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(8.dp))
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                            ) {
                                Column(modifier = Modifier.padding(16.dp)) {
                                    extra.offers.take(5).forEach { o ->
                                        Row(
                                            modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Text(o.name.ifBlank { "Offer #${o.id}" }, fontWeight = FontWeight.Medium, maxLines = 1, overflow = TextOverflow.Ellipsis, modifier = Modifier.weight(1f))
                                            Text("C: ${o.clicks} | Cv: ${o.conv}", color = Color.Gray, modifier = Modifier.padding(start = 8.dp))
                                        }
                                        HorizontalDivider(color = MaterialTheme.colorScheme.surfaceVariant)
                                    }
                                }
                            }
                            Spacer(modifier = Modifier.height(24.dp))
                        }

                        if (extra.affiliates.isNotEmpty()) {
                            Text("Top Affiliates", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(8.dp))
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                            ) {
                                Column(modifier = Modifier.padding(16.dp)) {
                                    extra.affiliates.take(5).forEach { a ->
                                        Row(
                                            modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Text(a.name.ifBlank { "Affiliate #${a.id}" }, fontWeight = FontWeight.Medium, maxLines = 1, overflow = TextOverflow.Ellipsis, modifier = Modifier.weight(1f))
                                            Text("C: ${a.clicks} | Cv: ${a.conv}", color = Color.Gray, modifier = Modifier.padding(start = 8.dp))
                                        }
                                        HorizontalDivider(color = MaterialTheme.colorScheme.surfaceVariant)
                                    }
                                }
                            }
                            Spacer(modifier = Modifier.height(24.dp))
                        }

                        // High Risk Fraud Conversions
                        if (extra.fraudConvs.isNotEmpty()) {
                            Text("High Risk Fraud Conversions", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold, color = Color(0xFFEF4444))
                            Spacer(modifier = Modifier.height(8.dp))
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                colors = CardDefaults.cardColors(containerColor = Color(0xFFFEF2F2)),
                                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                                border = BorderStroke(1.dp, Color(0xFFFCA5A5))
                            ) {
                                Column(modifier = Modifier.padding(16.dp)) {
                                    extra.fraudConvs.forEach { fc ->
                                        Column(modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp)) {
                                            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                                Text("ID: ${fc.conversionId}", fontWeight = FontWeight.Bold, color = Color(0xFFB91C1C))
                                                Text("$${(( fc.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontWeight = FontWeight.Bold, color = Color(0xFF15803D))
                                            }
                                            Text("${fc.affName ?: "Unknown"} (${fc.affiliateCode ?: "-"})", style = MaterialTheme.typography.bodyMedium)
                                            Text("IP: ${fc.ipAddress ?: "N/A"}", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                                            Text(fc.convertedAt, style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                                        }
                                        HorizontalDivider(color = Color(0xFFFECACA))
                                    }
                                }
                            }
                            Spacer(modifier = Modifier.height(24.dp))
                        }

                        // Recent Conversions
                        if (extra.recentConvs.isNotEmpty()) {
                            Text("Recent Conversions", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(8.dp))
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                            ) {
                                Column(modifier = Modifier.padding(16.dp)) {
                                    extra.recentConvs.forEach { rc ->
                                        Column(modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp)) {
                                            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                                val statusColor = when (rc.status.lowercase()) {
                                                    "approved" -> Color(0xFF10B981)
                                                    "rejected", "chargebacked" -> Color(0xFFEF4444)
                                                    else -> Color(0xFFF59E0B)
                                                }
                                                Text(rc.status.uppercase(), fontWeight = FontWeight.Bold, color = statusColor, fontSize = 12.sp)
                                                Text("$${(( rc.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontWeight = FontWeight.Bold, color = Color(0xFF15803D))
                                            }
                                            Text(rc.offerName ?: "Offer #${rc.id}", fontWeight = FontWeight.Medium)
                                            Text(rc.affName ?: "Unknown Affiliate", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                                            Text(rc.convertedAt, style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                                        }
                                        HorizontalDivider(color = MaterialTheme.colorScheme.surfaceVariant)
                                    }
                                }
                            }
                            Spacer(modifier = Modifier.height(24.dp))
                        }
                    }
                }
            }
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
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
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

@Composable
fun PieChartCard(labels: List<String>, data: List<Int>, customColors: List<Color>? = null) {
    Card(
        modifier = Modifier.fillMaxWidth().height(200.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        if (labels.isEmpty() || data.isEmpty() || data.sum() == 0) {
            Box(contentAlignment = Alignment.Center, modifier = Modifier.fillMaxSize()) {
                Text("No data", color = Color.Gray)
            }
            return@Card
        }

        val colors = customColors ?: listOf(Color(0xFF4F46E5), Color(0xFF10B981), Color(0xFFF59E0B), Color(0xFFEF4444), Color(0xFF8B5CF6))
        val total = data.sum().toFloat()
        
        Column(
            modifier = Modifier.fillMaxSize().padding(8.dp),
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
            Spacer(modifier = Modifier.height(8.dp))
            Row(
                modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.Center
            ) {
                labels.take(4).forEachIndexed { index, label ->
                    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(horizontal = 4.dp)) {
                        Box(modifier = Modifier.size(6.dp).background(colors[index % colors.size], CircleShape))
                        Spacer(modifier = Modifier.width(2.dp))
                        Text(label, fontSize = 13.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
                    }
                }
            }
        }
    }
}

@Composable
fun PeriodTabs(selectedPeriod: String, onSelect: (String) -> Unit) {
    val periods = listOf(
        "today" to "Today",
        "yesterday" to "Yesterday",
        "7d" to "7D",
        "15d" to "Last 15D",
        "30d" to "30D",
        "90d" to "90D",
        "mtd" to "This Month",
        "lastmonth" to "Last Month"
    )
    
    Row(
        modifier = Modifier.horizontalScroll(rememberScrollState()),
        horizontalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        periods.forEach { (key, label) ->
            FilterChip(
                selected = key == selectedPeriod,
                onClick = { onSelect(key) },
                label = { Text(label) }
            )
        }
    }
}

@Composable
fun KpiGrid(stats: net.affscash.android.data.model.ManagerDashboardData) {
    Column {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            KpiCard(
                title = "Managed Affiliates",
                value = stats.totalAffiliates.toString(),
                subTitle = "Under your management",
                icon = Icons.Outlined.People,
                modifier = Modifier.weight(1f),
                color = Color(0xFF3B82F6)
            )
            KpiCard(
                title = "Total Clicks",
                value = stats.clicks.toString(),
                subTitle = "Unique: ${stats.unique}",
                trend = stats.trend?.clicks,
                icon = Icons.Outlined.AdsClick,
                modifier = Modifier.weight(1f),
                color = Color(0xFF8B5CF6)
            )
        }
        Spacer(modifier = Modifier.height(12.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            KpiCard(
                title = "Conversions",
                value = stats.conv.toString(),
                subTitle = "CR: ${stats.cr}%",
                trend = stats.trend?.conv,
                icon = Icons.Outlined.Analytics,
                modifier = Modifier.weight(1f),
                color = Color(0xFF10B981)
            )
            KpiCard(
                title = "Fraud Conversion %",
                value = "${stats.fraudConvPct}%",
                subTitle = "${stats.fraudConv} Fraud Conversions",
                trend = stats.trend?.fraudConvPct,
                icon = Icons.Outlined.Security,
                modifier = Modifier.weight(1f),
                color = Color(0xFFEF4444)
            )
        }
        Spacer(modifier = Modifier.height(12.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            val scoreColor = when {
                stats.fraudScoreAverage >= 75 -> Color(0xFFEF4444)
                stats.fraudScoreAverage >= 40 -> Color(0xFFF59E0B)
                else -> Color(0xFF10B981)
            }
            KpiCard(
                title = "IPQS Fraud Score",
                value = "${stats.fraudScoreAverage} /100",
                subTitle = "Real-time average",
                icon = Icons.Outlined.Security,
                modifier = Modifier.weight(1f),
                color = scoreColor
            )
        }
    }
}

@Composable
fun KpiCard(
    title: String,
    value: String,
    subTitle: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    trend: Double? = null,
    color: Color,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = color.copy(alpha = 0.15f),
                    modifier = Modifier.size(32.dp)
                ) {
                    Icon(
                        icon,
                        contentDescription = null,
                        tint = color,
                        modifier = Modifier.padding(6.dp)
                    )
                }
                Spacer(modifier = Modifier.width(8.dp))
                Text(title, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            Spacer(modifier = Modifier.height(12.dp))
            Text(value, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold, color = MaterialTheme.colorScheme.onSurface)
            Spacer(modifier = Modifier.height(4.dp))
            Text(subTitle, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            
            trend?.let {
                Spacer(modifier = Modifier.height(8.dp))
                val trendColor = if (it > 0) Color(0xFF059669) else if (it < 0) Color(0xFFDC2626) else Color.Gray
                val trendBg = if (it > 0) Color(0xFFD1FAE5) else if (it < 0) Color(0xFFFEE2E2) else Color(0xFFF3F4F6)
                val trendText = if (it > 0) "▲ ${abs(it)}% vs prev" else if (it < 0) "▼ ${abs(it)}% vs prev" else "—"
                
                Surface(
                    color = trendBg,
                    shape = RoundedCornerShape(8.dp)
                ) {
                    Text(
                        trendText,
                        color = trendColor,
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                    )
                }
            }
        }
    }
}
