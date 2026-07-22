package net.affscash.android.ui.admin

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.Email
import androidx.compose.material.icons.outlined.Notifications
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.patrykandpatrick.vico.compose.axis.horizontal.rememberBottomAxis
import com.patrykandpatrick.vico.compose.axis.vertical.rememberStartAxis
import com.patrykandpatrick.vico.compose.chart.Chart
import com.patrykandpatrick.vico.compose.chart.line.lineChart
import com.patrykandpatrick.vico.core.entry.FloatEntry
import com.patrykandpatrick.vico.core.entry.entryModelOf
import net.affscash.android.R
import net.affscash.android.data.model.AdminDashboardData
import net.affscash.android.ui.dashboard.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminDashboardScreen(
    viewModel: AdminDashboardViewModel = hiltViewModel(),
    onNavigateToNotifications: () -> Unit = {},
    onNavigateToChat: () -> Unit = {},
    onNavigateToOfferApprovals: () -> Unit = {}
) {
    val uiState by viewModel.uiState.collectAsState()
    val selectedFilter by viewModel.selectedFilter.collectAsState()

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(PremiumUI.BackgroundGradient)
            .windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))
    ) {
        when (val state = uiState) {
            is AdminDashboardUiState.Loading -> {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center), color = Color(0xFF4F46E5))
            }
            is AdminDashboardUiState.Error -> {
                Column(
                    modifier = Modifier.align(Alignment.Center),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Text(text = "Error: ${state.message}", color = MaterialTheme.colorScheme.error)
                    Spacer(modifier = Modifier.height(8.dp))
                    Button(
                        onClick = { viewModel.loadDashboardData() },
                        shape = PremiumUI.ButtonShape,
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                    ) {
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
                            .padding(horizontal = 12.dp),
                        contentPadding = PaddingValues(top = 8.dp, bottom = 80.dp)
                    ) {
                        // Header Bar
                        item {
                            Surface(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(bottom = 12.dp),
                                shape = PremiumUI.CardShape,
                                color = Color.White.copy(alpha = 0.90f),
                                shadowElevation = 6.dp,
                                border = PremiumUI.GlassBorder
                            ) {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(horizontal = 14.dp, vertical = 10.dp),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Image(
                                        painter = painterResource(id = R.drawable.logo),
                                        contentDescription = "AffsCash Logo",
                                        modifier = Modifier.height(38.dp)
                                    )
                                    Row(
                                        verticalAlignment = Alignment.CenterVertically,
                                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                                    ) {
                                        val counts = data.headerCounts
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

                                        val liveChatsCount = if (badgeManager != null) {
                                            badgeManager.unreadChats.collectAsState(initial = counts?.unreadChats ?: 0).value
                                        } else {
                                            counts?.unreadChats ?: 0
                                        }
                                        HeaderIconWithBadge(
                                            icon = Icons.Outlined.Email,
                                            count = liveChatsCount,
                                            badgeColor = Color(0xFF3B82F6),
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

                        // 3D Segmented Date Filters
                        item {
                            Segmented3DDateFilter(
                                options = DateFilter.values().toList(),
                                selectedOption = selectedFilter,
                                onOptionSelected = { viewModel.setFilter(it) },
                                modifier = Modifier.padding(bottom = 14.dp)
                            )
                        }

                        // 3D KPI Grid
                        item {
                            AdminKpiGrid3D(data)
                            Spacer(modifier = Modifier.height(14.dp))
                        }

                        // Performance Trend Chart
                        item {
                            Text(
                                text = "Performance Trend",
                                style = PremiumUI.HeaderStyle,
                                color = Color(0xFF1E293B),
                                modifier = Modifier.padding(bottom = 8.dp)
                            )
                            
                            val trendData = data.trend
                            if (trendData.labels.isNotEmpty() && trendData.clicks.isNotEmpty()) {
                                val entries = trendData.clicks.mapIndexed { index, value ->
                                    FloatEntry(x = index.toFloat(), y = value.toFloat())
                                }
                                val model = entryModelOf(entries)
                                GlassCard(elevation = 6.dp) {
                                    Box(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .height(180.dp)
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
                                            modifier = Modifier.fillMaxSize()
                                        )
                                    }
                                }
                            } else {
                                GlassCard {
                                    Text("No trend data available for this period.", style = PremiumUI.SecondaryText)
                                }
                            }
                            Spacer(modifier = Modifier.height(16.dp))
                        }

                        // Top Offers List
                        if (data.topOffers.isNotEmpty()) {
                            item {
                                Text(
                                    text = "Top Offers",
                                    style = PremiumUI.HeaderStyle,
                                    color = Color(0xFF1E293B),
                                    modifier = Modifier.padding(bottom = 8.dp)
                                )
                                GlassCard(elevation = 6.dp) {
                                    data.topOffers.take(5).forEachIndexed { idx, o ->
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(vertical = 8.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Row(
                                                verticalAlignment = Alignment.CenterVertically,
                                                modifier = Modifier.weight(1f)
                                            ) {
                                                Box(
                                                    modifier = Modifier
                                                        .size(32.dp)
                                                        .clip(CircleShape)
                                                        .background(PremiumUI.PrimaryGradient),
                                                    contentAlignment = Alignment.Center
                                                ) {
                                                    Text(
                                                        text = "${idx + 1}",
                                                        color = Color.White,
                                                        fontWeight = FontWeight.Bold,
                                                        fontSize = 12.sp
                                                    )
                                                }
                                                Spacer(modifier = Modifier.width(10.dp))
                                                Text(
                                                    text = o.name.ifBlank { "Offer #${o.id}" },
                                                    fontWeight = FontWeight.SemiBold,
                                                    fontSize = 14.sp,
                                                    color = Color(0xFF0F172A),
                                                    maxLines = 1,
                                                    overflow = TextOverflow.Ellipsis
                                                )
                                            }
                                            Text(
                                                text = "C: ${o.clicks} | Cv: ${o.conversions} | $${(( o.profit )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                                                style = PremiumUI.SecondaryText,
                                                fontSize = 11.sp
                                            )
                                        }
                                        if (idx < data.topOffers.take(5).size - 1) {
                                            HorizontalDivider(color = Color(0xFFE2E8F0))
                                        }
                                    }
                                }
                                Spacer(modifier = Modifier.height(16.dp))
                            }
                        }

                        // Top Affiliates List
                        if (data.topAffiliates.isNotEmpty()) {
                            item {
                                Text(
                                    text = "Top Affiliates",
                                    style = PremiumUI.HeaderStyle,
                                    color = Color(0xFF1E293B),
                                    modifier = Modifier.padding(bottom = 8.dp)
                                )
                                GlassCard(elevation = 6.dp) {
                                    data.topAffiliates.take(5).forEachIndexed { idx, a ->
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(vertical = 8.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Row(
                                                verticalAlignment = Alignment.CenterVertically,
                                                modifier = Modifier.weight(1f)
                                            ) {
                                                Box(
                                                    modifier = Modifier
                                                        .size(32.dp)
                                                        .clip(CircleShape)
                                                        .background(PremiumUI.EmeraldGradient),
                                                    contentAlignment = Alignment.Center
                                                ) {
                                                    Text(
                                                        text = "${idx + 1}",
                                                        color = Color.White,
                                                        fontWeight = FontWeight.Bold,
                                                        fontSize = 12.sp
                                                    )
                                                }
                                                Spacer(modifier = Modifier.width(10.dp))
                                                Text(
                                                    text = a.name.ifBlank { "Affiliate #${a.id}" },
                                                    fontWeight = FontWeight.SemiBold,
                                                    fontSize = 14.sp,
                                                    color = Color(0xFF0F172A),
                                                    maxLines = 1,
                                                    overflow = TextOverflow.Ellipsis
                                                )
                                            }
                                            Text(
                                                text = "C: ${a.clicks} | Cv: ${a.conversions} | $${(( a.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                                                style = PremiumUI.SecondaryText,
                                                fontSize = 11.sp
                                            )
                                        }
                                        if (idx < data.topAffiliates.take(5).size - 1) {
                                            HorizontalDivider(color = Color(0xFFE2E8F0))
                                        }
                                    }
                                }
                                Spacer(modifier = Modifier.height(16.dp))
                            }
                        }

                        // Recent Conversions List
                        if (data.recentConversions.isNotEmpty()) {
                            item {
                                Text(
                                    text = "Recent Conversions",
                                    style = PremiumUI.HeaderStyle,
                                    color = Color(0xFF1E293B),
                                    modifier = Modifier.padding(bottom = 8.dp)
                                )
                                GlassCard(elevation = 6.dp) {
                                    data.recentConversions.take(10).forEachIndexed { idx, rc ->
                                        Column(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(vertical = 6.dp)
                                        ) {
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.SpaceBetween,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                StatusBadge(status = rc.status)
                                                Text(
                                                    text = "$${(( rc.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                                                    fontWeight = FontWeight.Bold,
                                                    color = Color(0xFF059669),
                                                    fontSize = 13.sp
                                                )
                                            }
                                            Spacer(modifier = Modifier.height(4.dp))
                                            Text(
                                                text = rc.offerName ?: "Offer #${rc.id}",
                                                fontWeight = FontWeight.SemiBold,
                                                fontSize = 13.sp,
                                                color = Color(0xFF0F172A)
                                            )
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.SpaceBetween
                                            ) {
                                                Text(
                                                    text = rc.affiliateName ?: "Unknown Affiliate",
                                                    style = PremiumUI.SecondaryText,
                                                    fontSize = 11.sp
                                                )
                                                Text(
                                                    text = rc.convertedAt,
                                                    style = PremiumUI.SecondaryText,
                                                    fontSize = 11.sp
                                                )
                                            }
                                        }
                                        if (idx < data.recentConversions.take(10).size - 1) {
                                            HorizontalDivider(color = Color(0xFFE2E8F0))
                                        }
                                    }
                                }
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
fun AdminKpiGrid3D(data: AdminDashboardData) {
    val kpis = data.kpis
    val summary = data.summary
    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            KPICard3D(
                title = "Total Clicks",
                value = kpis.clicks.toString(),
                subtitle = "Unique: ${kpis.uniqueClicks}",
                icon = Icons.Default.TouchApp,
                iconGradient = PremiumUI.CyanGradient,
                modifier = Modifier.weight(1f)
            )
            KPICard3D(
                title = "Conversions",
                value = kpis.conversions.toString(),
                subtitle = "CR: ${kpis.cr}%",
                icon = Icons.Default.CheckCircle,
                iconGradient = PremiumUI.EmeraldGradient,
                modifier = Modifier.weight(1f)
            )
        }
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            KPICard3D(
                title = "Revenue",
                value = "$${(( kpis.revenue )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                subtitle = "Total generated",
                icon = Icons.Default.AttachMoney,
                iconGradient = PremiumUI.PrimaryGradient,
                modifier = Modifier.weight(1f)
            )
            KPICard3D(
                title = "Profit",
                value = "$${(( kpis.profit )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                subtitle = "Net profit",
                icon = Icons.Default.AccountBalanceWallet,
                iconGradient = PremiumUI.PurpleGradient,
                modifier = Modifier.weight(1f)
            )
        }
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            KPICard3D(
                title = "Platform Affiliates",
                value = summary.totalAffiliates.toString(),
                subtitle = "Pending: ${summary.pendingAffiliates}",
                icon = Icons.Default.People,
                iconGradient = PremiumUI.AmberGradient,
                modifier = Modifier.weight(1f)
            )
            KPICard3D(
                title = "Fraud Rate",
                value = "${kpis.fraudConvPct}%",
                subtitle = "${kpis.fraudConv} Conversions",
                icon = Icons.Default.Security,
                iconGradient = if (kpis.fraudConvPct > 10.0) PremiumUI.RoseGradient else PremiumUI.AmberGradient,
                modifier = Modifier.weight(1f)
            )
        }
    }
}
