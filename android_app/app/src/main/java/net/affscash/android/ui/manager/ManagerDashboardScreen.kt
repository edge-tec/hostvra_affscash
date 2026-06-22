package net.affscash.android.ui.manager

import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.WindowInsetsSides
import androidx.compose.foundation.layout.only
import androidx.compose.foundation.layout.safeDrawing
import androidx.compose.foundation.layout.windowInsetsPadding


import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.ChatBubbleOutline
import androidx.compose.material.icons.outlined.CheckCircle
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
import net.affscash.android.ui.dashboard.PremiumUI
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
    onNavigateToNotifications: () -> Unit = {},
    onNavigateToOfferApprovals: () -> Unit = {}
) {
    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            Surface(
                modifier = Modifier.fillMaxWidth().background(PremiumUI.PastelHeader, RoundedCornerShape(bottomStart = 16.dp, bottomEnd = 16.dp)),
                color = Color.Transparent,
                shape = RoundedCornerShape(bottomStart = 16.dp, bottomEnd = 16.dp)
            ) {
                Box(modifier = Modifier.windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(start = 8.dp, end = 8.dp, top = 8.dp, bottom = 8.dp)
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Image(
                            painter = painterResource(id = R.drawable.logo),
                            contentDescription = "AffsCash Logo",
                            modifier = Modifier.height(44.dp).padding(start = 4.dp)
                        )
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            val stats = uiState.stats
                            val balance = stats?.commissionBalance ?: 0.0
                            val counts = stats?.headerCounts
                            
                            // Balance Pill
                            Surface(
                                color = Color(0x59FFFFFF),
                                shape = PremiumUI.CardShape,
                                border = BorderStroke(1.dp, Color(0x4DFFFFFF)),
                                modifier = Modifier.padding(end = 8.dp),
                                onClick = onNavigateToInvoices
                            ) {
                                Row(
                                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 6.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Icon(
                                        Icons.Outlined.MonetizationOn,
                                        contentDescription = null,
                                        tint = Color(0xFF059669),
                                        modifier = Modifier.size(8.dp)
                                    )
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text(
                                        "$$balance", 
                                        color = Color(0xFF059669),
                                        fontWeight = FontWeight.ExtraBold,
                                        style = MaterialTheme.typography.labelLarge
                                    )
                                }
                            }

                            val badgeManager = remember { net.affscash.android.AffscashApp.getBadgeManager() }

                            // Notifications Icon
                            val liveNotifsCount = if (badgeManager != null) {
                                badgeManager.unreadNotifs.collectAsState(initial = counts?.unreadNotifs ?: 0).value
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
                                badgeManager.unreadAlerts.collectAsState(initial = counts?.unreadAlerts ?: 0).value
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
                                badgeManager.unreadChats.collectAsState(initial = counts?.unreadChats ?: 0).value
                            } else {
                                counts?.unreadChats ?: 0
                            }
                            HeaderIconWithBadge(
                                icon = Icons.Outlined.ChatBubbleOutline,
                                count = liveChatsCount,
                                badgeColor = Color(0xFFEF4444),
                                onClick = onNavigateToChat
                            )

                            // Approvals Icon
                            val liveApprovalsCount = if (badgeManager != null) {
                                badgeManager.pendingApprovals.collectAsState(initial = counts?.pendingApprovals ?: 0).value
                            } else {
                                counts?.pendingApprovals ?: 0
                            }
                            HeaderIconWithBadge(
                                icon = Icons.Outlined.CheckCircle,
                                count = liveApprovalsCount,
                                badgeColor = Color(0xFF10B981),
                                onClick = onNavigateToOfferApprovals
                            )
                        }
                    }
                }
                            }
}
        }
    ) { paddingValues ->
        Box(modifier = Modifier.padding(paddingValues).fillMaxSize().background(PremiumUI.BackgroundGradient)) {
            LazyColumn(
                modifier = Modifier.fillMaxSize(),
                contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp)
            ) {
                item {
                    Text(
                        text = "Analytics Dashboard",
                        style = MaterialTheme.typography.headlineMedium.copy(brush = PremiumUI.PrimaryGradient),
                        fontWeight = FontWeight.ExtraBold
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                }

                item {
                    PeriodTabs(uiState.selectedPeriod) { period ->
                        viewModel.setPeriod(period)
                    }
                    Spacer(modifier = Modifier.height(4.dp))
                }

                if (uiState.isLoadingStats) {
                    item {
                        CircularProgressIndicator(modifier = Modifier.padding(4.dp))
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
                            Spacer(modifier = Modifier.height(4.dp))
                        }
                    }
                }

                item {
                    Text(
                        text = "Performance Trend",
                        style = MaterialTheme.typography.titleSmall,
                        fontWeight = FontWeight.Bold
                    )
                    Spacer(modifier = Modifier.height(4.dp))
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
                                                if ((index >= 0) && (index < trendData.labels.size)) trendData.labels[index] else ""
                                            }
                                        ),
                                        modifier = Modifier.fillMaxSize().padding(4.dp)
                                    )
                                }
                            }
                        } else {
                            Text("No trend data available for this period.")
                        }
                    }
                }

                item {
                    if (uiState.isLoadingExtra) {
                        CircularProgressIndicator(modifier = Modifier.padding(vertical = 4.dp))
                    } else if (uiState.extraData != null) {
                        val extra = uiState.extraData!!
                        
                        // Hourly Traffic
                        if (extra.hourly?.labels?.isNotEmpty() == true) {
                            Text("Hourly Traffic", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(4.dp))
                            BarChartCard(extra.hourly.labels, extra.hourly.data.map { it.toFloat() })
                            Spacer(modifier = Modifier.height(4.dp))
                        }

                        // Conversion Status
                        if (extra.convStatus?.labels?.isNotEmpty() == true) {
                            Text("Conversion Status", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(4.dp))
                            val customColors = extra.convStatus.colors.map { 
                                try { Color(android.graphics.Color.parseColor(it)) } catch(e: Exception) { Color.Gray } 
                            }
                            PieChartCard(extra.convStatus.labels, extra.convStatus.data, customColors)
                            Spacer(modifier = Modifier.height(4.dp))
                        }

                        // Top Countries
                        if (extra.countries.isNotEmpty()) {
                            Text("Top Countries", style = PremiumUI.TitleMedium)
                            Spacer(modifier = Modifier.height(2.dp))
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(10.dp),
                                colors = CardDefaults.cardColors(containerColor = Color.Transparent),
                                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                            ) {
                                Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
                                    Column(modifier = Modifier.padding(8.dp)) {
                                        extra.countries.take(5).forEachIndexed { index, c ->
                                            Row(
                                                modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp),
                                                horizontalArrangement = Arrangement.SpaceBetween,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Text(c.country.ifBlank { "Unknown" }, style = PremiumUI.DataBold)
                                                Text("C: ${c.clicks} | Cv: ${c.conv}", style = PremiumUI.SecondaryText)
                                            }
                                            if (index < 4) HorizontalDivider(color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.5f))
                                        }
                                    }
                                }
                            }
                            Spacer(modifier = Modifier.height(4.dp))
                        }

                        // Breakdowns (Device, Browser, OS)
                        Text("Traffic Breakdown", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                        Spacer(modifier = Modifier.height(4.dp))
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(4.dp)) {
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
                        Spacer(modifier = Modifier.height(4.dp))
                        Row(modifier = Modifier.fillMaxWidth()) {
                            if (extra.os?.labels?.isNotEmpty() == true) {
                                Column(modifier = Modifier.fillMaxWidth(0.5f)) {
                                    Text("OS", style = MaterialTheme.typography.labelLarge)
                                    PieChartCard(extra.os.labels, extra.os.data)
                                }
                            }
                        }
                        Spacer(modifier = Modifier.height(4.dp))
                        
                        // Top Offers & Top Affiliates
                        if (extra.offers.isNotEmpty()) {
                            Text("Top Offers", style = PremiumUI.TitleMedium)
                            Spacer(modifier = Modifier.height(2.dp))
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(10.dp),
                                colors = CardDefaults.cardColors(containerColor = Color.Transparent),
                                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                            ) {
                                Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
                                    Column(modifier = Modifier.padding(8.dp)) {
                                        extra.offers.take(5).forEachIndexed { index, o ->
                                            Row(
                                                modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp),
                                                horizontalArrangement = Arrangement.SpaceBetween,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Text(o.name.ifBlank { "Offer #${o.id}" }, style = PremiumUI.DataBold, maxLines = 1, overflow = TextOverflow.Ellipsis, modifier = Modifier.weight(1f))
                                                Text("C: ${o.clicks} | Cv: ${o.conv}", style = PremiumUI.SecondaryText, modifier = Modifier.padding(start = 8.dp))
                                            }
                                            if (index < 4) HorizontalDivider(color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.5f))
                                        }
                                    }
                                }
                            }
                            Spacer(modifier = Modifier.height(6.dp))
                        }

                        if (extra.affiliates.isNotEmpty()) {
                            Text("Top Affiliates", style = PremiumUI.TitleMedium)
                            Spacer(modifier = Modifier.height(2.dp))
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(10.dp),
                                colors = CardDefaults.cardColors(containerColor = Color.Transparent),
                                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                            ) {
                                Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
                                    Column(modifier = Modifier.padding(8.dp)) {
                                        extra.affiliates.take(5).forEachIndexed { index, a ->
                                            Row(
                                                modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp),
                                                horizontalArrangement = Arrangement.SpaceBetween,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Text(a.name.ifBlank { "Affiliate #${a.id}" }, style = PremiumUI.DataBold, maxLines = 1, overflow = TextOverflow.Ellipsis, modifier = Modifier.weight(1f))
                                                Text("C: ${a.clicks} | Cv: ${a.conv}", style = PremiumUI.SecondaryText, modifier = Modifier.padding(start = 8.dp))
                                            }
                                            if (index < 4) HorizontalDivider(color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.5f))
                                        }
                                    }
                                }
                            }
                            Spacer(modifier = Modifier.height(6.dp))
                        }

                        // High Risk Fraud Conversions
                        if (extra.fraudConvs.isNotEmpty()) {
                            Text("High Risk Fraud Conversions", style = PremiumUI.TitleMedium, color = Color(0xFFEF4444))
                            Spacer(modifier = Modifier.height(2.dp))
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(10.dp),
                                colors = CardDefaults.cardColors(containerColor = Color.Transparent),
                                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                                border = BorderStroke(1.dp, Color(0xFFFCA5A5))
                            ) {
                                Box(modifier = Modifier.background(Color(0xFFFEF2F2).copy(alpha = 0.9f)).fillMaxSize()) {
                                    Column(modifier = Modifier.padding(8.dp)) {
                                        extra.fraudConvs.forEachIndexed { index, fc ->
                                            Column(modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp)) {
                                                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                                                    Text("ID: ${fc.conversionId}", style = PremiumUI.DataBold, color = Color(0xFFB91C1C))
                                                    Text("$${(( fc.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontWeight = FontWeight.Bold, color = Color(0xFF15803D))
                                                }
                                                Text("${fc.affName ?: "Unknown"} (${fc.affiliateCode ?: "-"})", style = PremiumUI.DataBold)
                                                Text("IP: ${fc.ipAddress ?: "N/A"} • ${fc.convertedAt}", style = PremiumUI.SecondaryText)
                                            }
                                            if (index < extra.fraudConvs.lastIndex) HorizontalDivider(color = Color(0xFFFECACA))
                                        }
                                    }
                                }
                            }
                            Spacer(modifier = Modifier.height(6.dp))
                        }

                        // Recent Conversions
                        if (extra.recentConvs.isNotEmpty()) {
                            Text("Recent Conversions", style = PremiumUI.TitleMedium)
                            Spacer(modifier = Modifier.height(2.dp))
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(10.dp),
                                colors = CardDefaults.cardColors(containerColor = Color.Transparent),
                                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                            ) {
                                Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
                                    Column(modifier = Modifier.padding(8.dp)) {
                                        extra.recentConvs.forEachIndexed { index, rc ->
                                            Column(modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp)) {
                                                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                                                    net.affscash.android.ui.dashboard.StatusBadge(status = rc.status)
                                                    Text("$${(( rc.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontWeight = FontWeight.Bold, color = Color(0xFF15803D))
                                                }
                                                Spacer(modifier = Modifier.height(4.dp))
                                                Text(rc.offerName ?: "Offer #${rc.id}", style = PremiumUI.DataBold)
                                                Text("${rc.affName ?: "Unknown"} • ${rc.convertedAt}", style = PremiumUI.SecondaryText)
                                            }
                                            if (index < extra.recentConvs.lastIndex) HorizontalDivider(color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.5f))
                                        }
                                    }
                                }
                            }
                            Spacer(modifier = Modifier.height(6.dp))
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
fun PieChartCard(labels: List<String>, data: List<Int>, customColors: List<Color>? = null) {
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
            } else {
                val colors = customColors ?: listOf(Color(0xFF4F46E5), Color(0xFF10B981), Color(0xFFF59E0B), Color(0xFFEF4444), Color(0xFF8B5CF6))
                val total = data.sum().toFloat()
                
                Box(modifier = Modifier.windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {
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
                    Spacer(modifier = Modifier.height(4.dp))
                    Row(
                        modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.Center
                    ) {
                        labels.take(4).forEachIndexed { index, label ->
                            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(horizontal = 4.dp)) {
                                Box(modifier = Modifier.size(6.dp).background(colors[index % colors.size], CircleShape))
                                Spacer(modifier = Modifier.width(2.dp))
                                Text(label, fontSize = 12.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
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
        horizontalArrangement = Arrangement.spacedBy(4.dp)
    ) {
        periods.forEach { (key, label) ->
            FilterChip(
modifier = Modifier.height(28.dp),
                selected = key == selectedPeriod,
                onClick = { onSelect(key) },
                label = { Text(label, fontSize = 11.sp) }
            )
        }
    }
}

@Composable
fun KpiGrid(stats: net.affscash.android.data.model.ManagerDashboardData) {
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            KpiCard(
                title = "MANAGED AFFILIATES",
                value = stats.totalAffiliates.toString(),
                subTitle = "Under your management",
                icon = Icons.Outlined.People,
                modifier = Modifier.weight(1f),
                color = Color(0xFF3B82F6)
            )
            KpiCard(
                title = "TOTAL CLICKS",
                value = stats.clicks.toString(),
                subTitle = "Unique: ${stats.unique}",
                trend = stats.trend?.clicks,
                icon = Icons.Outlined.AdsClick,
                modifier = Modifier.weight(1f),
                color = Color(0xFF3B82F6)
            )
        }
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            KpiCard(
                title = "CONVERSIONS",
                value = stats.conv.toString(),
                subTitle = "CR: ${stats.cr}%",
                trend = stats.trend?.conv,
                icon = Icons.Outlined.Analytics,
                modifier = Modifier.weight(1f),
                color = Color(0xFF10B981)
            )
            KpiCard(
                title = "CONVERSION RATE",
                value = "${stats.cr}%",
                subTitle = "Overall CR%",
                icon = Icons.Outlined.Analytics,
                modifier = Modifier.weight(1f),
                color = Color(0xFF10B981)
            )
        }
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            KpiCard(
                title = "FRAUD CONVERSION %",
                value = "${stats.fraudConvPct}%",
                subTitle = "${stats.fraudConv} Fraud",
                trend = stats.trend?.fraudConvPct,
                icon = Icons.Outlined.Security,
                modifier = Modifier.weight(1f),
                color = Color(0xFFEF4444)
            )
            val scoreColor = when {
                stats.fraudScoreAverage >= 75 -> Color(0xFFEF4444)
                stats.fraudScoreAverage >= 40 -> Color(0xFFF59E0B)
                else -> Color(0xFF10B981)
            }
            KpiCard(
                title = "IPQS FRAUD SCORE",
                value = "${stats.fraudScoreAverage}/100",
                subTitle = "Real-time avg",
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
    color: Color,
    modifier: Modifier = Modifier,
    trend: Double? = null
) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
            Column(modifier = Modifier.padding(10.dp)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Surface(
                        shape = PremiumUI.CardShape,
                        color = color.copy(alpha = 0.15f),
                        modifier = Modifier.size(24.dp)
                    ) {
                        Icon(
                            icon,
                            contentDescription = null,
                            tint = color,
                            modifier = Modifier.padding(4.dp)
                        )
                    }
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(title, fontSize = 10.sp, fontWeight = FontWeight.SemiBold, color = MaterialTheme.colorScheme.onSurfaceVariant, maxLines = 1, overflow = TextOverflow.Ellipsis)
                }
                Spacer(modifier = Modifier.height(4.dp))
                Text(value, style = PremiumUI.DataBold, color = MaterialTheme.colorScheme.onSurface)
                Spacer(modifier = Modifier.height(2.dp))
                Text(subTitle, style = PremiumUI.SecondaryText)
                
                trend?.let {
                    Spacer(modifier = Modifier.height(4.dp))
                    val trendColor = if (it > 0) Color(0xFF059669) else if (it < 0) Color(0xFFDC2626) else Color.Gray
                    val trendBg = if (it > 0) Color(0xFFD1FAE5) else if (it < 0) Color(0xFFFEE2E2) else Color(0xFFF3F4F6)
                    val trendText = if (it > 0) "▲ ${abs(it)}%" else if (it < 0) "▼ ${abs(it)}%" else "—"
                    
                    Surface(
                        color = trendBg,
                        shape = RoundedCornerShape(4.dp)
                    ) {
                        Text(
                            trendText,
                            color = trendColor,
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
