package net.affscash.android.ui.manager.referral

import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.WindowInsetsSides
import androidx.compose.foundation.layout.only
import androidx.compose.foundation.layout.safeDrawing
import androidx.compose.foundation.layout.windowInsetsPadding


import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.outlined.ContentCopy
import androidx.compose.material.icons.outlined.Group
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.MonetizationOn
@Composable
fun ManagerReferralScreen(
    onNavigateBack: () -> Unit = {},
    viewModel: ManagerReferralViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    Scaffold(
        topBar = {
            Surface(
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.background,
                shadowElevation = 2.dp
            ) {
                Box(modifier = Modifier.fillMaxWidth().windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {
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
                        text = "Referrals",
                        style = PremiumUI.HeaderStyle,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                }
                            }
}
        }
    ) { paddingValues ->
        when (val state = uiState) {
            is ManagerReferralUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize().padding(paddingValues), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            }
            is ManagerReferralUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize().padding(paddingValues), contentAlignment = Alignment.Center) {
                    Text(state.message, color = MaterialTheme.colorScheme.error)
                }
            }
            is ManagerReferralUiState.Success -> {
                val data = state.data
                
                Column(modifier = Modifier.fillMaxSize().padding(paddingValues)) {
                    // Top Banner Card
                    GlassCard(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp)
                    ) {
                        Column(modifier = Modifier.padding(16.dp)) {
                            Text(
                                "Your Manager Referral Link",
                                fontSize = 15.sp,
                                fontWeight = FontWeight.Black,
                                color = Color(0xFF0F172A)
                            )
                            Text(
                                "When a new affiliate registers using your link, they are automatically assigned to your team — no manual assignment needed.",
                                fontSize = 11.sp,
                                color = Color(0xFF64748B),
                                modifier = Modifier.padding(top = 4.dp, bottom = 12.dp)
                            )
                            OutlinedTextField(
                                value = data.referralLink,
                                onValueChange = {},
                                readOnly = true,
                                modifier = Modifier.fillMaxWidth(),
                                singleLine = true,
                                textStyle = LocalTextStyle.current.copy(fontSize = 12.sp, fontWeight = FontWeight.Bold),
                                colors = OutlinedTextFieldDefaults.colors(
                                    focusedBorderColor = Color(0xFF6366F1),
                                    unfocusedBorderColor = Color(0xFFCBD5E1)
                                ),
                                shape = PremiumUI.ButtonShape,
                                trailingIcon = {
                                    IconButton(onClick = {
                                        val clipboardManager = context.getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
                                        val clipData = ClipData.newPlainText("Referral Link", data.referralLink)
                                        clipboardManager.setPrimaryClip(clipData)
                                        Toast.makeText(context, "Link copied to clipboard", Toast.LENGTH_SHORT).show()
                                    }) {
                                        Icon(Icons.Outlined.ContentCopy, contentDescription = "Copy", tint = Color(0xFF4338CA))
                                    }
                                }
                            )
                            Spacer(modifier = Modifier.height(8.dp))
                            Surface(
                                color = Color(0xFFEEF2FF),
                                border = BorderStroke(1.dp, Color(0xFFC7D2FE)),
                                shape = PremiumUI.PillShape
                            ) {
                                Text(
                                    "Code: ${data.referralCode}", 
                                    fontSize = 11.sp, 
                                    fontWeight = FontWeight.Black, 
                                    color = Color(0xFF4338CA),
                                    modifier = Modifier.padding(horizontal = 12.dp, vertical = 5.dp)
                                )
                            }
                        }
                    }

                    // 3D Summary KPI Cards
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp),
                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        net.affscash.android.ui.dashboard.KPICard3D(
                            title = "Total Team",
                            value = "${data.stats.totalReferrals}",
                            icon = Icons.Outlined.Group,
                            iconGradient = PremiumUI.PrimaryGradient,
                            modifier = Modifier.weight(1f)
                        )
                        net.affscash.android.ui.dashboard.KPICard3D(
                            title = "Active",
                            value = "${data.stats.activeReferrals}",
                            icon = Icons.Outlined.CheckCircle,
                            iconGradient = PremiumUI.EmeraldGradient,
                            modifier = Modifier.weight(1f)
                        )
                        net.affscash.android.ui.dashboard.KPICard3D(
                            title = "Earned",
                            value = "$${data.stats.totalEarned}",
                            icon = Icons.Outlined.MonetizationOn,
                            iconGradient = PremiumUI.PurpleGradient,
                            modifier = Modifier.weight(1f)
                        )
                    }

                    Spacer(modifier = Modifier.height(12.dp))

                    // Section Title
                    Text(
                        text = "Team Members (${data.referredAffiliates.size})",
                        style = MaterialTheme.typography.titleSmall,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp)
                    )

                    // Content List
                    if (data.referredAffiliates.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize().padding(8.dp), contentAlignment = Alignment.Center) {
                            Text(
                                text = "No referrals yet. Share your referral link to grow your team automatically!",
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                textAlign = TextAlign.Center
                            )
                        }
                    } else {
                        LazyColumn(
                            modifier = Modifier.fillMaxSize(),
                            contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            items(data.referredAffiliates) { aff ->
                                GlassCard(elevation = 2.dp) {
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Column {
                                            Text(aff.name, fontWeight = FontWeight.Bold, fontSize = 14.sp, color = Color(0xFF0F172A))
                                            Text("Joined: ${aff.joinedAt}", fontSize = 11.sp, color = Color(0xFF64748B))
                                        }
                                        Surface(color = Color(0xFFD1FAE5), shape = RoundedCornerShape(6.dp)) {
                                            Text("ACTIVE", modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF059669))
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
