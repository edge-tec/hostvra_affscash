package net.affscash.android.ui.screens.admin.fraud

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.AdminFraudConversion
import net.affscash.android.ui.components.CustomDropdownMenu
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminFraudReportScreen(
    viewModel: AdminFraudReportViewModel = hiltViewModel(),
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(uiState.error) {
        uiState.error?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearMessages()
        }
    }
    LaunchedEffect(uiState.successMessage) {
        uiState.successMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearMessages()
        }
    }

    // Periodic live tick polling
    LaunchedEffect(Unit) {
        while (true) {
            kotlinx.coroutines.delay(10000)
            if ((uiState.stats?.pendingCheck ?: 0) > 0) {
                viewModel.triggerLiveCheck()
            }
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
                                Icons.Outlined.Shield,
                                contentDescription = null,
                                tint = Color.White,
                                modifier = Modifier.size(20.dp)
                            )
                        }
                        Spacer(modifier = Modifier.width(10.dp))
                        Column {
                            Text(
                                text = "Fraud Score Report",
                                fontSize = 17.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF0F172A)
                            )
                            Text(
                                text = "Monitor & audit conversion risk scores",
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
                modifier = Modifier.padding(horizontal = 12.dp, vertical = 2.dp)
            )

            if (uiState.isLoading && uiState.conversions.isEmpty()) {
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth(), color = Color(0xFF4F46E5))
            }

            // 3D Stats Cards
            uiState.stats?.let { stats ->
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 12.dp, vertical = 4.dp),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    StatCard3D("Total", stats.totalConversions.toString(), Modifier.weight(1f), Color(0xFF0F172A))
                    StatCard3D("Pending", stats.pendingCheck.toString(), Modifier.weight(1f), Color(0xFFD97706))
                    StatCard3D("Avg Score", "${stats.avgFraudScore}", Modifier.weight(1f), Color(0xFF4F46E5))
                    StatCard3D("High Risk", stats.highRisk.toString(), Modifier.weight(1f), Color(0xFFDC2626))
                }
            }

            // Filters Button
            var showFilters by remember { mutableStateOf(false) }
            Button(
                onClick = { showFilters = !showFilters },
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 12.dp, vertical = 4.dp)
                    .height(42.dp),
                shape = PremiumUI.ButtonShape,
                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
            ) {
                Icon(Icons.Default.FilterList, contentDescription = null, modifier = Modifier.size(16.dp))
                Spacer(Modifier.width(6.dp))
                Text(if (showFilters) "Hide Filters" else "Show Filters", fontWeight = FontWeight.Bold)
            }

            if (showFilters) {
                GlassCard(
                    modifier = Modifier.padding(horizontal = 12.dp, vertical = 2.dp),
                    elevation = 2.dp
                ) {
                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        CustomDropdownMenu(
                            options = listOf("all" to "All Status", "pending" to "Pending", "approved" to "Approved", "rejected" to "Rejected"),
                            selectedOption = uiState.status,
                            onOptionSelected = { viewModel.updateFilter(status = it) },
                            label = "Status",
                            modifier = Modifier.weight(1f)
                        )
                        CustomDropdownMenu(
                            options = listOf("converted_at" to "Date", "fraud_score" to "Fraud Score"),
                            selectedOption = uiState.sort,
                            onOptionSelected = { viewModel.updateFilter(sort = it) },
                            label = "Sort By",
                            modifier = Modifier.weight(1f)
                        )
                    }
                }
            }

            // List Area
            if (uiState.conversions.isEmpty() && !uiState.isLoading) {
                Box(modifier = Modifier.fillMaxWidth().weight(1f), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Icon(Icons.Outlined.Shield, contentDescription = null, modifier = Modifier.size(48.dp), tint = Color(0xFF94A3B8))
                        Spacer(modifier = Modifier.height(6.dp))
                        Text("No conversions found matching criteria.", fontSize = 14.sp, color = Color(0xFF64748B))
                    }
                }
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxWidth().weight(1f).padding(horizontal = 12.dp),
                    contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    items(uiState.conversions) { conv ->
                        ConversionFraudCard3D(
                            conversion = conv,
                            onApprove = { viewModel.updateConversionStatus(conv.conversionId, "approved") },
                            onReject = { reason -> viewModel.updateConversionStatus(conv.conversionId, "rejected", reason) }
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun StatCard3D(label: String, value: String, modifier: Modifier = Modifier, valueColor: Color = Color(0xFF0F172A)) {
    Surface(
        modifier = modifier,
        shape = RoundedCornerShape(12.dp),
        color = Color.White,
        border = BorderStroke(1.dp, Color(0xFFE2E8F0)),
        shadowElevation = 1.dp
    ) {
        Column(
            modifier = Modifier.padding(vertical = 8.dp, horizontal = 4.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(label, fontSize = 10.sp, fontWeight = FontWeight.Bold, color = Color(0xFF64748B))
            Spacer(modifier = Modifier.height(2.dp))
            Text(
                value,
                fontSize = 16.sp,
                fontWeight = FontWeight.ExtraBold,
                color = valueColor
            )
        }
    }
}

@Composable
fun ConversionFraudCard3D(
    conversion: AdminFraudConversion,
    onApprove: () -> Unit,
    onReject: (String) -> Unit
) {
    var showRejectDialog by remember { mutableStateOf(false) }
    val isHighRisk = conversion.fraudScore != null && conversion.fraudScore >= 75

    GlassCard(
        modifier = Modifier.fillMaxWidth(),
        elevation = 2.dp
    ) {
        Column(modifier = Modifier.fillMaxWidth()) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Surface(
                    shape = RoundedCornerShape(6.dp),
                    color = Color(0xFFEEF2FF)
                ) {
                    Text(
                        text = "ID: ${conversion.conversionId.take(8)}...",
                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF4F46E5)
                    )
                }

                StatusBadge3D(conversion.status)
            }

            Spacer(Modifier.height(6.dp))
            Text("Affiliate: ${conversion.affName ?: "N/A"} (${conversion.affiliateCode ?: ""})", fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFF0F172A))
            Text("Offer: ${conversion.offerName ?: "Custom URL"}", fontSize = 12.sp, color = Color(0xFF334155))
            Text("IP: ${conversion.ipAddress ?: "N/A"}", fontSize = 11.sp, color = Color(0xFF64748B))
            Text("Date: ${conversion.convertedAt ?: "N/A"}", fontSize = 11.sp, color = Color(0xFF94A3B8))
            
            Spacer(Modifier.height(8.dp))
            HorizontalDivider(color = Color(0xFFF1F5F9))
            Spacer(Modifier.height(6.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column {
                    Text("IPQS Score", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                    val scoreText = conversion.fraudScore?.toString() ?: "Pending"
                    val scoreColor = when {
                        conversion.fraudScore == null -> Color(0xFF64748B)
                        conversion.fraudScore >= 75 -> Color(0xFFDC2626)
                        conversion.fraudScore >= 40 -> Color(0xFFD97706)
                        else -> Color(0xFF059669)
                    }
                    Text(scoreText, color = scoreColor, fontWeight = FontWeight.ExtraBold, fontSize = 13.sp)
                }
                
                Column(horizontalAlignment = Alignment.End) {
                    Text("IPQuery", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                    Text("${conversion.ipqueryRiskScore ?: 0} / ${conversion.ipqueryRiskLevel ?: "N/A"}", fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFF334155))
                }
            }
            
            Spacer(Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                if (conversion.status != "approved") {
                    OutlinedButton(
                        onClick = onApprove,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.height(32.dp),
                        border = BorderStroke(1.dp, Color(0xFF818CF8)),
                        contentPadding = PaddingValues(horizontal = 12.dp)
                    ) {
                        Text("Approve", fontSize = 11.sp, color = Color(0xFF4F46E5), fontWeight = FontWeight.Bold)
                    }
                    Spacer(modifier = Modifier.width(6.dp))
                }
                if (conversion.status != "rejected") {
                    Button(
                        onClick = { showRejectDialog = true },
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.height(32.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626)),
                        contentPadding = PaddingValues(horizontal = 12.dp)
                    ) {
                        Text("Reject", fontSize = 11.sp, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }

    if (showRejectDialog) {
        var reason by remember { mutableStateOf("") }
        AlertDialog(
            onDismissRequest = { showRejectDialog = false },
            title = { Text("Reject Conversion", fontWeight = FontWeight.Bold) },
            text = {
                OutlinedTextField(
                    value = reason,
                    onValueChange = { reason = it },
                    label = { Text("Reason (Optional)", fontSize = 12.sp) },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(10.dp)
                )
            },
            confirmButton = {
                Button(
                    onClick = {
                        onReject(reason)
                        showRejectDialog = false
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))
                ) {
                    Text("Confirm Reject", fontWeight = FontWeight.Bold)
                }
            },
            dismissButton = {
                TextButton(onClick = { showRejectDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }
}

@Composable
fun StatusBadge3D(status: String?) {
    val (color, textColor, text) = when (status?.lowercase()) {
        "approved" -> Triple(Color(0xFFD1FAE5), Color(0xFF059669), "Approved")
        "rejected" -> Triple(Color(0xFFFEE2E2), Color(0xFFDC2626), "Rejected")
        else -> Triple(Color(0xFFFEF3C7), Color(0xFFD97706), "Pending")
    }
    Surface(
        color = color,
        shape = RoundedCornerShape(6.dp)
    ) {
        Text(
            text = text.uppercase(),
            modifier = Modifier.padding(horizontal = 7.dp, vertical = 3.dp),
            color = textColor,
            fontSize = 10.sp,
            fontWeight = FontWeight.ExtraBold
        )
    }
}
