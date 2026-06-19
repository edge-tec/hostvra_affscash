package net.affscash.android.ui.screens.admin.vpn

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
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

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminVpnLogScreen(
    onNavigateBack: () -> Unit,
    viewModel: AdminVpnLogViewModel = hiltViewModel()
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
            TopAppBar(
                title = { Text("VPN & Proxy Blocked Log") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    navigationIconContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            // Stats Section
            if (uiState.stats != null) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 8.dp),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    StatCard("TODAY", uiState.stats?.todayBlocked?.toString() ?: "0", MaterialTheme.colorScheme.error, Modifier.weight(1f))
                    StatCard("30 DAYS", uiState.stats?.totalLast30Days?.toString() ?: "0", MaterialTheme.colorScheme.primary, Modifier.weight(1f))
                    StatCard("VPN", uiState.stats?.vpnHostingCount?.toString() ?: "0", Color(0xFF673AB7), Modifier.weight(1f))
                    StatCard("PROXY", uiState.stats?.proxyCount?.toString() ?: "0", Color(0xFF9C27B0), Modifier.weight(1f))
                }
            } else if (uiState.isLoadingStats) {
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            }

            Spacer(modifier = Modifier.height(16.dp))

            // Filters
            var isFiltersExpanded by remember { mutableStateOf(false) }
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
            ) {
                Column(modifier = Modifier.padding(12.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Filters", fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                        IconButton(onClick = { isFiltersExpanded = !isFiltersExpanded }) {
                            Icon(if (isFiltersExpanded) Icons.Filled.KeyboardArrowUp else Icons.Filled.KeyboardArrowDown, "Toggle Filters")
                        }
                    }
                    if (isFiltersExpanded) {
                        var ip by remember { mutableStateOf(uiState.filterIp) }
                        var aff by remember { mutableStateOf(uiState.filterAffiliate) }
                        var type by remember { mutableStateOf(uiState.filterType) }
                        val types = listOf("All Types", "VPN", "Proxy", "Hosting")

                        OutlinedTextField(
                            value = ip,
                            onValueChange = { ip = it },
                            label = { Text("IP Address") },
                            modifier = Modifier.fillMaxWidth().padding(bottom = 8.dp),
                            singleLine = true
                        )
                        OutlinedTextField(
                            value = aff,
                            onValueChange = { aff = it },
                            label = { Text("Affiliate Name or ID") },
                            modifier = Modifier.fillMaxWidth().padding(bottom = 8.dp),
                            singleLine = true
                        )
                        // Simple dropdown text field alternative since Android doesn't have an easy native native dropdown without ExposedDropdownMenuBox
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
                                label = { Text("Detection Type") },
                                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = typeExpanded) },
                                modifier = Modifier.menuAnchor().fillMaxWidth()
                            )
                            ExposedDropdownMenu(
                                expanded = typeExpanded,
                                onDismissRequest = { typeExpanded = false }
                            ) {
                                types.forEach { selectionOption ->
                                    DropdownMenuItem(
                                        text = { Text(selectionOption) },
                                        onClick = {
                                            type = selectionOption
                                            typeExpanded = false
                                        }
                                    )
                                }
                            }
                        }

                        Button(
                            onClick = {
                                viewModel.updateFilters(ip, aff, if (type == "All Types") "" else type, "", "")
                                isFiltersExpanded = false
                            },
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Apply Filters")
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(16.dp))

            // Title & Clear Button
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "Blocked Attempts",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
                TextButton(
                    onClick = { viewModel.clearOldLogs() },
                    enabled = !uiState.isClearing
                ) {
                    Icon(Icons.Filled.Delete, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Clear Old Entries")
                }
            }
            HorizontalDivider()

            // List
            if (uiState.isLoadingLogs) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            } else if (uiState.error != null) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(uiState.error ?: "", color = MaterialTheme.colorScheme.error)
                }
            } else if (uiState.logs.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text("No blocked attempts found.", color = Color.Gray)
                }
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    items(uiState.logs) { log ->
                        VpnLogCard(log)
                    }
                }
            }
        }
    }
}

@Composable
fun StatCard(title: String, value: String, color: Color, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Column(
            modifier = Modifier.padding(8.dp).fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(title, fontSize = 13.sp, fontWeight = FontWeight.Bold, color = Color.Gray, maxLines = 1, overflow = TextOverflow.Ellipsis)
            Spacer(modifier = Modifier.height(2.dp))
            Text(value, fontSize = 112.sp, fontWeight = FontWeight.Bold, color = color)
        }
    }
}

@Composable
fun VpnLogCard(log: VpnLogItem) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = log.ipAddress,
                    fontWeight = FontWeight.Bold,
                    fontSize = 16.sp,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Badge(
                    containerColor = if (log.detectionType.equals("VPN", true) || log.detectionType.equals("Hosting", true)) Color(0xFF673AB7) else Color(0xFF9C27B0),
                    contentColor = Color.White
                ) {
                    Text(log.detectionType, fontSize = 12.sp, modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp))
                }
            }
            Spacer(modifier = Modifier.height(8.dp))
            
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(modifier = Modifier.weight(1f)) {
                    Text("DATE & TIME", fontSize = 10.sp, fontWeight = FontWeight.Bold, color = Color.Gray)
                    Text(log.blockedAt, fontSize = 13.sp)
                }
                Column(modifier = Modifier.weight(1f)) {
                    Text("COUNTRY", fontSize = 10.sp, fontWeight = FontWeight.Bold, color = Color.Gray)
                    Text(log.country.ifEmpty { "Unknown" }, fontSize = 13.sp)
                }
            }
            Spacer(modifier = Modifier.height(8.dp))

            if (log.affName != null || log.affiliateCode != null) {
                Text("AFFILIATE", fontSize = 10.sp, fontWeight = FontWeight.Bold, color = Color.Gray)
                Text("${log.affName ?: "Unknown"} (${log.affiliateCode ?: "N/A"})", fontSize = 14.sp, color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.SemiBold)
                Spacer(modifier = Modifier.height(8.dp))
            }

            if (log.offerName != null) {
                Text("OFFER", fontSize = 10.sp, fontWeight = FontWeight.Bold, color = Color.Gray)
                Text("${log.offerName} (ID: ${log.offerId})", fontSize = 13.sp)
                Spacer(modifier = Modifier.height(8.dp))
            }
            
            Text("USER AGENT", fontSize = 10.sp, fontWeight = FontWeight.Bold, color = Color.Gray)
            Text(log.userAgent ?: "Unknown", fontSize = 12.sp, color = Color.DarkGray, maxLines = 2, overflow = TextOverflow.Ellipsis)
        }
    }
}
