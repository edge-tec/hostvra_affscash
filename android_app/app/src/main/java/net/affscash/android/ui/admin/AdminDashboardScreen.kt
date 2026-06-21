package net.affscash.android.ui.admin

import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.WindowInsetsSides
import androidx.compose.foundation.layout.only
import androidx.compose.foundation.layout.safeDrawing
import androidx.compose.foundation.layout.windowInsetsPadding


import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.AdminTopOffer
import net.affscash.android.data.model.AdminTopAffiliate
import net.affscash.android.data.model.AdminRecentConversion
import net.affscash.android.data.model.AdminDashboardData
import net.affscash.android.ui.dashboard.AdminDashboardUiState
import net.affscash.android.ui.dashboard.AdminDashboardViewModel
import net.affscash.android.ui.dashboard.DateFilter
import net.affscash.android.ui.dashboard.PremiumUI
import com.patrykandpatrick.vico.compose.axis.horizontal.rememberBottomAxis
import com.patrykandpatrick.vico.compose.axis.vertical.rememberStartAxis
import com.patrykandpatrick.vico.compose.chart.Chart
import com.patrykandpatrick.vico.compose.chart.line.lineChart
import com.patrykandpatrick.vico.core.entry.entryModelOf
import com.patrykandpatrick.vico.core.entry.FloatEntry

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminDashboardScreen(
    viewModel: AdminDashboardViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val selectedFilter by viewModel.selectedFilter.collectAsState()

    Box(modifier = Modifier.fillMaxSize().background(PremiumUI.BackgroundGradient).windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {
        when (val state = uiState) {
            is AdminDashboardUiState.Loading -> {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            }
            is AdminDashboardUiState.Error -> {
                Column(
                    modifier = Modifier.align(Alignment.Center),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Text(text = "Error: ${state.message}", color = MaterialTheme.colorScheme.error)
                    Spacer(modifier = Modifier.height(4.dp))
                    Button(onClick = { viewModel.loadDashboardData() }) {
                        Text("Retry")
                    }
                }
            }
            is AdminDashboardUiState.Success -> {
                val data = state.data.data
                if (data != null) {
                    LazyColumn(
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(horizontal = 8.dp),
                        contentPadding = PaddingValues(vertical = 8.dp)
                    ) {
                        item {
                            Text(
                                text = "Admin Analytics",
                                style = MaterialTheme.typography.headlineMedium.copy(brush = PremiumUI.PrimaryGradient),
                                fontWeight = FontWeight.ExtraBold,
                                modifier = Modifier.padding(bottom = 8.dp)
                            )
                        }

                        // Date Filters Row
                        item {
                            LazyRow(
                                horizontalArrangement = Arrangement.spacedBy(4.dp),
                                modifier = Modifier.padding(bottom = 8.dp)
                            ) {
                                items(DateFilter.values()) { filter ->
                                    FilterChip(
modifier = Modifier.height(28.dp),
                                        selected = filter == selectedFilter,
                                        onClick = { viewModel.setFilter(filter) },
                                        label = { Text(filter.label) }
                                    )
                                }
                            }
                        }

                        // KPI Grid
                        item {
                            AdminKpiGrid(data)
                            Spacer(modifier = Modifier.height(4.dp))
                        }

                        // Trend Chart
                        item {
                            Text(text = "Performance Trend", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(4.dp))
                            
                            val trendData = data.trend
                            if (trendData.labels.isNotEmpty() && trendData.clicks.isNotEmpty()) {
                                val entries = trendData.clicks.mapIndexed { index, value ->
                                    FloatEntry(x = index.toFloat(), y = value.toFloat())
                                }
                                val model = entryModelOf(entries)
                                Card(
                                    modifier = Modifier.fillMaxWidth().height(140.dp),
                                    shape = RoundedCornerShape(12.dp),
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
                                            modifier = Modifier.fillMaxSize().padding(4.dp)
                                        )
                                    }
                                }
                            } else {
                                Text("No trend data available for this period.")
                            }
                            Spacer(modifier = Modifier.height(4.dp))
                        }

                        // Top Offers
                        if (data.topOffers.isNotEmpty()) {
                            item {
                                Text(text = "Top Offers", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                                Spacer(modifier = Modifier.height(4.dp))
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(12.dp),
                                    colors = CardDefaults.cardColors(containerColor = Color.Transparent),
                                    elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
                                ) {
                                    Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
                                        Column(modifier = Modifier.padding(4.dp)) {
                                            data.topOffers.take(5).forEach { o ->
                                                Row(
                                                    modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
                                                    horizontalArrangement = Arrangement.SpaceBetween
                                                ) {
                                                    Text(o.name.ifBlank { "Offer #${o.id}" }, fontWeight = FontWeight.Medium, maxLines = 1, overflow = TextOverflow.Ellipsis, modifier = Modifier.weight(1f))
                                                    Text("C: ${o.clicks} | Cv: ${o.conversions} | $${(( o.profit )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", color = Color.Gray, modifier = Modifier.padding(start = 8.dp))
                                                }
                                                HorizontalDivider(color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.5f))
                                            }
                                        }
                                    }
                                }
                                Spacer(modifier = Modifier.height(4.dp))
                            }
                        }

                        // Top Affiliates
                        if (data.topAffiliates.isNotEmpty()) {
                            item {
                                Text(text = "Top Affiliates", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                                Spacer(modifier = Modifier.height(4.dp))
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(12.dp),
                                    colors = CardDefaults.cardColors(containerColor = Color.Transparent),
                                    elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
                                ) {
                                    Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
                                        Column(modifier = Modifier.padding(4.dp)) {
                                            data.topAffiliates.take(5).forEach { a ->
                                                Row(
                                                    modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
                                                    horizontalArrangement = Arrangement.SpaceBetween
                                                ) {
                                                    Text(a.name.ifBlank { "Affiliate #${a.id}" }, fontWeight = FontWeight.Medium, maxLines = 1, overflow = TextOverflow.Ellipsis, modifier = Modifier.weight(1f))
                                                    Text("C: ${a.clicks} | Cv: ${a.conversions} | P: $${(( a.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", color = Color.Gray, modifier = Modifier.padding(start = 8.dp))
                                                }
                                                HorizontalDivider(color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.5f))
                                            }
                                        }
                                    }
                                }
                                Spacer(modifier = Modifier.height(4.dp))
                            }
                        }

                        // Recent Conversions
                        if (data.recentConversions.isNotEmpty()) {
                            item {
                                Text(text = "Recent Conversions", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                                Spacer(modifier = Modifier.height(4.dp))
                                Card(
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(12.dp),
                                    colors = CardDefaults.cardColors(containerColor = Color.Transparent),
                                    elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
                                ) {
                                    Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
                                        Column(modifier = Modifier.padding(4.dp)) {
                                            data.recentConversions.take(15).forEach { rc ->
                                                Column(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
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
                                                    Text(rc.affiliateName ?: "Unknown Affiliate", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                                                    Text(rc.convertedAt, style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                                                }
                                                HorizontalDivider(color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.5f))
                                            }
                                        }
                                    }
                                }
                                Spacer(modifier = Modifier.height(4.dp))
                            }
                        }
                    }
                } else {
                    Text("No data available", modifier = Modifier.align(Alignment.Center))
                }
            }
        }
    }
}

@Composable
fun AdminKpiGrid(data: AdminDashboardData) {
    val kpis = data.kpis
    val summary = data.summary
    Column {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(4.dp)) {
            AdminKpiCard(
                title = "Total Clicks",
                value = kpis.clicks.toString(),
                subTitle = "Unique: ${kpis.uniqueClicks}",
                modifier = Modifier.weight(1f),
                color = Color(0xFF3B82F6)
            )
            AdminKpiCard(
                title = "Conversions",
                value = kpis.conversions.toString(),
                subTitle = "CR: ${kpis.cr}%",
                modifier = Modifier.weight(1f),
                color = Color(0xFF10B981)
            )
        }
        Spacer(modifier = Modifier.height(4.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(4.dp)) {
            AdminKpiCard(
                title = "Revenue",
                value = "$${(( kpis.revenue )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                subTitle = "Total generated",
                modifier = Modifier.weight(1f),
                color = Color(0xFF10B981)
            )
            AdminKpiCard(
                title = "Profit",
                value = "$${(( kpis.profit )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                subTitle = "Net profit",
                modifier = Modifier.weight(1f),
                color = Color(0xFF10B981)
            )
        }
        Spacer(modifier = Modifier.height(4.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(4.dp)) {
            AdminKpiCard(
                title = "Platform Affiliates",
                value = summary.totalAffiliates.toString(),
                subTitle = "Pending: ${summary.pendingAffiliates}",
                modifier = Modifier.weight(1f),
                color = Color(0xFF6366F1)
            )
            AdminKpiCard(
                title = "Fraud Rate",
                value = "${kpis.fraudConvPct}%",
                subTitle = "${kpis.fraudConv} Conversions",
                modifier = Modifier.weight(1f),
                color = if (kpis.fraudConvPct > 10.0) Color(0xFFEF4444) else Color(0xFFF59E0B)
            )
        }
    }
}

@Composable
fun AdminKpiCard(
    title: String,
    value: String,
    subTitle: String,
    color: Color,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxSize()) {
            Column(modifier = Modifier.padding(4.dp)) {
                Text(title, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Spacer(modifier = Modifier.height(4.dp))
                Text(value, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.ExtraBold, color = MaterialTheme.colorScheme.onSurface)
                Spacer(modifier = Modifier.height(2.dp))
                Text(subTitle, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
        }
    }
}
