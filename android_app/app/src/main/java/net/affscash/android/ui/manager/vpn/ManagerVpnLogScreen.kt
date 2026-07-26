package net.affscash.android.ui.manager.vpn

import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.VpnLogItem
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI
import net.affscash.android.ui.dashboard.StatusBadge

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerVpnLogScreen(
    onNavigateBack: () -> Unit,
    viewModel: ManagerVpnLogViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    // Observe clear success message
    LaunchedEffect(uiState.clearSuccessMessage) {
        uiState.clearSuccessMessage?.let { msg ->
            Toast.makeText(context, msg, Toast.LENGTH_SHORT).show()
            viewModel.clearMessage()
        }
    }

    Scaffold(
        topBar = {
            Surface(
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.background,
                shadowElevation = 2.dp
            ) {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(start = 8.dp, end = 24.dp, top = 8.dp, bottom = 8.dp)
                    ) {
                        IconButton(onClick = onNavigateBack) {
                            Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                        }
                        Text(
                            text = "VPN & Proxy Blocked Log",
                            style = PremiumUI.HeaderStyle,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                    }
                }
            }
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .background(PremiumUI.PageBackground)
        ) {
            // 3D Stats Cards Section
            if (uiState.stats != null) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 10.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    StatCard3D("TODAY", uiState.stats?.todayBlocked?.toString() ?: "0", Color(0xFFEF4444), Modifier.weight(1f))
                    StatCard3D("30 DAYS", uiState.stats?.totalLast30Days?.toString() ?: "0", Color(0xFF6366F1), Modifier.weight(1f))
                    StatCard3D("VPN", uiState.stats?.vpnHostingCount?.toString() ?: "0", Color(0xFF8B5CF6), Modifier.weight(1f))
                    StatCard3D("PROXY", uiState.stats?.proxyCount?.toString() ?: "0", Color(0xFFD946EF), Modifier.weight(1f))
                }
            } else if (uiState.isLoadingStats) {
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            }

            Spacer(modifier = Modifier.height(4.dp))

            // Expandable Filters Card
            var isFiltersExpanded by remember { mutableStateOf(false) }
            GlassCard(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 4.dp)
            ) {
                Column(modifier = Modifier.padding(12.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Filters & Search", fontWeight = FontWeight.ExtraBold, fontSize = 13.sp, color = Color(0xFF1E293B))
                        IconButton(onClick = { isFiltersExpanded = !isFiltersExpanded }) {
                            Icon(
                                if (isFiltersExpanded) Icons.Filled.KeyboardArrowUp else Icons.Filled.KeyboardArrowDown,
                                contentDescription = "Toggle Filters",
                                tint = MaterialTheme.colorScheme.primary
                            )
                        }
                    }
                    if (isFiltersExpanded) {
                        Column(modifier = Modifier.padding(top = 8.dp)) {
                            // Search Filter Input
                            OutlinedTextField(
                                value = uiState.selectedAffiliateId?.toString() ?: "",
                                onValueChange = { val id = it.toIntOrNull(); viewModel.setAffiliateFilter(id) },
                                label = { Text("Filter by Affiliate ID") },
                                modifier = Modifier.fillMaxWidth(),
                                singleLine = true,
                                shape = PremiumUI.ButtonShape
                            )
                            Spacer(modifier = Modifier.height(8.dp))
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.End
                            ) {
                                Button(
                                    onClick = { viewModel.clearFilters() },
                                    shape = PremiumUI.ButtonShape,
                                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
                                ) {
                                    Text("Reset Filters", color = MaterialTheme.colorScheme.onSurfaceVariant, fontSize = 12.sp)
                                }
                            }
                        }
                    }
                }
            }

            // Clear Log Button Header
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "Blocked Attempts (${uiState.logs.size})",
                    fontSize = 13.sp,
                    fontWeight = FontWeight.ExtraBold,
                    color = Color(0xFF475569)
                )
                TextButton(
                    onClick = { viewModel.clearVpnLogs() }
                ) {
                    Icon(Icons.Default.Delete, contentDescription = null, tint = Color(0xFFEF4444), modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Clear Old Entries", fontSize = 11.sp, color = Color(0xFFEF4444), fontWeight = FontWeight.Bold)
                }
            }

            if (uiState.isLoadingLogs) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            } else if (uiState.logs.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text("No blocked attempts found for your affiliates.", color = Color.Gray, fontSize = 13.sp)
                }
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(horizontal = 16.dp, vertical = 4.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    items(uiState.logs) { log ->
                        VpnLogCard3D(log)
                    }
                }
            }
        }
    }
}

@Composable
private fun StatCard3D(title: String, value: String, color: Color, modifier: Modifier = Modifier) {
    Surface(
        modifier = modifier.height(68.dp),
        shape = PremiumUI.CardShape,
        color = Color.White,
        shadowElevation = 3.dp,
        border = PremiumUI.Card3DBorder
    ) {
        Column(
            modifier = Modifier
                .background(PremiumUI.CardGradient)
                .padding(8.dp)
                .fillMaxSize(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Text(value, fontSize = 18.sp, fontWeight = FontWeight.Black, color = color)
            Spacer(modifier = Modifier.height(2.dp))
            Text(title, fontSize = 9.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF64748B), maxLines = 1, overflow = TextOverflow.Ellipsis)
        }
    }
}

@Composable
private fun VpnLogCard3D(log: VpnLogItem) {
    GlassCard(
        modifier = Modifier.fillMaxWidth()
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = log.ipAddress,
                    fontWeight = FontWeight.Black,
                    fontSize = 15.sp,
                    color = Color(0xFF0F172A)
                )
                
                val isVpn = log.detectionType.equals("VPN", true) || log.detectionType.equals("Hosting", true)
                Surface(
                    color = if (isVpn) Color(0xFFF3E8FF) else Color(0xFFFCE7F3),
                    border = BorderStroke(1.dp, if (isVpn) Color(0xFFC084FC) else Color(0xFFF472B6)),
                    shape = PremiumUI.PillShape
                ) {
                    Text(
                        text = log.detectionType,
                        fontSize = 11.sp,
                        fontWeight = FontWeight.ExtraBold,
                        color = if (isVpn) Color(0xFF7E22CE) else Color(0xFFBE185D),
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                    )
                }
            }
            Spacer(modifier = Modifier.height(6.dp))
            
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(modifier = Modifier.weight(1f)) {
                    Text("DATE & TIME", fontSize = 9.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF94A3B8))
                    Text(log.blockedAt, fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFF334155))
                }
                Column(modifier = Modifier.weight(1f)) {
                    Text("COUNTRY", fontSize = 9.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF94A3B8))
                    Text(log.country.ifEmpty { "Unknown" }, fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFF334155))
                }
            }
            Spacer(modifier = Modifier.height(6.dp))

            if (log.affName != null || log.affiliateCode != null) {
                Text("AFFILIATE", fontSize = 9.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF94A3B8))
                Text(
                    "${log.affName ?: "Unknown"} (${log.affiliateCode ?: "N/A"})",
                    fontSize = 12.sp,
                    color = Color(0xFF4338CA),
                    fontWeight = FontWeight.Bold
                )
                Spacer(modifier = Modifier.height(6.dp))
            }

            if (log.offerName != null) {
                Text("OFFER", fontSize = 9.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF94A3B8))
                Text("${log.offerName} (ID: ${log.offerId})", fontSize = 12.sp, fontWeight = FontWeight.Medium, color = Color(0xFF1E293B))
                Spacer(modifier = Modifier.height(6.dp))
            }
            
            Text("USER AGENT", fontSize = 9.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF94A3B8))
            Text(
                log.userAgent ?: "Unknown",
                fontSize = 11.sp,
                color = Color(0xFF64748B),
                maxLines = 2,
                overflow = TextOverflow.Ellipsis
            )
        }
    }
}
