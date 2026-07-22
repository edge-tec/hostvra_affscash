package net.affscash.android.ui.screens.admin.reports

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ArrowDropDown
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import net.affscash.android.ui.components.CustomDropdownMenu
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI
import kotlinx.serialization.json.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminReportsScreen(
    viewModel: AdminReportsViewModel = viewModel(),
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(uiState.error) {
        uiState.error?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearError()
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .background(PremiumUI.PageBackground)
                .padding(padding)
        ) {
            // 3D Glass Header Bar
            Surface(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 12.dp, vertical = 8.dp),
                shape = PremiumUI.CardShape,
                color = Color.White,
                shadowElevation = 2.dp,
                border = PremiumUI.GlassBorder
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 14.dp, vertical = 12.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        IconButton(onClick = onNavigateBack) {
                            Icon(Icons.Default.ArrowBack, contentDescription = "Back", tint = Color(0xFF0F172A))
                        }
                        Box(
                            modifier = Modifier
                                .size(38.dp)
                                .clip(RoundedCornerShape(10.dp))
                                .background(PremiumUI.HeaderGradient),
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(
                                Icons.Outlined.Assessment,
                                contentDescription = null,
                                tint = Color.White,
                                modifier = Modifier.size(20.dp)
                            )
                        }
                        Spacer(modifier = Modifier.width(10.dp))
                        Column {
                            Text(
                                text = "Performance Reports",
                                fontSize = 17.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF0F172A)
                            )
                            Text(
                                text = "Detailed analytics & metrics",
                                fontSize = 11.sp,
                                color = Color(0xFF64748B)
                            )
                        }
                    }

                    IconButton(onClick = { viewModel.loadReport() }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Refresh", tint = Color(0xFF4F46E5))
                    }
                }
            }

            net.affscash.android.ui.components.DateRangeFilterComponent(
                state = uiState.dateRangeState,
                onOptionSelected = { viewModel.setDateRangeOption(it) },
                onCustomRangeSelected = { start, end -> viewModel.setCustomDateRange(start, end) },
                modifier = Modifier.padding(horizontal = 12.dp, vertical = 4.dp)
            )

            // Report Type Dropdown & Filter Toggle
            var reportTypeExpanded by remember { mutableStateOf(false) }
            val tabs = listOf(
                "performance" to "Performance",
                "clicks" to "Clicks",
                "conversions" to "Conversions",
                "rejected" to "Rejected",
                "pending" to "Pending",
                "autohide" to "Autohide",
                "offer_report" to "Offer Reports",
                "postback" to "Postback Log",
                "sl_clicks" to "SmartLink Clicks",
                "sl_conversions" to "SmartLink Conv",
                "sl_affiliates" to "SmartLink Affiliates"
            )
            val currentTabName = tabs.find { it.first == uiState.tab }?.second ?: "Report Type"

            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 12.dp, vertical = 4.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Box(modifier = Modifier.weight(1f)) {
                    Surface(
                        onClick = { reportTypeExpanded = true },
                        shape = RoundedCornerShape(12.dp),
                        color = Color.White,
                        border = BorderStroke(1.dp, Color(0xFFE2E8F0))
                    ) {
                        Row(
                            modifier = Modifier.fillMaxWidth().padding(horizontal = 12.dp, vertical = 10.dp),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(currentTabName, fontSize = 13.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                            Icon(Icons.Default.ArrowDropDown, contentDescription = null, tint = Color(0xFF64748B))
                        }
                    }

                    DropdownMenu(
                        expanded = reportTypeExpanded,
                        onDismissRequest = { reportTypeExpanded = false }
                    ) {
                        tabs.forEach { tabInfo ->
                            DropdownMenuItem(
                                text = { Text(tabInfo.second) },
                                onClick = { viewModel.updateTab(tabInfo.first); reportTypeExpanded = false }
                            )
                        }
                    }
                }
            }

            if (uiState.isLoading) {
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth(), color = Color(0xFF4F46E5))
            }

            // Stats KPI Cards
            uiState.totals?.let { totals ->
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .horizontalScroll(rememberScrollState())
                        .padding(horizontal = 12.dp, vertical = 6.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    ReportStatCard3D("Clicks", "${totals.clicks.toInt()}", Color(0xFF4F46E5), Modifier.width(110.dp))
                    ReportStatCard3D("Unique", "${totals.uclicks.toInt()}", Color(0xFF0284C7), Modifier.width(110.dp))
                    ReportStatCard3D("Conversions", "${totals.conversions.toInt()}", Color(0xFF10B981), Modifier.width(110.dp))
                    ReportStatCard3D("Payout", "$${"%.2f".format(totals.payout)}", Color(0xFFD97706), Modifier.width(120.dp))
                    ReportStatCard3D("Revenue", "$${"%.2f".format(totals.revenue)}", Color(0xFF059669), Modifier.width(120.dp))
                }
            }

            // Data List
            Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
                if (uiState.rows.isEmpty()) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Text("No report data available.", color = Color(0xFF64748B))
                    }
                } else {
                    LazyColumn(
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(horizontal = 12.dp),
                        contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        items(uiState.rows) { row ->
                            ReportRowCard3D(row)
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun ReportStatCard3D(title: String, value: String, accentColor: Color, modifier: Modifier = Modifier) {
    Surface(
        modifier = modifier,
        shape = RoundedCornerShape(14.dp),
        color = Color.White,
        shadowElevation = 2.dp,
        border = BorderStroke(1.dp, Color(0xFFE2E8F0))
    ) {
        Column(
            modifier = Modifier.padding(vertical = 10.dp, horizontal = 8.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(title, fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF64748B))
            Spacer(modifier = Modifier.height(2.dp))
            Text(value, fontSize = 15.sp, fontWeight = FontWeight.ExtraBold, color = accentColor)
        }
    }
}

@Composable
fun ReportRowCard3D(row: JsonObject) {
    val date = row["date"]?.jsonPrimitive?.contentOrNull ?: row["created_at"]?.jsonPrimitive?.contentOrNull ?: "Summary"
    val clicks = row["clicks"]?.jsonPrimitive?.contentOrNull ?: row["total_clicks"]?.jsonPrimitive?.contentOrNull ?: "0"
    val conv = row["conversions"]?.jsonPrimitive?.contentOrNull ?: row["total_conversions"]?.jsonPrimitive?.contentOrNull ?: "0"
    val profit = row["profit"]?.jsonPrimitive?.contentOrNull ?: row["revenue"]?.jsonPrimitive?.contentOrNull ?: "0.00"

    GlassCard(elevation = 2.dp) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(date, fontSize = 13.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF0F172A))
                Spacer(modifier = Modifier.height(4.dp))
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Surface(color = Color(0xFFF8FAFC), shape = RoundedCornerShape(6.dp), border = BorderStroke(1.dp, Color(0xFFE2E8F0))) {
                        Text("Clicks: $clicks", modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), fontSize = 11.sp, color = Color(0xFF475569))
                    }
                    Spacer(modifier = Modifier.width(6.dp))
                    Surface(color = Color(0xFFEEF2FF), shape = RoundedCornerShape(6.dp)) {
                        Text("Conv: $conv", modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color(0xFF4F46E5))
                    }
                }
            }

            Column(horizontalAlignment = Alignment.End) {
                Text("Profit / Rev", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                Text("$$profit", fontSize = 14.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF10B981))
            }
        }
    }
}
