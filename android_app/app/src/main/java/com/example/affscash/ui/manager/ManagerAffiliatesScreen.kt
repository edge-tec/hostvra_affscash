package com.example.affscash.ui.manager

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.Payment
import androidx.compose.material3.ScrollableTabRow
import androidx.compose.material3.Tab
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.example.affscash.data.local.UserManager
import com.example.affscash.data.model.ManagerAffiliateListModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerAffiliatesScreen(
    onLoginToAffiliate: (String) -> Unit = {},
    onNavigateToCreate: () -> Unit = {},
    onNavigateToEdit: (Int) -> Unit = {},
    onNavigateToView: (Int) -> Unit = {},
    viewModel: ManagerAffiliatesViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    val userManager = com.example.affscash.data.local.UserManager(context)

    LaunchedEffect(uiState.error, uiState.actionMessage) {
        uiState.error?.let {
            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
            viewModel.clearActionMessage()
        }
        uiState.actionMessage?.let {
            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
            viewModel.clearActionMessage()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("My Affiliates") },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary
                ),
                actions = {
                    IconButton(onClick = onNavigateToCreate) {
                        Icon(Icons.Default.Add, contentDescription = "Create Affiliate", tint = MaterialTheme.colorScheme.onPrimary)
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
            // Action Buttons
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                OutlinedButton(onClick = { Toast.makeText(context, "Export CSV not supported on mobile", Toast.LENGTH_SHORT).show() }) {
                    Icon(Icons.Default.Download, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.width(4.dp))
                    Text("Export CSV")
                }
                Button(onClick = { Toast.makeText(context, "Navigate to Payouts", Toast.LENGTH_SHORT).show() }) {
                    Icon(Icons.Default.Payment, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.width(4.dp))
                    Text("Payout Management")
                }
            }

            // Status Tabs
            val tabs = listOf("all" to "All", "active" to "Active", "pending" to "Pending", "suspended" to "Suspended", "rejected" to "Rejected")
            ScrollableTabRow(
                selectedTabIndex = tabs.indexOfFirst { it.first == uiState.statusFilter }.takeIf { it >= 0 } ?: 0,
                edgePadding = 16.dp,
                modifier = Modifier.fillMaxWidth()
            ) {
                tabs.forEachIndexed { index, (key, title) ->
                    Tab(
                        selected = uiState.statusFilter == key,
                        onClick = { viewModel.setStatusFilter(key) },
                        text = { Text(title) }
                    )
                }
            }

            // Filters
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                OutlinedTextField(
                    value = uiState.searchQuery,
                    onValueChange = { viewModel.updateSearchQuery(it) },
                    placeholder = { Text("Search affiliates...") },
                    leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
                    modifier = Modifier.weight(1f),
                    singleLine = true
                )
                
                Spacer(modifier = Modifier.width(8.dp))
                
                var expanded by remember { mutableStateOf(false) }
                ExposedDropdownMenuBox(
                    expanded = expanded,
                    onExpandedChange = { expanded = it }
                ) {
                    OutlinedTextField(
                        value = when(uiState.fraudScoreFilter) {
                            "low" -> "Low (< 30)"
                            "medium" -> "Medium (30-70)"
                            "high" -> "High (> 70)"
                            else -> "All scores"
                        },
                        onValueChange = {},
                        readOnly = true,
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                        modifier = Modifier.width(140.dp).menuAnchor()
                    )
                    ExposedDropdownMenu(
                        expanded = expanded,
                        onDismissRequest = { expanded = false }
                    ) {
                        listOf("all" to "All scores", "low" to "Low (< 30)", "medium" to "Medium (30-70)", "high" to "High (> 70)").forEach { (key, label) ->
                            DropdownMenuItem(
                                text = { Text(label) },
                                onClick = {
                                    viewModel.setFraudScoreFilter(key)
                                    expanded = false
                                }
                            )
                        }
                    }
                }
            }

            if (uiState.isLoading) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            } else if (uiState.affiliates.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text("No affiliates found.")
                }
            } else {
                LazyColumn(
                    contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    items(uiState.affiliates) { affiliate ->
                        ManagerAffiliateCard(
                            affiliate = affiliate,
                            canApprove = uiState.canApprove,
                            onUpdateStatus = { status -> viewModel.updateAffiliateStatus(affiliate.affId, status) },
                            onImpersonate = {
                                viewModel.impersonateAffiliate(affiliate.affId) { role, user ->
                                    userManager.saveUser(role, user.email, "${user.firstName} ${user.lastName}")
                                    userManager.saveIsImpersonating(true)
                                    onLoginToAffiliate(role)
                                }
                            },
                            onView = { onNavigateToView(affiliate.affId) },
                            onEdit = { onNavigateToEdit(affiliate.affId) }
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun ManagerAffiliateCard(
    affiliate: ManagerAffiliateListModel,
    canApprove: Boolean,
    onUpdateStatus: (String) -> Unit,
    onImpersonate: () -> Unit,
    onView: () -> Unit,
    onEdit: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Column {
                    Text(
                        text = "${affiliate.firstName} ${affiliate.lastName}",
                        fontWeight = FontWeight.Bold,
                        style = MaterialTheme.typography.titleMedium
                    )
                    Text(text = affiliate.email, style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                    Text(text = affiliate.affiliateCode, style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.SemiBold)
                    if (!affiliate.company.isNullOrBlank()) {
                        Text(text = affiliate.company, style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium, color = MaterialTheme.colorScheme.primary)
                    }
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text(
                        text = affiliate.balance ?: "$0.00",
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.primary
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    StatusBadge(status = affiliate.status)
                }
            }

            Spacer(modifier = Modifier.height(12.dp))
            
            // Stats Grid
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Joined", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                    Text(affiliate.createdAt.take(10), style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
                }
                Column {
                    Text("Last Login", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                    Text(affiliate.lastLogin?.take(16) ?: "Never", style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
                }
                Column {
                    Text("Days Inactive", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                    Text(affiliate.daysInactive?.let { "$it DAYS" } ?: "-", style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
                }
            }

            Spacer(modifier = Modifier.height(8.dp))

            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Default.Warning, contentDescription = null, modifier = Modifier.size(16.dp), tint = Color.Gray)
                Spacer(modifier = Modifier.width(4.dp))
                Text(
                    text = "Fraud Score: ${affiliate.fraudScore.toInt()} (${affiliate.fraudCheckedCount} checks)",
                    style = MaterialTheme.typography.bodySmall,
                    color = if (affiliate.fraudScore > 70) Color.Red else if (affiliate.fraudScore > 30) Color(0xFFFFA500) else Color(0xFF4CAF50)
                )
            }

            Spacer(modifier = Modifier.height(16.dp))
            Divider()
            Spacer(modifier = Modifier.height(12.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedButton(onClick = onView, contentPadding = PaddingValues(horizontal = 8.dp)) {
                        Text("View")
                    }
                    OutlinedButton(onClick = onEdit, contentPadding = PaddingValues(horizontal = 8.dp)) {
                        Text("Edit")
                    }
                }
                
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Button(onClick = onImpersonate, contentPadding = PaddingValues(horizontal = 8.dp)) {
                        Icon(Icons.Default.VpnKey, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Login As")
                    }
                    
                    if (canApprove) {
                        if (affiliate.status == "pending") {
                            Button(
                                onClick = { onUpdateStatus("active") },
                                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4CAF50)),
                                contentPadding = PaddingValues(horizontal = 8.dp)
                            ) {
                                Text("Approve")
                            }
                        } else if (affiliate.status == "active") {
                            Button(
                                onClick = { onUpdateStatus("suspended") },
                                colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error),
                                contentPadding = PaddingValues(horizontal = 8.dp)
                            ) {
                                Text("Suspend")
                            }
                        } else {
                            Button(
                                onClick = { onUpdateStatus("active") },
                                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4CAF50)),
                                contentPadding = PaddingValues(horizontal = 8.dp)
                            ) {
                                Text("Activate")
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun StatusBadge(status: String) {
    val (color, text) = when (status) {
        "active" -> Color(0xFFE8F5E9) to Color(0xFF2E7D32)
        "pending" -> Color(0xFFFFF3E0) to Color(0xFFEF6C00)
        "suspended" -> Color(0xFFFFEBEE) to Color(0xFFC62828)
        "rejected" -> Color(0xFFF5F5F5) to Color(0xFF616161)
        else -> Color(0xFFF5F5F5) to Color(0xFF616161)
    }
    
    Box(
        modifier = Modifier
            .background(color = color, shape = MaterialTheme.shapes.small)
            .padding(horizontal = 8.dp, vertical = 4.dp)
    ) {
        Text(
            text = status.uppercase(),
            color = text,
            fontSize = 10.sp,
            fontWeight = FontWeight.Bold
        )
    }
}
