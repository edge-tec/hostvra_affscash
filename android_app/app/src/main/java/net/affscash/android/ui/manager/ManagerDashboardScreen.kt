package net.affscash.android.ui.manager

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
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.patrykandpatrick.vico.compose.axis.horizontal.rememberBottomAxis
import com.patrykandpatrick.vico.compose.axis.vertical.rememberStartAxis
import com.patrykandpatrick.vico.compose.chart.Chart
import com.patrykandpatrick.vico.compose.chart.column.columnChart
import com.patrykandpatrick.vico.compose.chart.line.lineChart
import com.patrykandpatrick.vico.core.entry.FloatEntry
import com.patrykandpatrick.vico.core.entry.entryModelOf
import net.affscash.android.R
import net.affscash.android.ui.dashboard.HeaderIconWithBadge
import net.affscash.android.ui.dashboard.KPICard3D
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.ManagerDashboardViewModel
import net.affscash.android.ui.dashboard.PremiumUI
import net.affscash.android.ui.dashboard.StatusBadge

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
                modifier = Modifier
                    .fillMaxWidth()
                    .background(PremiumUI.BackgroundGradient),
                color = Color.Transparent
            ) {
                Box(modifier = Modifier.windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 12.dp, vertical = 8.dp)
                    ) {
                        Surface(
                            modifier = Modifier.fillMaxWidth(),
                            shape = PremiumUI.CardShape,
                            color = Color.White,
                            shadowElevation = 2.dp,
                            border = PremiumUI.GlassBorder
                        ) {
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(horizontal = 14.dp, vertical = 8.dp),
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Image(
                                    painter = painterResource(id = R.drawable.logo),
                                    contentDescription = "AffsCash Logo",
                                    modifier = Modifier.height(38.dp)
                                )
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    val stats = uiState.stats
                                    val balance = stats?.commissionBalance ?: 0.0
                                    val counts = stats?.headerCounts
                                    
                                    // Balance Pill
                                    Surface(
                                        color = Color(0xFFD1FAE5),
                                        shape = PremiumUI.PillShape,
                                        modifier = Modifier
                                            .padding(end = 8.dp),
                                        onClick = onNavigateToInvoices
                                    ) {
                                        Row(
                                            modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp),
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Icon(
                                                Icons.Outlined.MonetizationOn,
                                                contentDescription = null,
                                                tint = Color(0xFF059669),
                                                modifier = Modifier.size(14.dp)
                                            )
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Text(
                                                "$$balance", 
                                                color = Color(0xFF059669),
                                                fontWeight = FontWeight.ExtraBold,
                                                fontSize = 12.sp
                                            )
                                        }
                                    }

                                    val badgeManager = remember { net.affscash.android.AffscashApp.getBadgeManager() }

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
        }
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .padding(paddingValues)
                .fillMaxSize()
                .background(PremiumUI.BackgroundGradient)
        ) {
            LazyColumn(
                modifier = Modifier.fillMaxSize(),
                contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp)
            ) {
                item {
                    Text(
                        text = "Analytics Dashboard",
                        style = PremiumUI.HeaderStyle,
                        color = Color(0xFF1E293B)
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                }

                item {
                    PeriodTabs(uiState.selectedPeriod) { period ->
                        viewModel.setPeriod(period)
                    }
                    Spacer(modifier = Modifier.height(12.dp))
                }

                if (uiState.isLoadingStats) {
                    item {
                        CircularProgressIndicator(modifier = Modifier.padding(16.dp), color = Color(0xFF4F46E5))
                    }
                } else if (uiState.error != null) {
                    item {
                        Text("Error: ${uiState.error}", color = MaterialTheme.colorScheme.error)
                        Button(
                            onClick = { viewModel.setPeriod(uiState.selectedPeriod) },
                            shape = PremiumUI.ButtonShape
                        ) {
                            Text("Retry")
                        }
                    }
                } else {
                    uiState.stats?.let { stats ->
                        item {
                            KpiGrid3D(stats)
                            Spacer(modifier = Modifier.height(14.dp))
                        }
                    }
                }

                item {
                    Text(
                        text = "Performance Trend",
                        style = PremiumUI.HeaderStyle,
                        color = Color(0xFF1E293B)
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                }

                item {
                    if (uiState.isLoadingTrend) {
                        CircularProgressIndicator(color = Color(0xFF4F46E5))
                    } else if (uiState.trend != null) {
                        val trendData = uiState.trend!!
                        if (trendData.labels.isNotEmpty() && trendData.clicksData.isNotEmpty()) {
                            val entries = trendData.clicksData.mapIndexed { index, value ->
                                FloatEntry(x = index.toFloat(), y = value.toFloat())
                            }
                            val model = entryModelOf(entries)
                            GlassCard(elevation = 6.dp) {
                                Box(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .height(160.dp)
                                ) {
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
                                        modifier = Modifier.fillMaxSize()
                                    )
                                }
                            }
                        } else {
                            GlassCard {
                                Text("No trend data available for this period.", style = PremiumUI.SecondaryText)
                            }
                        }
                    }
                    Spacer(modifier = Modifier.height(14.dp))
                }

                item {
                    if (uiState.isLoadingExtra) {
                        CircularProgressIndicator(modifier = Modifier.padding(vertical = 8.dp), color = Color(0xFF4F46E5))
                    } else if (uiState.extraData != null) {
                        val extra = uiState.extraData!!
                        
                        // Hourly Traffic
                        if (extra.hourly?.labels?.isNotEmpty() == true) {
                            Text("Hourly Traffic", style = PremiumUI.HeaderStyle, color = Color(0xFF1E293B))
                            Spacer(modifier = Modifier.height(8.dp))
                            BarChartCard3D(extra.hourly.labels, extra.hourly.data.map { it.toFloat() })
                            Spacer(modifier = Modifier.height(14.dp))
                        }

                        // Conversion Status
                        if (extra.convStatus?.labels?.isNotEmpty() == true) {
                            Text("Conversion Status", style = PremiumUI.HeaderStyle, color = Color(0xFF1E293B))
                            Spacer(modifier = Modifier.height(8.dp))
                            val customColors = extra.convStatus.colors.map { 
                                try { Color(android.graphics.Color.parseColor(it)) } catch(e: Exception) { Color.Gray } 
                            }
                            PieChartCard3D(extra.convStatus.labels, extra.convStatus.data, customColors)
                            Spacer(modifier = Modifier.height(14.dp))
                        }

                        // Top Countries
                        if (extra.countries.isNotEmpty()) {
                            Text("Top Countries", style = PremiumUI.HeaderStyle, color = Color(0xFF1E293B))
                            Spacer(modifier = Modifier.height(8.dp))
                            GlassCard(elevation = 6.dp) {
                                extra.countries.take(5).forEachIndexed { index, c ->
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(vertical = 8.dp),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Text(c.country.ifBlank { "Unknown" }, style = PremiumUI.DataBold, color = Color(0xFF0F172A))
                                        Text("C: ${c.clicks} | Cv: ${c.conv}", style = PremiumUI.SecondaryText)
                                    }
                                    if (index < 4 && index < extra.countries.size - 1) HorizontalDivider(color = Color(0xFFE2E8F0))
                                }
                            }
                            Spacer(modifier = Modifier.height(14.dp))
                        }

                        // Breakdowns (Device, Browser, OS)
                        Text("Traffic Breakdown", style = PremiumUI.HeaderStyle, color = Color(0xFF1E293B))
                        Spacer(modifier = Modifier.height(8.dp))
                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                            if (extra.devices?.labels?.isNotEmpty() == true) {
                                Column(modifier = Modifier.weight(1f)) {
                                    Text("Devices", style = PremiumUI.TitleMedium, color = Color(0xFF475569))
                                    Spacer(modifier = Modifier.height(4.dp))
                                    PieChartCard3D(extra.devices.labels, extra.devices.data)
                                }
                            }
                            if (extra.browsers?.labels?.isNotEmpty() == true) {
                                Column(modifier = Modifier.weight(1f)) {
                                    Text("Browsers", style = PremiumUI.TitleMedium, color = Color(0xFF475569))
                                    Spacer(modifier = Modifier.height(4.dp))
                                    PieChartCard3D(extra.browsers.labels, extra.browsers.data)
                                }
                            }
                        }
                        Spacer(modifier = Modifier.height(10.dp))
                        Row(modifier = Modifier.fillMaxWidth()) {
                            if (extra.os?.labels?.isNotEmpty() == true) {
                                Column(modifier = Modifier.fillMaxWidth(0.5f)) {
                                    Text("OS", style = PremiumUI.TitleMedium, color = Color(0xFF475569))
                                    Spacer(modifier = Modifier.height(4.dp))
                                    PieChartCard3D(extra.os.labels, extra.os.data)
                                }
                            }
                        }
                        Spacer(modifier = Modifier.height(14.dp))
                        
                        // Top Offers & Top Affiliates
                        if (extra.offers.isNotEmpty()) {
                            Text("Top Offers", style = PremiumUI.HeaderStyle, color = Color(0xFF1E293B))
                            Spacer(modifier = Modifier.height(8.dp))
                            GlassCard(elevation = 6.dp) {
                                extra.offers.take(5).forEachIndexed { index, o ->
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(vertical = 8.dp),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Text(
                                            o.name.ifBlank { "Offer #${o.id}" }, 
                                            style = PremiumUI.DataBold, 
                                            color = Color(0xFF0F172A),
                                            maxLines = 1, 
                                            overflow = TextOverflow.Ellipsis, 
                                            modifier = Modifier.weight(1f)
                                        )
                                        Text("C: ${o.clicks} | Cv: ${o.conv}", style = PremiumUI.SecondaryText, modifier = Modifier.padding(start = 8.dp))
                                    }
                                    if (index < 4 && index < extra.offers.size - 1) HorizontalDivider(color = Color(0xFFE2E8F0))
                                }
                            }
                            Spacer(modifier = Modifier.height(14.dp))
                        }

                        if (extra.affiliates.isNotEmpty()) {
                            Text("Top Affiliates", style = PremiumUI.HeaderStyle, color = Color(0xFF1E293B))
                            Spacer(modifier = Modifier.height(8.dp))
                            GlassCard(elevation = 6.dp) {
                                extra.affiliates.take(5).forEachIndexed { index, a ->
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(vertical = 8.dp),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Text(
                                            a.name.ifBlank { "Affiliate #${a.id}" }, 
                                            style = PremiumUI.DataBold, 
                                            color = Color(0xFF0F172A),
                                            maxLines = 1, 
                                            overflow = TextOverflow.Ellipsis, 
                                            modifier = Modifier.weight(1f)
                                        )
                                        Text("C: ${a.clicks} | Cv: ${a.conv}", style = PremiumUI.SecondaryText, modifier = Modifier.padding(start = 8.dp))
                                    }
                                    if (index < 4 && index < extra.affiliates.size - 1) HorizontalDivider(color = Color(0xFFE2E8F0))
                                }
                            }
                            Spacer(modifier = Modifier.height(14.dp))
                        }

                        // High Risk Fraud Conversions
                        if (extra.fraudConvs.isNotEmpty()) {
                            Text("High Risk Fraud Conversions", style = PremiumUI.HeaderStyle, color = Color(0xFFEF4444))
                            Spacer(modifier = Modifier.height(8.dp))
                            GlassCard(
                                elevation = 6.dp,
                                containerColor = Color(0xFFFEF2F2).copy(alpha = 0.95f)
                            ) {
                                extra.fraudConvs.forEachIndexed { index, fc ->
                                    Column(modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(), 
                                            horizontalArrangement = Arrangement.SpaceBetween, 
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text("ID: ${fc.conversionId}", style = PremiumUI.DataBold, color = Color(0xFFB91C1C))
                                            Text("$${(( fc.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontWeight = FontWeight.Bold, color = Color(0xFF059669))
                                        }
                                        Text("${fc.affName ?: "Unknown"} (${fc.affiliateCode ?: "-"})", style = PremiumUI.DataBold, color = Color(0xFF0F172A))
                                        Text("IP: ${fc.ipAddress ?: "N/A"} • ${fc.convertedAt}", style = PremiumUI.SecondaryText)
                                    }
                                    if (index < extra.fraudConvs.lastIndex) HorizontalDivider(color = Color(0xFFFECACA))
                                }
                            }
                            Spacer(modifier = Modifier.height(14.dp))
                        }

                        // Recent Conversions
                        if (extra.recentConvs.isNotEmpty()) {
                            Text("Recent Conversions", style = PremiumUI.HeaderStyle, color = Color(0xFF1E293B))
                            Spacer(modifier = Modifier.height(8.dp))
                            GlassCard(elevation = 6.dp) {
                                extra.recentConvs.forEachIndexed { index, rc ->
                                    Column(modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(), 
                                            horizontalArrangement = Arrangement.SpaceBetween, 
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            StatusBadge(status = rc.status)
                                            Text("$${(( rc.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontWeight = FontWeight.Bold, color = Color(0xFF059669))
                                        }
                                        Spacer(modifier = Modifier.height(4.dp))
                                        Text(rc.offerName ?: "Offer #${rc.id}", style = PremiumUI.DataBold, color = Color(0xFF0F172A))
                                        Text("${rc.affName ?: "Unknown"} • ${rc.convertedAt}", style = PremiumUI.SecondaryText)
                                    }
                                    if (index < extra.recentConvs.lastIndex) HorizontalDivider(color = Color(0xFFE2E8F0))
                                }
                            }
                            Spacer(modifier = Modifier.height(20.dp))
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun BarChartCard3D(labels: List<String>, data: List<Float>) {
    if (labels.isEmpty() || data.isEmpty()) {
        GlassCard {
            Box(contentAlignment = Alignment.Center, modifier = Modifier.fillMaxSize().height(120.dp)) {
                Text("No data available", color = Color.Gray)
            }
        }
        return
    }

    val entries = data.mapIndexed { index, value ->
        FloatEntry(x = index.toFloat(), y = value)
    }
    val model = entryModelOf(entries)

    GlassCard(elevation = 6.dp) {
        Box(modifier = Modifier.fillMaxWidth().height(150.dp)) {
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
                modifier = Modifier.fillMaxSize()
            )
        }
    }
}

@Composable
fun PieChartCard3D(labels: List<String>, data: List<Int>, customColors: List<Color>? = null) {
    GlassCard(elevation = 6.dp) {
        if (labels.isEmpty() || data.isEmpty() || data.sum() == 0) {
            Box(contentAlignment = Alignment.Center, modifier = Modifier.fillMaxSize().height(120.dp)) {
                Text("No data", color = Color.Gray)
            }
        } else {
            val colors = customColors ?: listOf(Color(0xFF4F46E5), Color(0xFF10B981), Color(0xFFF59E0B), Color(0xFFEF4444), Color(0xFF8B5CF6))
            val total = data.sum().toFloat()
            
            Column(
                modifier = Modifier.fillMaxWidth().height(150.dp),
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
                                style = Stroke(width = 18f, cap = StrokeCap.Butt),
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
                        Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(horizontal = 6.dp)) {
                            Box(modifier = Modifier.size(8.dp).background(colors[index % colors.size], CircleShape))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text(label, fontSize = 11.sp, color = Color(0xFF475569), maxLines = 1, overflow = TextOverflow.Ellipsis)
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
    
    val scrollState = rememberScrollState()
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = PremiumUI.CardShape,
        color = Color.White.copy(alpha = 0.85f),
        shadowElevation = 4.dp,
        border = PremiumUI.GlassBorder
    ) {
        Row(
            modifier = Modifier
                .horizontalScroll(scrollState)
                .padding(6.dp),
            horizontalArrangement = Arrangement.spacedBy(6.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            periods.forEach { (key, label) ->
                val isSelected = key == selectedPeriod
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(14.dp))
                        .background(
                            if (isSelected) PremiumUI.PrimaryGradient
                            else androidx.compose.ui.graphics.Brush.linearGradient(listOf(Color.Transparent, Color.Transparent))
                        )
                        .clickable { onSelect(key) }
                        .padding(horizontal = 14.dp, vertical = 8.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = label,
                        fontSize = 12.sp,
                        fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
                        color = if (isSelected) Color.White else Color(0xFF475569)
                    )
                }
            }
        }
    }
}

@Composable
fun KpiGrid3D(stats: net.affscash.android.data.model.ManagerDashboardData) {
    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            KPICard3D(
                title = "MANAGED AFFILIATES",
                value = stats.totalAffiliates.toString(),
                subtitle = "Under management",
                icon = Icons.Outlined.People,
                iconGradient = PremiumUI.CyanGradient,
                modifier = Modifier.weight(1f)
            )
            KPICard3D(
                title = "TOTAL CLICKS",
                value = stats.clicks.toString(),
                subtitle = "Unique: ${stats.unique}",
                trendPercent = stats.trend?.clicks,
                icon = Icons.Outlined.AdsClick,
                iconGradient = PremiumUI.PrimaryGradient,
                modifier = Modifier.weight(1f)
            )
        }
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            KPICard3D(
                title = "CONVERSIONS",
                value = stats.conv.toString(),
                subtitle = "CR: ${stats.cr}%",
                trendPercent = stats.trend?.conv,
                icon = Icons.Outlined.Analytics,
                iconGradient = PremiumUI.EmeraldGradient,
                modifier = Modifier.weight(1f)
            )
            KPICard3D(
                title = "CONVERSION RATE",
                value = "${stats.cr}%",
                subtitle = "Overall CR%",
                icon = Icons.Outlined.Analytics,
                iconGradient = PremiumUI.PurpleGradient,
                modifier = Modifier.weight(1f)
            )
        }
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            KPICard3D(
                title = "FRAUD CONVERSION %",
                value = "${stats.fraudConvPct}%",
                subtitle = "${stats.fraudConv} Fraud",
                trendPercent = stats.trend?.fraudConvPct,
                icon = Icons.Outlined.Security,
                iconGradient = PremiumUI.RoseGradient,
                modifier = Modifier.weight(1f)
            )
            KPICard3D(
                title = "IPQS FRAUD SCORE",
                value = "${stats.fraudScoreAverage}/100",
                subtitle = "Real-time avg",
                icon = Icons.Outlined.Security,
                iconGradient = if (stats.fraudScoreAverage >= 75) PremiumUI.RoseGradient else PremiumUI.AmberGradient,
                modifier = Modifier.weight(1f)
            )
        }
    }
}
