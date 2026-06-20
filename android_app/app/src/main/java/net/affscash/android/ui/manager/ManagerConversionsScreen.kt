package net.affscash.android.ui.manager

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Assessment
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.VpnKey
import androidx.compose.material.icons.filled.Router
import androidx.compose.material.icons.outlined.Assessment
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.runtime.remember
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.compose.foundation.clickable
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.Conversion
import net.affscash.android.ui.conversions.ManagerConversionsUiState
import net.affscash.android.ui.conversions.ManagerConversionsViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerConversionsScreen(
    viewModel: ManagerConversionsViewModel = hiltViewModel(),
    onNavigateToDuplicates: () -> Unit = {},
    onNavigateToFraud: () -> Unit = {}
) {
    val uiState by viewModel.uiState.collectAsState()
    val statusFilter by viewModel.statusFilter.collectAsState()
    val searchQuery by viewModel.searchQuery.collectAsState()

    Scaffold(
        topBar = {
            Surface(
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.6f),
                shape = RoundedCornerShape(bottomStart = 24.dp, bottomEnd = 24.dp)
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(start = 24.dp, end = 24.dp, top = 24.dp, bottom = 16.dp)
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Surface(
                                shape = RoundedCornerShape(12.dp),
                                color = MaterialTheme.colorScheme.primary,
                                modifier = Modifier.size(48.dp)
                            ) {
                                Icon(
                                    Icons.Outlined.Assessment,
                                    contentDescription = null,
                                    tint = MaterialTheme.colorScheme.onPrimary,
                                    modifier = Modifier.padding(12.dp)
                                )
                            }
                            Spacer(modifier = Modifier.width(16.dp))
                            Column {
                                Text(
                                    text = "Conversions",
                                    style = MaterialTheme.typography.headlineSmall,
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.onPrimaryContainer
                                )
                                Text(
                                    text = "All team conversions",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.8f)
                                )
                            }
                        }
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            IconButton(
                                onClick = onNavigateToFraud,
                                modifier = Modifier.background(MaterialTheme.colorScheme.surface.copy(alpha = 0.5f), RoundedCornerShape(12.dp))
                            ) {
                                Icon(Icons.Default.Warning, contentDescription = "Fraud", tint = MaterialTheme.colorScheme.primary)
                            }
                            IconButton(
                                onClick = onNavigateToDuplicates,
                                modifier = Modifier.background(MaterialTheme.colorScheme.surface.copy(alpha = 0.5f), RoundedCornerShape(12.dp))
                            ) {
                                Icon(Icons.Default.Assessment, contentDescription = "Duplicates", tint = MaterialTheme.colorScheme.primary)
                            }
                        }
                    }
                }
            }
        }
    ) { paddingValues ->
        Column(modifier = Modifier.fillMaxSize().padding(paddingValues)) {
            // Tabs
        ScrollableTabRow(
            selectedTabIndex = when(statusFilter) {
                "all" -> 0
                "pending" -> 1
                "approved" -> 2
                "rejected" -> 3
                else -> 0
            },
            edgePadding = 16.dp,
            modifier = Modifier.fillMaxWidth()
        ) {
            Tab(selected = statusFilter == "all", onClick = { viewModel.setStatusFilter("all") }, text = { Text("All") })
            Tab(selected = statusFilter == "pending", onClick = { viewModel.setStatusFilter("pending") }, text = { Text("Pending") })
            Tab(selected = statusFilter == "approved", onClick = { viewModel.setStatusFilter("approved") }, text = { Text("Approved") })
            Tab(selected = statusFilter == "rejected", onClick = { viewModel.setStatusFilter("rejected") }, text = { Text("Rejected") })
        }

        // Search Bar
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 8.dp)
                .height(44.dp)
                .background(
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                    shape = RoundedCornerShape(22.dp)
                )
                .padding(horizontal = 16.dp),
            contentAlignment = Alignment.CenterStart
        ) {
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxWidth()) {
                Icon(
                    Icons.Default.Search,
                    contentDescription = "Search",
                    modifier = Modifier.size(20.dp),
                    tint = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Spacer(modifier = Modifier.width(8.dp))
                androidx.compose.foundation.text.BasicTextField(
                    value = searchQuery,
                    onValueChange = { viewModel.setSearchQuery(it) },
                    singleLine = true,
                    textStyle = androidx.compose.ui.text.TextStyle(
                        fontSize = 14.sp,
                        color = MaterialTheme.colorScheme.onSurface
                    ),
                    modifier = Modifier.weight(1f),
                    decorationBox = { innerTextField ->
                        if (searchQuery.isEmpty()) {
                            Text("Search by Click ID...", fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f))
                        }
                        innerTextField()
                    }
                )
                if (searchQuery.isNotEmpty()) {
                    Spacer(modifier = Modifier.width(8.dp))
                    IconButton(
                        onClick = { viewModel.setSearchQuery("") },
                        modifier = Modifier.size(24.dp)
                    ) {
                        Icon(
                            Icons.Default.Clear,
                            contentDescription = "Clear",
                            modifier = Modifier.size(16.dp),
                            tint = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }
        }

        Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
            when (val state = uiState) {
                is ManagerConversionsUiState.Loading -> {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
                is ManagerConversionsUiState.Error -> {
                    Column(
                        modifier = Modifier.align(Alignment.Center),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(text = "Error: ${state.message}", color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadConversions() }) {
                            Text("Retry")
                        }
                    }
                }
                is ManagerConversionsUiState.Success -> {
                    val allConversions = state.data.data
                    val filteredConversions = allConversions.filter {
                        (statusFilter == "all" || it.status.equals(statusFilter, ignoreCase = true)) &&
                        (searchQuery.isBlank() || it.clickId.contains(searchQuery, ignoreCase = true))
                    }

                    if (filteredConversions.isNotEmpty()) {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(horizontal = 16.dp),
                            contentPadding = PaddingValues(vertical = 8.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            items(filteredConversions) { conversion ->
                                ManagerConversionItem(conversion = conversion)
                            }
                        }
                    } else {
                        Text("No conversions found", modifier = Modifier.align(Alignment.Center))
                    }
                }
                }
            }
        }
    }
}


@Composable
fun ManagerConversionItem(conversion: Conversion) {
    var expandClickId by remember { mutableStateOf(false) }

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(20.dp)
        ) {
            // Header Row
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Text(
                    text = conversion.offerName ?: "Unknown Offer",
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.weight(1f).padding(end = 8.dp),
                    color = MaterialTheme.colorScheme.onSurface
                )
                
                val statusLower = conversion.status.lowercase()
                val containerColor = when(statusLower) {
                    "approved" -> Color(0xFF10B981).copy(alpha = 0.15f)
                    "rejected" -> MaterialTheme.colorScheme.error.copy(alpha = 0.15f)
                    else -> Color(0xFFF59E0B).copy(alpha = 0.15f)
                }
                val contentColor = when(statusLower) {
                    "approved" -> Color(0xFF10B981)
                    "rejected" -> MaterialTheme.colorScheme.error
                    else -> Color(0xFFD97706)
                }
                
                Surface(
                    color = containerColor,
                    shape = RoundedCornerShape(8.dp)
                ) {
                    Text(
                        text = conversion.status.uppercase(),
                        color = contentColor,
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp),
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Bold
                    )
                }
            }
            
            Spacer(modifier = Modifier.height(16.dp))

            // Info Box
            Surface(
                color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f),
                shape = RoundedCornerShape(12.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Person, contentDescription = null, modifier = Modifier.size(16.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(text = "${conversion.affName} (${conversion.affiliateCode})", style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold)
                    }
                    Spacer(modifier = Modifier.height(8.dp))
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.VpnKey, contentDescription = null, modifier = Modifier.size(16.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(
                            text = if (expandClickId) conversion.clickId else if (conversion.clickId.length > 16) conversion.clickId.take(16) + "..." else conversion.clickId,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            fontFamily = androidx.compose.ui.text.font.FontFamily.Monospace,
                            modifier = Modifier.clickable { expandClickId = !expandClickId }
                        )
                    }
                    Spacer(modifier = Modifier.height(8.dp))
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Router, contentDescription = null, modifier = Modifier.size(16.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(modifier = Modifier.width(8.dp))
                        
                        val country = conversion.country?.takeIf { it.isNotBlank() && it.lowercase() != "unknown" } ?: "Unknown Country"
                        val state = conversion.region?.takeIf { it.isNotBlank() && it.lowercase() != "unknown" } ?: "Unknown State"
                        val city = conversion.city?.takeIf { it.isNotBlank() && it.lowercase() != "unknown" }
                        
                        val locStr = listOfNotNull(country, state, city).joinToString(" | ")
                        
                        Text(
                            text = "${conversion.ipAddress} | $locStr", 
                            style = MaterialTheme.typography.bodySmall, 
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            
            // Stats Row
            if (!conversion.source.isNullOrEmpty() || !conversion.deviceType.isNullOrEmpty() || !conversion.os.isNullOrEmpty()) {
                Spacer(modifier = Modifier.height(12.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    if (!conversion.source.isNullOrEmpty()) {
                        Column(modifier = Modifier.weight(1f)) {
                            Text(text = "Source", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                            Text(text = conversion.source, style = MaterialTheme.typography.bodyMedium)
                        }
                    }
                    if (!conversion.deviceType.isNullOrEmpty()) {
                        Column(modifier = Modifier.weight(1f)) {
                            Text(text = "Device", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                            Text(text = conversion.deviceType.replaceFirstChar { it.uppercase() }, style = MaterialTheme.typography.bodyMedium)
                        }
                    }
                    if (!conversion.os.isNullOrEmpty()) {
                        Column(modifier = Modifier.weight(1f), horizontalAlignment = Alignment.End) {
                            Text(text = "OS", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                            Text(text = conversion.os, style = MaterialTheme.typography.bodyMedium)
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(16.dp))

            // Footer Row
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Bottom
            ) {
                Column {
                    val ipqsScore = conversion.fraudScore ?: 0
                    val (ipqsColor, ipqsBg) = when {
                        ipqsScore > 75 -> MaterialTheme.colorScheme.error to MaterialTheme.colorScheme.errorContainer
                        ipqsScore > 50 -> Color(0xFFD97706) to Color(0xFFFEF3C7)
                        else -> Color(0xFF10B981) to Color(0xFFD1FAE5)
                    }
                    Text(text = "IPQS Score", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Spacer(modifier = Modifier.height(4.dp))
                    Surface(color = ipqsBg, shape = RoundedCornerShape(4.dp)) {
                        Text(text = "$ipqsScore", style = MaterialTheme.typography.labelSmall, color = ipqsColor, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp))
                    }
                }
                
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(text = "Date", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(text = conversion.convertedAt.take(10), style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
                }

                Column(horizontalAlignment = Alignment.End) {
                    Text(text = "Payout", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text(
                        text = "$${"%.2f".format(conversion.payout)}",
                        style = MaterialTheme.typography.titleMedium,
                        color = MaterialTheme.colorScheme.primary,
                        fontWeight = FontWeight.ExtraBold
                    )
                }
            }
        }
    }
}

