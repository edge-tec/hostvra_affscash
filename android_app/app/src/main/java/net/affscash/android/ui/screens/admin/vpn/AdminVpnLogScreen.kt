package net.affscash.android.ui.screens.admin.vpn

import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.VpnLogItem
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminVpnLogScreen(
    onNavigateBack: () -> Unit,
    viewModel: AdminVpnLogViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    var isFiltersExpanded by remember { mutableStateOf(false) }

    LaunchedEffect(uiState.clearSuccessMessage) {
        uiState.clearSuccessMessage?.let { msg ->
            Toast.makeText(context, msg, Toast.LENGTH_SHORT).show()
            viewModel.clearMessage()
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(PremiumUI.PageBackground)
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
                            text = "VPN & Proxy Logs",
                            fontSize = 17.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF0F172A)
                        )
                        Text(
                            text = "Blocked attempts & proxy logs",
                            fontSize = 11.sp,
                            color = Color(0xFF64748B)
                        )
                    }
                }

                Surface(
                    onClick = { isFiltersExpanded = !isFiltersExpanded },
                    shape = RoundedCornerShape(12.dp),
                    color = if (isFiltersExpanded) Color(0xFFEEF2FF) else Color(0xFFF8FAFC),
                    border = BorderStroke(1.dp, Color(0xFFE2E8F0))
                ) {
                    Box(
                        modifier = Modifier.padding(8.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            Icons.Outlined.FilterList,
                            contentDescription = "Filter",
                            tint = Color(0xFF4F46E5),
                            modifier = Modifier.size(18.dp)
                        )
                    }
                }
            }
        }

        // Stats Section
        if (uiState.stats != null) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 12.dp, vertical = 4.dp),
                horizontalArrangement = Arrangement.spacedBy(6.dp)
            ) {
                VpnStatCard3D("TODAY", uiState.stats?.todayBlocked?.toString() ?: "0", Color(0xFFEF4444), Modifier.weight(1f))
                VpnStatCard3D("30 DAYS", uiState.stats?.totalLast30Days?.toString() ?: "0", Color(0xFF4F46E5), Modifier.weight(1f))
                VpnStatCard3D("VPN", uiState.stats?.vpnHostingCount?.toString() ?: "0", Color(0xFF8B5CF6), Modifier.weight(1f))
                VpnStatCard3D("PROXY", uiState.stats?.proxyCount?.toString() ?: "0", Color(0xFFD97706), Modifier.weight(1f))
            }
        } else if (uiState.isLoadingStats) {
            LinearProgressIndicator(modifier = Modifier.fillMaxWidth(), color = Color(0xFF4F46E5))
        }

        // Filters Card
        AnimatedVisibility(visible = isFiltersExpanded) {
            GlassCard(
                modifier = Modifier.padding(horizontal = 12.dp, vertical = 4.dp),
                elevation = 3.dp
            ) {
                var ip by remember { mutableStateOf(uiState.filterIp) }
                var aff by remember { mutableStateOf(uiState.filterAffiliate) }
                var type by remember { mutableStateOf(uiState.filterType) }
                val types = listOf("All Types", "VPN", "Proxy", "Hosting")

                Column(modifier = Modifier.fillMaxWidth()) {
                    OutlinedTextField(
                        value = ip,
                        onValueChange = { ip = it },
                        placeholder = { Text("Search IP Address", fontSize = 13.sp) },
                        modifier = Modifier.fillMaxWidth().padding(bottom = 6.dp),
                        singleLine = true,
                        shape = RoundedCornerShape(12.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFF4F46E5),
                            unfocusedBorderColor = Color(0xFFE2E8F0),
                            focusedContainerColor = Color(0xFFF8FAFC),
                            unfocusedContainerColor = Color(0xFFF8FAFC)
                        )
                    )
                    OutlinedTextField(
                        value = aff,
                        onValueChange = { aff = it },
                        placeholder = { Text("Affiliate Name or ID", fontSize = 13.sp) },
                        modifier = Modifier.fillMaxWidth().padding(bottom = 6.dp),
                        singleLine = true,
                        shape = RoundedCornerShape(12.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFF4F46E5),
                            unfocusedBorderColor = Color(0xFFE2E8F0),
                            focusedContainerColor = Color(0xFFF8FAFC),
                            unfocusedContainerColor = Color(0xFFF8FAFC)
                        )
                    )

                    var typeExpanded by remember { mutableStateOf(false) }
                    ExposedDropdownMenuBox(
                        expanded = typeExpanded,
                        onExpandedChange = { typeExpanded = !typeExpanded },
                        modifier = Modifier.fillMaxWidth().padding(bottom = 8.dp)
                    ) {
                        OutlinedTextField(
                            value = if (type.isEmpty()) "All Types" else type,
                            onValueChange = {},
                            readOnly = true,
                            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = typeExpanded) },
                            modifier = Modifier.menuAnchor().fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp),
                            colors = OutlinedTextFieldDefaults.colors(
                                focusedBorderColor = Color(0xFF4F46E5),
                                unfocusedBorderColor = Color(0xFFE2E8F0),
                                focusedContainerColor = Color(0xFFF8FAFC),
                                unfocusedContainerColor = Color(0xFFF8FAFC)
                            )
                        )
                        ExposedDropdownMenu(
                            expanded = typeExpanded,
                            onDismissRequest = { typeExpanded = false }
                        ) {
                            types.forEach { selectionOption ->
                                DropdownMenuItem(
                                    text = { Text(selectionOption) },
                                    onClick = {
                                        type = if (selectionOption == "All Types") "" else selectionOption
                                        typeExpanded = false
                                    }
                                )
                            }
                        }
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.End,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        TextButton(onClick = {
                            ip = ""
                            aff = ""
                            type = ""
                            viewModel.updateFilters("", "", "", "", "")
                        }) {
                            Text("Reset", color = Color(0xFF64748B))
                        }
                        Spacer(modifier = Modifier.width(6.dp))
                        Button(
                            onClick = { viewModel.updateFilters(ip, aff, type, "", "") },
                            shape = PremiumUI.ButtonShape,
                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                        ) {
                            Text("Apply Filters", fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }
        }

        // Section Title & Clear Action
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 6.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text("Blocked Attempts", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
            
            TextButton(
                onClick = { viewModel.clearOldLogs() },
                enabled = !uiState.isClearing
            ) {
                Icon(Icons.Outlined.DeleteSweep, contentDescription = null, modifier = Modifier.size(16.dp), tint = Color(0xFFDC2626))
                Spacer(modifier = Modifier.width(4.dp))
                Text("Clear Old Entries", fontSize = 12.sp, color = Color(0xFFDC2626), fontWeight = FontWeight.Bold)
            }
        }

        Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
            if (uiState.isLoadingLogs) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = Color(0xFF4F46E5))
                }
            } else if (uiState.error != null) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(uiState.error!!, color = Color(0xFFEF4444))
                }
            } else if (uiState.logs.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text("No blocked attempts found", color = Color(0xFF64748B))
                }
            } else {
                LazyColumn(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(horizontal = 12.dp),
                    contentPadding = PaddingValues(top = 2.dp, bottom = 80.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
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
fun VpnStatCard3D(title: String, value: String, accentColor: Color, modifier: Modifier = Modifier) {
    Surface(
        modifier = modifier,
        shape = RoundedCornerShape(14.dp),
        color = Color.White,
        shadowElevation = 2.dp,
        border = BorderStroke(1.dp, Color(0xFFE2E8F0))
    ) {
        Column(
            modifier = Modifier.padding(vertical = 10.dp, horizontal = 6.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(title, fontSize = 9.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF64748B))
            Spacer(modifier = Modifier.height(2.dp))
            Text(value, fontSize = 16.sp, fontWeight = FontWeight.ExtraBold, color = accentColor)
        }
    }
}

@Composable
fun VpnLogCard3D(log: VpnLogItem) {
    GlassCard(elevation = 2.dp) {
        Column(modifier = Modifier.fillMaxWidth()) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = log.ipAddress,
                    fontSize = 14.sp,
                    fontWeight = FontWeight.ExtraBold,
                    color = Color(0xFF0F172A),
                    modifier = Modifier.weight(1f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                
                val isProxy = log.detectionType.contains("Proxy", ignoreCase = true)
                Surface(
                    shape = RoundedCornerShape(6.dp),
                    color = if (isProxy) Color(0xFFF3E8FF) else Color(0xFFFEE2E2)
                ) {
                    Text(
                        text = log.detectionType,
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                        fontSize = 11.sp,
                        fontWeight = FontWeight.ExtraBold,
                        color = if (isProxy) Color(0xFF7E22CE) else Color(0xFFDC2626)
                    )
                }
            }
            
            Spacer(modifier = Modifier.height(6.dp))
            
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("DATE & TIME", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                    Text(log.blockedAt, fontSize = 11.sp, color = Color(0xFF334155))
                }
                Column {
                    Text("COUNTRY", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                    Text(log.country.ifEmpty { "N/A" }, fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color(0xFF4F46E5))
                }
            }
            
            if (log.offerName != null) {
                Spacer(modifier = Modifier.height(6.dp))
                Text("OFFER", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                Text(log.offerName, fontSize = 11.sp, color = Color(0xFF334155), maxLines = 1, overflow = TextOverflow.Ellipsis)
            }
            
            if (log.userAgent != null) {
                Spacer(modifier = Modifier.height(4.dp))
                Text("USER AGENT", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                Text(log.userAgent, fontSize = 10.sp, color = Color(0xFF64748B), maxLines = 2, overflow = TextOverflow.Ellipsis)
            }
        }
    }
}
