package net.affscash.android.ui.admin

import android.content.Intent
import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.MainActivity
import net.affscash.android.data.model.AdminAffiliate
import net.affscash.android.ui.affiliates.AdminAffiliatesViewModel
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI
import net.affscash.android.ui.dashboard.StatusBadge

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminUsersScreen(
    onNavigateToEdit: (Int) -> Unit = {},
    onNavigateToView: (Int) -> Unit = {},
    viewModel: AdminAffiliatesViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val statusTab by viewModel.status.collectAsState()
    val searchQuery by viewModel.searchQuery.collectAsState()
    val context = LocalContext.current

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

    val tabs = listOf("all", "pending", "active", "rejected", "suspended")

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(PremiumUI.PageBackground)
    ) {
        // 3D Search Bar Container
        Surface(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 12.dp, vertical = 8.dp),
            shape = PremiumUI.CardShape,
            color = Color.White,
            shadowElevation = 2.dp,
            border = PremiumUI.GlassBorder
        ) {
            OutlinedTextField(
                value = searchQuery,
                onValueChange = { viewModel.setSearchQuery(it) },
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(6.dp),
                placeholder = { Text("Search by name, email, code...", fontSize = 13.sp) },
                leadingIcon = { Icon(Icons.Outlined.Search, contentDescription = "Search", tint = Color(0xFF64748B)) },
                trailingIcon = {
                    if (searchQuery.isNotEmpty()) {
                        IconButton(onClick = { viewModel.setSearchQuery("") }) {
                            Icon(Icons.Default.Clear, contentDescription = "Clear", tint = Color(0xFF64748B))
                        }
                    }
                },
                singleLine = true,
                shape = RoundedCornerShape(14.dp),
                colors = OutlinedTextFieldDefaults.colors(
                    unfocusedBorderColor = Color.Transparent,
                    focusedBorderColor = Color(0xFF4F46E5),
                    unfocusedContainerColor = Color(0xFFF8FAFC),
                    focusedContainerColor = Color(0xFFF8FAFC)
                )
            )
        }

        // 3D Tab Pills Bar
        ScrollableTabRow(
            selectedTabIndex = tabs.indexOf(statusTab),
            edgePadding = 12.dp,
            containerColor = Color.Transparent,
            contentColor = Color(0xFF4F46E5),
            divider = {},
            indicator = {}
        ) {
            tabs.forEachIndexed { index, tab ->
                val isSelected = index == tabs.indexOf(statusTab)
                Box(
                    modifier = Modifier
                        .padding(horizontal = 4.dp, vertical = 6.dp)
                        .clip(RoundedCornerShape(12.dp))
                        .background(
                            if (isSelected) PremiumUI.PrimaryGradient
                            else androidx.compose.ui.graphics.Brush.linearGradient(listOf(Color.White, Color.White))
                        )
                        .clickable { viewModel.setStatus(tab) }
                        .padding(horizontal = 16.dp, vertical = 8.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = tab.uppercase(),
                        fontSize = 12.sp,
                        fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
                        color = if (isSelected) Color.White else Color(0xFF475569)
                    )
                }
            }
        }

        Spacer(modifier = Modifier.height(4.dp))

        Box(modifier = Modifier.fillMaxSize()) {
            if (uiState.isLoading) {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center), color = Color(0xFF4F46E5))
            } else if (uiState.affiliates.isNotEmpty()) {
                LazyColumn(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(horizontal = 12.dp),
                    contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    items(uiState.affiliates) { affiliate ->
                        AdminAffiliateItem(
                            affiliate = affiliate,
                            onAction = { action ->
                                viewModel.performAction(action, affiliate.userId, onSuccess = { role ->
                                    if (role != null) {
                                        Toast.makeText(context, "Logged in as ${affiliate.firstName}", Toast.LENGTH_SHORT).show()
                                        val intent = Intent(context, MainActivity::class.java).apply {
                                            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                                        }
                                        context.startActivity(intent)
                                    } else {
                                        Toast.makeText(context, "Action successful", Toast.LENGTH_SHORT).show()
                                    }
                                }, onError = { msg ->
                                    Toast.makeText(context, "Error: $msg", Toast.LENGTH_LONG).show()
                                })
                            },
                            onView = { onNavigateToView(affiliate.affId) },
                            onEdit = { onNavigateToEdit(affiliate.affId) }
                        )
                    }
                }
            } else {
                Text("No affiliates found", modifier = Modifier.align(Alignment.Center), color = Color(0xFF64748B))
            }
        }
    }
}

@Composable
fun AdminAffiliateItem(
    affiliate: AdminAffiliate,
    onAction: (String) -> Unit,
    onView: () -> Unit,
    onEdit: () -> Unit
) {
    GlassCard(elevation = 2.dp) {
        Column(modifier = Modifier.fillMaxWidth()) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.weight(1f)) {
                    Box(
                        modifier = Modifier
                            .size(38.dp)
                            .clip(CircleShape)
                            .background(PremiumUI.PrimaryGradient),
                        contentAlignment = Alignment.Center
                    ) {
                        Text(
                            text = (affiliate.firstName.take(1) + affiliate.lastName.take(1)).uppercase(),
                            color = Color.White,
                            fontWeight = FontWeight.Bold,
                            fontSize = 14.sp
                        )
                    }
                    Spacer(modifier = Modifier.width(10.dp))
                    Column {
                        Text(
                            text = "${affiliate.firstName} ${affiliate.lastName}",
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF0F172A)
                        )
                        Text(
                            text = affiliate.email,
                            fontSize = 12.sp,
                            color = Color(0xFF64748B)
                        )
                    }
                }
                
                StatusBadge(status = affiliate.status)
            }
            
            Spacer(modifier = Modifier.height(10.dp))
            HorizontalDivider(color = Color(0xFFF1F5F9))
            Spacer(modifier = Modifier.height(8.dp))
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Column {
                    Text(text = "Code: ${affiliate.affiliateCode}", fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFF334155))
                    Text(text = "Joined: ${affiliate.createdAt.take(10)}", fontSize = 11.sp, color = Color(0xFF64748B))
                }
                Column(horizontalAlignment = Alignment.End) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(text = "Fraud Score: ", fontSize = 11.sp, color = Color(0xFF64748B))
                        Text(
                            text = "${affiliate.fraudScore}",
                            fontSize = 12.sp,
                            fontWeight = FontWeight.Bold,
                            color = if (affiliate.fraudScore > 20) Color(0xFFEF4444) else Color(0xFF10B981)
                        )
                    }
                    val balanceVal = (affiliate.balance)?.toString()?.toDoubleOrNull() ?: 0.0
                    Text(
                        text = "Balance: $${"%.2f".format(balanceVal)}",
                        fontSize = 14.sp,
                        fontWeight = FontWeight.ExtraBold,
                        color = Color(0xFF4F46E5)
                    )
                }
            }
            
            Spacer(modifier = Modifier.height(10.dp))
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                    OutlinedButton(
                        onClick = onView,
                        shape = RoundedCornerShape(10.dp),
                        border = BorderStroke(1.dp, Color(0xFFCBD5E1)),
                        contentPadding = PaddingValues(horizontal = 12.dp, vertical = 4.dp)
                    ) {
                        Text("View", color = Color(0xFF334155), fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                    }
                    OutlinedButton(
                        onClick = onEdit,
                        shape = RoundedCornerShape(10.dp),
                        border = BorderStroke(1.dp, Color(0xFFCBD5E1)),
                        contentPadding = PaddingValues(horizontal = 12.dp, vertical = 4.dp)
                    ) {
                        Text("Edit", color = Color(0xFF334155), fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                    }
                }

                Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                    if (affiliate.status == "pending") {
                        Button(
                            onClick = { onAction("approve") },
                            shape = RoundedCornerShape(10.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF10B981)),
                            contentPadding = PaddingValues(horizontal = 14.dp, vertical = 4.dp)
                        ) {
                            Text("Approve", fontWeight = FontWeight.Bold, fontSize = 12.sp)
                        }
                    }
                    if (affiliate.status == "active") {
                        Button(
                            onClick = { onAction("impersonate") },
                            shape = RoundedCornerShape(10.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5)),
                            contentPadding = PaddingValues(horizontal = 14.dp, vertical = 4.dp)
                        ) {
                            Text("Login As", fontWeight = FontWeight.Bold, fontSize = 12.sp)
                        }
                    }
                }
            }
        }
    }
}
