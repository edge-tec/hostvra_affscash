package net.affscash.android.ui.screens.admin.account_delete

import android.widget.Toast
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
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.DeleteOutline
import androidx.compose.material.icons.filled.ErrorOutline
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import net.affscash.android.data.model.AdminAccountDeleteRequestItem
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminAccountDeleteRequestsScreen(
    viewModel: AdminAccountDeleteRequestsViewModel,
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    var showActionDialog by remember { mutableStateOf(false) }
    var selectedRequest by remember { mutableStateOf<AdminAccountDeleteRequestItem?>(null) }
    var selectedDecision by remember { mutableStateOf("") }
    var adminNote by remember { mutableStateOf("") }

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
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back", tint = Color(0xFF0F172A))
                    }
                    Box(
                        modifier = Modifier
                            .size(38.dp)
                            .clip(RoundedCornerShape(10.dp))
                            .background(PremiumUI.HeaderGradient),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            Icons.Outlined.PersonRemove,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(20.dp)
                        )
                    }
                    Spacer(modifier = Modifier.width(10.dp))
                    Column {
                        Text(
                            text = "Account Deletion Requests",
                            fontSize = 16.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF0F172A)
                        )
                        Text(
                            text = "Review affiliate account closure requests",
                            fontSize = 11.sp,
                            color = Color(0xFF64748B)
                        )
                    }
                }
            }
        }

        // Stats Row
        uiState.stats?.let { stats ->
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .horizontalScroll(rememberScrollState())
                    .padding(horizontal = 12.dp, vertical = 4.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                StatCard3D(title = "PENDING", value = stats.pending.toString(), color = Color(0xFFFEF3C7), textColor = Color(0xFFD97706))
                StatCard3D(title = "APPROVED", value = stats.approved.toString(), color = Color(0xFFD1FAE5), textColor = Color(0xFF059669))
                StatCard3D(title = "REJECTED", value = stats.rejected.toString(), color = Color(0xFFFEE2E2), textColor = Color(0xFFDC2626))
                StatCard3D(title = "TOTAL", value = stats.total.toString(), color = Color(0xFFEEF2FF), textColor = Color(0xFF4F46E5))
            }
        }

        // 3D Segmented Tab Pills
        val tabs = listOf("pending", "approved", "rejected", "all")
        Surface(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 12.dp, vertical = 4.dp),
            shape = RoundedCornerShape(16.dp),
            color = Color(0xFFF1F5F9),
            border = BorderStroke(1.dp, Color(0xFFE2E8F0))
        ) {
            Row(
                modifier = Modifier.fillMaxWidth().padding(4.dp),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                tabs.forEach { tab ->
                    val isSelected = uiState.currentTab == tab
                    Surface(
                        onClick = { viewModel.setTab(tab) },
                        modifier = Modifier.weight(1f),
                        shape = RoundedCornerShape(12.dp),
                        color = if (isSelected) Color.White else Color.Transparent,
                        shadowElevation = if (isSelected) 2.dp else 0.dp
                    ) {
                        Box(
                            modifier = Modifier.padding(vertical = 8.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = tab.replaceFirstChar { if (it.isLowerCase()) it.titlecase(Locale.getDefault()) else it.toString() },
                                fontSize = 12.sp,
                                fontWeight = if (isSelected) FontWeight.ExtraBold else FontWeight.Medium,
                                color = if (isSelected) Color(0xFF4F46E5) else Color(0xFF64748B)
                            )
                        }
                    }
                }
            }
        }

        if (uiState.isLoading) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = Color(0xFF4F46E5))
            }
        } else if (uiState.error != null && uiState.requests.isEmpty()) {
            val errorText = uiState.error ?: "Unknown error"
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Icon(
                        imageVector = Icons.Default.ErrorOutline,
                        contentDescription = "Error",
                        modifier = Modifier.size(48.dp),
                        tint = Color(0xFFEF4444)
                    )
                    Spacer(modifier = Modifier.height(6.dp))
                    Text(text = "Error loading requests", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = errorText,
                        fontSize = 12.sp,
                        color = Color(0xFF64748B),
                        textAlign = TextAlign.Center,
                        modifier = Modifier.padding(horizontal = 32.dp)
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    Button(
                        onClick = { viewModel.loadData() },
                        shape = PremiumUI.ButtonShape,
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                    ) {
                        Text("Retry")
                    }
                }
            }
        } else if (uiState.requests.isEmpty()) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Box(
                        modifier = Modifier
                            .size(64.dp)
                            .clip(CircleShape)
                            .background(Color(0xFFF1F5F9)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.DeleteOutline,
                            contentDescription = "No Requests",
                            modifier = Modifier.size(32.dp),
                            tint = Color(0xFF94A3B8)
                        )
                    }
                    Spacer(modifier = Modifier.height(10.dp))
                    Text(
                        text = "All Caught Up!",
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF0F172A)
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = "There are no account deletion requests at the moment.",
                        fontSize = 12.sp,
                        color = Color(0xFF64748B)
                    )
                }
            }
        } else {
            LazyColumn(
                modifier = Modifier.fillMaxSize().padding(horizontal = 12.dp),
                contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(uiState.requests) { request ->
                    AdminAccountDeleteRequestItemCard(
                        request = request,
                        onAction = { req, decision ->
                            selectedRequest = req
                            selectedDecision = decision
                            adminNote = ""
                            showActionDialog = true
                        }
                    )
                }
            }
        }
    }

    if (showActionDialog && selectedRequest != null) {
        val nameStr = listOfNotNull(selectedRequest?.firstName, selectedRequest?.lastName).joinToString(" ").ifEmpty { "Affiliate" }
        AlertDialog(
            onDismissRequest = { showActionDialog = false },
            title = {
                Text(
                    text = if (selectedDecision == "approve") "Approve Deletion Request" else "Reject Deletion Request",
                    fontWeight = FontWeight.Bold,
                    fontSize = 16.sp
                )
            },
            text = {
                Column {
                    Text(
                        text = "Are you sure you want to ${selectedDecision} deletion for ${nameStr} (${selectedRequest?.email ?: "No email"})?",
                        fontSize = 13.sp,
                        color = Color(0xFF334155)
                    )
                    Spacer(modifier = Modifier.height(10.dp))
                    OutlinedTextField(
                        value = adminNote,
                        onValueChange = { adminNote = it },
                        label = { Text("Admin Note (Optional)", fontSize = 12.sp) },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = false,
                        maxLines = 3,
                        shape = RoundedCornerShape(10.dp)
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        val req = selectedRequest ?: return@Button
                        viewModel.submitAction(
                            requestId = req.id,
                            decision = selectedDecision,
                            adminNote = adminNote,
                            onSuccess = { msg ->
                                Toast.makeText(context, msg, Toast.LENGTH_SHORT).show()
                                showActionDialog = false
                            },
                            onError = { err ->
                                Toast.makeText(context, err, Toast.LENGTH_SHORT).show()
                            }
                        )
                    },
                    colors = ButtonDefaults.buttonColors(
                        containerColor = if (selectedDecision == "approve") Color(0xFF059669) else Color(0xFFDC2626)
                    )
                ) {
                    Text(if (selectedDecision == "approve") "Confirm Approve" else "Confirm Reject")
                }
            },
            dismissButton = {
                TextButton(onClick = { showActionDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }
}

@Composable
fun StatCard3D(
    title: String,
    value: String,
    color: Color,
    textColor: Color
) {
    Surface(
        shape = RoundedCornerShape(12.dp),
        color = color,
        border = BorderStroke(1.dp, textColor.copy(alpha = 0.2f))
    ) {
        Column(
            modifier = Modifier
                .width(85.dp)
                .padding(vertical = 8.dp, horizontal = 4.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(
                text = value,
                fontWeight = FontWeight.ExtraBold,
                fontSize = 18.sp,
                color = textColor
            )
            Spacer(modifier = Modifier.height(2.dp))
            Text(
                text = title,
                fontSize = 9.sp,
                fontWeight = FontWeight.Bold,
                color = textColor
            )
        }
    }
}

@Composable
fun AdminAccountDeleteRequestItemCard(
    request: AdminAccountDeleteRequestItem,
    onAction: (AdminAccountDeleteRequestItem, String) -> Unit
) {
    val fullName = listOfNotNull(request.firstName, request.lastName).joinToString(" ").ifEmpty { "Affiliate #${request.affiliateId}" }
    
    GlassCard(elevation = 2.dp) {
        Column(modifier = Modifier.fillMaxWidth()) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = fullName,
                        fontWeight = FontWeight.Bold,
                        fontSize = 14.sp,
                        color = Color(0xFF0F172A)
                    )
                    Text(
                        text = request.email ?: "No email",
                        fontSize = 12.sp,
                        color = Color(0xFF64748B)
                    )
                }

                val (badgeBg, badgeColor) = when (request.status) {
                    "approved" -> Color(0xFFD1FAE5) to Color(0xFF059669)
                    "pending" -> Color(0xFFFEF3C7) to Color(0xFFD97706)
                    "rejected" -> Color(0xFFFEE2E2) to Color(0xFFDC2626)
                    else -> Color(0xFFF1F5F9) to Color(0xFF64748B)
                }

                Surface(
                    color = badgeBg,
                    shape = RoundedCornerShape(6.dp)
                ) {
                    Text(
                        text = request.status.uppercase(),
                        color = badgeColor,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.ExtraBold,
                        modifier = Modifier.padding(horizontal = 7.dp, vertical = 3.dp)
                    )
                }
            }

            Spacer(modifier = Modifier.height(6.dp))
            Text(
                text = "Reason: ${request.reason}",
                fontSize = 12.sp,
                color = Color(0xFF334155)
            )

            if (!request.adminNote.isNullOrEmpty()) {
                Spacer(modifier = Modifier.height(4.dp))
                Text(
                    text = "Admin Note: ${request.adminNote}",
                    fontSize = 11.sp,
                    color = Color(0xFF64748B)
                )
            }

            Spacer(modifier = Modifier.height(8.dp))
            HorizontalDivider(color = Color(0xFFF1F5F9))
            Spacer(modifier = Modifier.height(6.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "Requested: ${request.requestedAt}",
                    fontSize = 11.sp,
                    color = Color(0xFF94A3B8)
                )

                if (request.status == "pending") {
                    Row {
                        OutlinedButton(
                            onClick = { onAction(request, "reject") },
                            shape = RoundedCornerShape(8.dp),
                            modifier = Modifier.height(32.dp),
                            border = BorderStroke(1.dp, Color(0xFFFCA5A5)),
                            contentPadding = PaddingValues(horizontal = 10.dp)
                        ) {
                            Text("Reject", fontSize = 11.sp, color = Color(0xFFDC2626), fontWeight = FontWeight.Bold)
                        }
                        Spacer(modifier = Modifier.width(6.dp))
                        Button(
                            onClick = { onAction(request, "approve") },
                            shape = RoundedCornerShape(8.dp),
                            modifier = Modifier.height(32.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF059669)),
                            contentPadding = PaddingValues(horizontal = 10.dp)
                        ) {
                            Text("Approve", fontSize = 11.sp, fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }
        }
    }
}
