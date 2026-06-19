package net.affscash.android.ui.manager

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
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
import net.affscash.android.data.model.ManagedAffiliate
import net.affscash.android.data.model.ManagerSmartlink

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerSmartlinksScreen(
    onNavigateToRequests: () -> Unit,
    viewModel: ManagerSmartlinksViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Smart Links", fontWeight = FontWeight.Bold)
                        Text(
                            "Browse smart links and manage access requests",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                },
                actions = {
                    Box {
                        TextButton(onClick = onNavigateToRequests) {
                            Text("Requests", color = MaterialTheme.colorScheme.primary)
                        }
                        if (uiState.pendingRequestsCount > 0) {
                            Badge(
                                modifier = Modifier.align(Alignment.TopEnd).padding(end = 4.dp, top = 8.dp),
                                containerColor = MaterialTheme.colorScheme.error
                            ) {
                                Text(uiState.pendingRequestsCount.toString())
                            }
                        }
                    }
                }
            )
        }
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
        ) {
            // Search Bar
            OutlinedTextField(
                value = uiState.searchQuery,
                onValueChange = { viewModel.updateSearchQuery(it) },
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                placeholder = { Text("Search smartlinks...") },
                leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search") },
                shape = RoundedCornerShape(12.dp),
                singleLine = true
            )

            if (uiState.isLoading && uiState.smartlinks.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            } else if (uiState.error != null && uiState.smartlinks.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(text = uiState.error ?: "Unknown error", color = MaterialTheme.colorScheme.error)
                }
            } else if (uiState.smartlinks.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text("No smartlinks found.", color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            } else {
                LazyColumn(
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp),
                    modifier = Modifier.fillMaxSize()
                ) {
                    item {
                        Text(
                            text = "Available Smart Links (${uiState.smartlinks.size})",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold
                        )
                    }
                    items(uiState.smartlinks) { sl ->
                        ManagerSmartlinkCard(
                            smartlink = sl,
                            trackingUrlBase = uiState.trackingUrlBase,
                            managedAffiliates = uiState.managedAffiliates,
                            onNavigateToRequests = onNavigateToRequests
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun ManagerSmartlinkCard(
    smartlink: ManagerSmartlink,
    trackingUrlBase: String,
    managedAffiliates: List<ManagedAffiliate>,
    onNavigateToRequests: () -> Unit
) {
    var showLinkGenerator by remember { mutableStateOf(false) }
    var selectedAffiliate by remember { mutableStateOf<ManagedAffiliate?>(null) }
    val context = LocalContext.current

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(11.dp)) {
            // Header
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "#${smartlink.id}",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                if (smartlink.status.lowercase() == "active") {
                    Badge(containerColor = Color(0xFF4CAF50)) {
                        Text("ACTIVE", fontSize = 9.sp, modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp))
                    }
                } else {
                    Badge(containerColor = Color(0xFF9E9E9E)) {
                        Text("PAUSED", fontSize = 9.sp, modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp))
                    }
                }
            }
            
            Spacer(modifier = Modifier.height(4.dp))

            Text(
                text = smartlink.name,
                fontSize = 14.sp,
                fontWeight = FontWeight.Bold,
                maxLines = 2,
                overflow = TextOverflow.Ellipsis
            )
            if (!smartlink.description.isNullOrEmpty()) {
                Text(
                    text = smartlink.description,
                    fontSize = 11.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }

            Spacer(modifier = Modifier.height(8.dp))

            // Badges Row
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column {
                    Text("My Affiliates", fontSize = 9.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Spacer(modifier = Modifier.height(4.dp))
                    Box(
                        modifier = Modifier
                            .clip(RoundedCornerShape(12.dp))
                            .background(Color(0xFFE8F5E9))
                            .padding(horizontal = 6.dp, vertical = 2.dp)
                    ) {
                        Text(
                            text = "🟢 ${smartlink.myApproved} APPROVED",
                            fontSize = 9.sp,
                            color = Color(0xFF2E7D32),
                            fontWeight = FontWeight.Bold
                        )
                    }
                }

                if (smartlink.myPending > 0) {
                    Column {
                        Text("Requests", fontSize = 9.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(modifier = Modifier.height(4.dp))
                        Box(
                            modifier = Modifier
                                .clip(RoundedCornerShape(12.dp))
                                .background(Color(0xFFFFF3E0))
                                .clickable { onNavigateToRequests() }
                                .padding(horizontal = 6.dp, vertical = 2.dp)
                        ) {
                            Text(
                                text = "🟠 ${smartlink.myPending} PENDING",
                                fontSize = 9.sp,
                                color = Color(0xFFEF6C00),
                                fontWeight = FontWeight.Bold
                            )
                        }
                    }
                }

                Column(horizontalAlignment = Alignment.End) {
                    Text("Total Appv", fontSize = 9.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text(
                        text = smartlink.totalApproved.toString(),
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Bold
                    )
                }
            }

            Spacer(modifier = Modifier.height(8.dp))
            Divider(color = MaterialTheme.colorScheme.outlineVariant)

            // Tracking Link Generator
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .clickable { showLinkGenerator = !showLinkGenerator }
                    .padding(vertical = 12.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    "Tracking Link Generator",
                    style = MaterialTheme.typography.labelLarge,
                    color = MaterialTheme.colorScheme.primary
                )
                Icon(
                    imageVector = if (showLinkGenerator) Icons.Default.ExpandLess else Icons.Default.ExpandMore,
                    contentDescription = "Expand",
                    tint = MaterialTheme.colorScheme.primary
                )
            }

            AnimatedVisibility(visible = showLinkGenerator) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(MaterialTheme.colorScheme.surfaceVariant, RoundedCornerShape(8.dp))
                        .padding(12.dp)
                ) {
                    if (managedAffiliates.isEmpty()) {
                        Text(
                            "No managed affiliates available.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    } else {
                        var expanded by remember { mutableStateOf(false) }
                        
                        Box {
                            OutlinedButton(
                                onClick = { expanded = true },
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(8.dp)
                            ) {
                                Text(
                                    text = selectedAffiliate?.name ?: "— Select affiliate —",
                                    color = if (selectedAffiliate == null) MaterialTheme.colorScheme.onSurfaceVariant else MaterialTheme.colorScheme.onSurface
                                )
                            }
                            DropdownMenu(
                                expanded = expanded,
                                onDismissRequest = { expanded = false }
                            ) {
                                managedAffiliates.forEach { affiliate ->
                                    DropdownMenuItem(
                                        text = { Text("${affiliate.name} (${affiliate.affiliateCode})") },
                                        onClick = {
                                            selectedAffiliate = affiliate
                                            expanded = false
                                        }
                                    )
                                }
                            }
                        }

                        val generatedUrl = if (selectedAffiliate != null) {
                            "$trackingUrlBase${smartlink.id}?aff=${selectedAffiliate!!.affiliateCode}&sub1="
                        } else {
                            ""
                        }

                        Spacer(modifier = Modifier.height(8.dp))
                        
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            OutlinedTextField(
                                value = generatedUrl.ifEmpty { "Select affiliate above..." },
                                onValueChange = {},
                                readOnly = true,
                                textStyle = LocalTextStyle.current.copy(fontSize = 12.sp),
                                modifier = Modifier.weight(1f),
                                singleLine = true
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                            Button(
                                onClick = {
                                    if (generatedUrl.isNotEmpty()) {
                                        val clipboard = context.getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
                                        val clip = ClipData.newPlainText("Tracking Link", generatedUrl)
                                        clipboard.setPrimaryClip(clip)
                                        Toast.makeText(context, "Link Copied!", Toast.LENGTH_SHORT).show()
                                    }
                                },
                                enabled = generatedUrl.isNotEmpty(),
                                shape = RoundedCornerShape(8.dp)
                            ) {
                                Text("Copy")
                            }
                        }
                    }
                }
            }
        }
    }
}
