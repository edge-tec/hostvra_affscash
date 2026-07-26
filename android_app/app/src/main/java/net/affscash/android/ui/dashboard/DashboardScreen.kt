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
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
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
import net.affscash.android.data.model.DashboardAnalyticsStatsResponse
import net.affscash.android.data.model.DashboardTrendChartResponse

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
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(horizontal = 14.dp, vertical = 8.dp),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Image(
                                    painter = painterResource(id = R.drawable.logo),
                                    contentDescription = "AffsCash Logo",
                                    modifier = Modifier.height(38.dp)
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
                                        color = Color(0xFFD1FAE5),
                                        shape = PremiumUI.PillShape,
                                        modifier = Modifier.padding(end = 4.dp),
                                        onClick = onNavigateToInvoices
                                    ) {
                                        Row(
                                            modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp),
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text(
                                                "$$balance", 
                                                color = Color(0xFF059669),
                                                fontWeight = FontWeight.ExtraBold,
                                                fontSize = 12.sp
                                            )
                                            Spacer(modifier = Modifier.width(2.dp))
                                            net.affscash.android.ui.dashboard.GradientIcon(
                                                Icons.Default.ArrowDropDown,
                                                contentDescription = "Dropdown",
                                                tint = Color(0xFF059669),
                                                modifier = Modifier.size(14.dp)
                                            )
                                        }
                                    }

                                    val badgeManager = remember { net.affscash.android.AffscashApp.getBadgeManager() }

                                    HeaderIconWithBadge(
                                        icon = Icons.Outlined.Article,
                                        count = counts?.unreadNews ?: 0,
                                        badgeColor = Color(0xFF4F46E5),
                                        onClick = onNavigateToNews
                                    )

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
            when (uiState) {
                is DashboardState.Loading -> {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center), color = Color(0xFF4F46E5))
                }
                is DashboardState.Error -> {
                    val msg = (uiState as DashboardState.Error).message
                    Column(
                        modifier = Modifier.align(Alignment.Center),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(msg, color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(
                            onClick = { viewModel.loadDashboardData() },
                            shape = PremiumUI.ButtonShape
                        ) {
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
                                style = PremiumUI.HeaderStyle,
                                color = Color(0xFF1E293B)
                            )
                            Spacer(modifier = Modifier.height(8.dp))
                        }
                        
                        item {
                            PeriodTabs(filterOption) { period ->
                                viewModel.setFilterOption(period)
                            }
                            Spacer(modifier = Modifier.height(12.dp))
                        }

                        item {
                            KpiGrid3D(data.stats)
                            Spacer(modifier = Modifier.height(14.dp))
                        }

                        item {
                            Text("Performance Trend", style = PremiumUI.HeaderStyle, color = Color(0xFF1E293B))
                            Spacer(modifier = Modifier.height(8.dp))
                            LineChartCard3D(data.trend)
                            Spacer(modifier = Modifier.height(14.dp))
                        }

                        item {
                            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                                Column(modifier = Modifier.weight(1f)) {
                                    Text("Devices", style = PremiumUI.TitleMedium, color = Color(0xFF475569))
                                    Spacer(modifier = Modifier.height(4.dp))
                                    PieChartCard3D(data.devices.labels, data.devices.data)
                                }
                                Column(modifier = Modifier.weight(1f)) {
                                    Text("Browsers", style = PremiumUI.TitleMedium, color = Color(0xFF475569))
                                    Spacer(modifier = Modifier.height(4.dp))
                                    PieChartCard3D(data.browsers.labels, data.browsers.data)
                                }
                            }
                            Spacer(modifier = Modifier.height(14.dp))
                        }

                        item {
                            Text("Hourly Traffic", style = PremiumUI.HeaderStyle, color = Color(0xFF1E293B))
                            Spacer(modifier = Modifier.height(8.dp))
                            BarChartCard3D(data.hourly.labels, data.hourly.data.map { it.toFloat() })
                            Spacer(modifier = Modifier.height(14.dp))
                        }

                        item {
                            Text("Traffic Sources", style = PremiumUI.HeaderStyle, color = Color(0xFF1E293B))
                            Spacer(modifier = Modifier.height(8.dp))
                            BarChartCard3D(data.sources.labels, data.sources.data.map { it.toFloat() })
                            Spacer(modifier = Modifier.height(14.dp))
                        }

                        item {
                            Text("Top Offers Ranking", style = PremiumUI.HeaderStyle, color = Color(0xFF1E293B))
                            Spacer(modifier = Modifier.height(8.dp))
                            OffersTable3D(data.offers.rows)
                            Spacer(modifier = Modifier.height(14.dp))
                        }

                        item {
                            Text("Top Countries", style = PremiumUI.HeaderStyle, color = Color(0xFF1E293B))
                            Spacer(modifier = Modifier.height(8.dp))
                            CountriesTable3D(data.countries.rows)
                            Spacer(modifier = Modifier.height(20.dp))
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
    val scrollState = rememberScrollState()
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = PremiumUI.CardShape,
        color = Color.White,
        shadowElevation = 2.dp,
        border = PremiumUI.GlassBorder
    ) {
        Row(
            modifier = Modifier
                .horizontalScroll(scrollState)
                .padding(6.dp),
            horizontalArrangement = Arrangement.spacedBy(6.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            periods.forEach { period ->
                val isSelected = selectedPeriod == period
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(14.dp))
                        .background(
                            if (isSelected) PremiumUI.PrimaryGradient
                            else androidx.compose.ui.graphics.Brush.linearGradient(listOf(Color.Transparent, Color.Transparent))
                        )
                        .clickable { onPeriodSelected(period) }
                        .padding(horizontal = 14.dp, vertical = 8.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = period,
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
fun KpiGrid3D(stats: DashboardAnalyticsStatsResponse) {
    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            KPICard3D(
                title = "Total Clicks",
                value = "${stats.clicks}",
                trendPercent = stats.trend.clicks,
                icon = Icons.Outlined.AdsClick,
                iconGradient = PremiumUI.CyanGradient,
                modifier = Modifier.weight(1f)
            )
            KPICard3D(
                title = "Unique Clicks",
                value = "${stats.unique}",
                icon = Icons.Outlined.People,
                iconGradient = PremiumUI.PrimaryGradient,
                modifier = Modifier.weight(1f)
            )
        }
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            KPICard3D(
                title = "Conversions",
                value = "${stats.conversions}",
                trendPercent = stats.trend.conv,
                icon = Icons.Outlined.Analytics,
                iconGradient = PremiumUI.EmeraldGradient,
                modifier = Modifier.weight(1f)
            )
            KPICard3D(
                title = "Revenue",
                value = "$${(( stats.revenue )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                trendPercent = stats.trend.revenue,
                icon = Icons.Outlined.MonetizationOn,
                iconGradient = PremiumUI.PurpleGradient,
                modifier = Modifier.weight(1f)
            )
        }
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            KPICard3D(
                title = "Conv. Rate",
                value = "${stats.cr}%",
                icon = Icons.Outlined.TrendingUp,
                iconGradient = PremiumUI.AmberGradient,
                modifier = Modifier.weight(1f)
            )
            KPICard3D(
                title = "Fraud Conv %",
                value = "${stats.fraudConvPct}%",
                trendPercent = stats.trend.fraudConvPct,
                icon = Icons.Outlined.Security,
                iconGradient = PremiumUI.RoseGradient,
                modifier = Modifier.weight(1f)
            )
        }
    }
}

@Composable
fun LineChartCard3D(trendData: DashboardTrendChartResponse) {
    if (trendData.labels.isEmpty() || trendData.clicksData.isEmpty()) {
        GlassCard {
            Box(contentAlignment = Alignment.Center, modifier = Modifier.fillMaxSize().height(120.dp)) {
                Text("No data available", color = Color.Gray)
            }
        }
        return
    }

    val entries = trendData.clicksData.mapIndexed { index, value ->
        FloatEntry(x = index.toFloat(), y = value.toFloat())
    }
    val model = entryModelOf(entries)

    GlassCard(elevation = 6.dp) {
        Box(modifier = Modifier.fillMaxWidth().height(160.dp)) {
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
                modifier = Modifier.fillMaxSize()
            )
        }
    }
}

@Composable
fun OffersTable3D(offers: List<net.affscash.android.data.model.DashboardOfferRow>) {
    GlassCard(elevation = 2.dp) {
        if (offers.isEmpty()) {
            Text("No offers available", modifier = Modifier.padding(4.dp), color = Color(0xFF64748B), fontSize = 13.sp)
        } else {
            offers.forEachIndexed { index, offer ->
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 8.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Surface(
                        color = when(index) {
                            0 -> Color(0xFFFEF3C7)
                            1 -> Color(0xFFF1F5F9)
                            2 -> Color(0xFFFFEDD5)
                            else -> Color(0xFFEEF2FF)
                        },
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text(
                            text = "#${index + 1}",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = when(index) {
                                0 -> Color(0xFFD97706)
                                1 -> Color(0xFF475569)
                                2 -> Color(0xFFC2410C)
                                else -> Color(0xFF4F46E5)
                            },
                            modifier = Modifier.padding(horizontal = 7.dp, vertical = 3.dp)
                        )
                    }
                    Spacer(modifier = Modifier.width(8.dp))
                    Text(offer.name, modifier = Modifier.weight(1f), fontSize = 13.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A), maxLines = 1, overflow = TextOverflow.Ellipsis)
                    
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Surface(
                            color = Color(0xFFF8FAFC),
                            shape = RoundedCornerShape(6.dp),
                            border = BorderStroke(1.dp, Color(0xFFE2E8F0))
                        ) {
                            Text(
                                text = "C: ${offer.clicks} | Cv: ${offer.conv}",
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Medium,
                                color = Color(0xFF475569),
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                            )
                        }
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "$${(( offer.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                            fontWeight = FontWeight.ExtraBold,
                            color = Color(0xFF10B981),
                            fontSize = 13.sp
                        )
                    }
                }
                if (index < offers.lastIndex) {
                    HorizontalDivider(color = Color(0xFFF1F5F9))
                }
            }
        }
    }
}

@Composable
fun CountriesTable3D(countries: List<net.affscash.android.data.model.DashboardCountryRow>) {
    GlassCard(elevation = 2.dp) {
        if (countries.isEmpty()) {
            Text("No countries data available", modifier = Modifier.padding(4.dp), color = Color(0xFF64748B), fontSize = 13.sp)
        } else {
            countries.forEachIndexed { index, row ->
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 8.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Surface(
                        color = Color(0xFFEEF2FF),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text(
                            text = row.country.ifEmpty { "XX" }.uppercase(),
                            fontSize = 11.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = Color(0xFF4F46E5),
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                        )
                    }
                    Spacer(modifier = Modifier.width(8.dp))
                    Text(
                        text = row.country.ifEmpty { "Unknown" },
                        modifier = Modifier.weight(1f),
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF0F172A),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Surface(
                            color = Color(0xFFF8FAFC),
                            shape = RoundedCornerShape(6.dp),
                            border = BorderStroke(1.dp, Color(0xFFE2E8F0))
                        ) {
                            Text(
                                text = "Clicks: ${row.clicks}",
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Medium,
                                color = Color(0xFF475569),
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                            )
                        }
                        Spacer(modifier = Modifier.width(6.dp))
                        Surface(
                            color = Color(0xFFD1FAE5),
                            shape = RoundedCornerShape(6.dp)
                        ) {
                            Text(
                                text = "Conv: ${row.conv}",
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF059669),
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                            )
                        }
                    }
                }
                if (index < countries.lastIndex) {
                    HorizontalDivider(color = Color(0xFFF1F5F9))
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
            .padding(horizontal = 3.dp)
            .wrapContentSize(),
        contentAlignment = Alignment.Center
    ) {
        Box(
            modifier = Modifier
                .size(36.dp)
                .clip(CircleShape)
                .background(Color(0xFFF1F5F9))
                .clickable(onClick = onClick),
            contentAlignment = Alignment.Center
        ) {
            net.affscash.android.ui.dashboard.GradientIcon(
                imageVector = icon,
                contentDescription = "Header Icon",
                tint = Color(0xFF334155),
                modifier = Modifier.size(19.dp)
            )
        }
        if (count > 0) {
            Box(
                modifier = Modifier
                    .align(Alignment.TopEnd)
                    .offset(x = 5.dp, y = (-3).dp)
                    .defaultMinSize(minWidth = 18.dp, minHeight = 18.dp)
                    .background(badgeColor, CircleShape)
                    .padding(horizontal = 4.dp, vertical = 1.dp),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = if (count > 99) "99+" else count.toString(),
                    color = Color.White,
                    fontSize = 10.sp,
                    fontWeight = FontWeight.Bold,
                    maxLines = 1,
                    textAlign = TextAlign.Center
                )
            }
        }
    }
}
