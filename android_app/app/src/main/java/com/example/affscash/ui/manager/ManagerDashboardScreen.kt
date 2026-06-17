package com.example.affscash.ui.manager

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
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
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.affscash.R
import com.example.affscash.ui.dashboard.ManagerDashboardViewModel
import com.example.affscash.ui.dashboard.HeaderIconWithBadge
import com.example.affscash.ui.components.PeriodTabs
import com.example.affscash.ui.components.KpiGrid
import com.example.affscash.ui.components.TrendChart
import com.patrykandpatrick.vico.compose.axis.horizontal.rememberBottomAxis
import com.patrykandpatrick.vico.compose.axis.vertical.rememberStartAxis
import com.patrykandpatrick.vico.compose.chart.Chart
import com.patrykandpatrick.vico.compose.chart.line.lineChart
import com.patrykandpatrick.vico.core.chart.line.LineChart
import com.patrykandpatrick.vico.core.entry.entryModelOf
import com.patrykandpatrick.vico.core.entry.FloatEntry
import com.patrykandpatrick.vico.core.entry.ChartEntryModel
import kotlin.math.abs

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerDashboardScreen(
    viewModel: ManagerDashboardViewModel = hiltViewModel(),
    onNavigateToInvoices: () -> Unit = {},
    onNavigateToFraudAlerts: () -> Unit = {},
    onNavigateToChat: () -> Unit = {}
) {
    val uiState by viewModel.uiState.collectAsState()

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
                    val stats = uiState.stats
                    val balance = stats?.commissionBalance ?: 0.0
                    val counts = stats?.headerCounts
                    
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

                    // Notifications Icon
                    HeaderIconWithBadge(
                        icon = Icons.Outlined.Notifications,
                        count = counts?.unreadNotifs ?: 0,
                        badgeColor = Color(0xFFEF4444),
                        onClick = { android.widget.Toast.makeText(context, "Notifications coming soon", android.widget.Toast.LENGTH_SHORT).show() }
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
                    containerColor = MaterialTheme.colorScheme.surface,
                    titleContentColor = MaterialTheme.colorScheme.onSurface
                )
            )
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
                    Spacer(modifier = Modifier.height(24.dp))
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
fun KpiGrid(stats: com.example.affscash.data.model.ManagerDashboardData) {
    Column {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            KpiCard(
                title = "Managed Affiliates",
                value = stats.totalAffiliates.toString(),
                subTitle = "Under your management",
                modifier = Modifier.weight(1f),
                color = Color(0xFF3B82F6)
            )
            KpiCard(
                title = "Total Clicks",
                value = stats.clicks.toString(),
                subTitle = "Unique: ${stats.unique}",
                trend = stats.trend?.clicks,
                modifier = Modifier.weight(1f),
                color = Color(0xFF3B82F6)
            )
        }
        Spacer(modifier = Modifier.height(8.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            KpiCard(
                title = "Conversions",
                value = stats.conv.toString(),
                subTitle = "CR: ${stats.cr}%",
                trend = stats.trend?.conv,
                modifier = Modifier.weight(1f),
                color = Color(0xFF10B981)
            )
            KpiCard(
                title = "Fraud Conversion %",
                value = "${stats.fraudConvPct}%",
                subTitle = "${stats.fraudConv} Fraud Conversions",
                trend = stats.trend?.fraudConvPct,
                modifier = Modifier.weight(1f),
                color = Color(0xFFEF4444)
            )
        }
        Spacer(modifier = Modifier.height(8.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            val scoreColor = when {
                stats.fraudScoreAverage >= 75 -> Color(0xFFEF4444)
                stats.fraudScoreAverage >= 40 -> Color(0xFFF59E0B)
                else -> Color(0xFF10B981)
            }
            KpiCard(
                title = "IPQS Fraud Score",
                value = "${stats.fraudScoreAverage} /100",
                subTitle = "Real-time average",
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
    trend: Double? = null,
    color: Color,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(title, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(modifier = Modifier.height(4.dp))
            Text(value, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold, color = MaterialTheme.colorScheme.onSurface)
            Spacer(modifier = Modifier.height(2.dp))
            Text(subTitle, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            
            trend?.let {
                Spacer(modifier = Modifier.height(6.dp))
                val trendColor = if (it > 0) Color(0xFF059669) else if (it < 0) Color(0xFFDC2626) else Color.Gray
                val trendBg = if (it > 0) Color(0xFFD1FAE5) else if (it < 0) Color(0xFFFEE2E2) else Color(0xFFF3F4F6)
                val trendText = if (it > 0) "▲ ${abs(it)}% vs prev" else if (it < 0) "▼ ${abs(it)}% vs prev" else "—"
                
                Surface(
                    color = trendBg,
                    shape = RoundedCornerShape(16.dp)
                ) {
                    Text(
                        trendText,
                        color = trendColor,
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp)
                    )
                }
            }
        }
    }
}
