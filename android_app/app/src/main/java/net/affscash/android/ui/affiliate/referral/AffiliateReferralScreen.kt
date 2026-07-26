package net.affscash.android.ui.affiliate.referral

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ContentCopy
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
import net.affscash.android.data.model.ReferralCommission
import net.affscash.android.data.model.ReferredAffiliate
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI

@Composable
fun AffiliateReferralScreen(
    viewModel: AffiliateReferralViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    var selectedTab by remember { mutableStateOf(0) }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(PremiumUI.PageBackground)
    ) {
        when (val state = uiState) {
            is AffiliateReferralUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = Color(0xFF4F46E5))
                }
            }
            is AffiliateReferralUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(state.message, color = Color(0xFFEF4444))
                }
            }
            is AffiliateReferralUiState.Success -> {
                val data = state.data

                LazyColumn(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(horizontal = 12.dp),
                    contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    // Referral Header Banner Card
                    item {
                        GlassCard(elevation = 3.dp) {
                            Column(modifier = Modifier.fillMaxWidth()) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Box(
                                        modifier = Modifier
                                            .size(36.dp)
                                            .clip(RoundedCornerShape(10.dp))
                                            .background(PremiumUI.HeaderGradient),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        net.affscash.android.ui.dashboard.GradientIcon(
                                            Icons.Outlined.Share,
                                            contentDescription = null,
                                            tint = Color.White,
                                            modifier = Modifier.size(18.dp)
                                        )
                                    }
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Text("Your Referral Link", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                                }
                                
                                Text(
                                    "Share this link with other affiliates. When they sign up and earn, you get a ${data.stats.commissionRate}% commission on their payouts!",
                                    fontSize = 12.sp,
                                    color = Color(0xFF64748B),
                                    modifier = Modifier.padding(vertical = 6.dp)
                                )

                                Surface(
                                    shape = RoundedCornerShape(12.dp),
                                    color = Color(0xFFF8FAFC),
                                    border = BorderStroke(1.dp, Color(0xFFE2E8F0)),
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Row(
                                        modifier = Modifier.padding(4.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Text(
                                            text = data.referralLink,
                                            fontSize = 11.sp,
                                            fontWeight = FontWeight.Medium,
                                            color = Color(0xFF334155),
                                            maxLines = 1,
                                            overflow = TextOverflow.Ellipsis,
                                            modifier = Modifier.weight(1f).padding(horizontal = 8.dp)
                                        )

                                        Button(
                                            onClick = {
                                                val clipboardManager = context.getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
                                                val clipData = ClipData.newPlainText("Referral Link", data.referralLink)
                                                clipboardManager.setPrimaryClip(clipData)
                                                Toast.makeText(context, "Link copied to clipboard", Toast.LENGTH_SHORT).show()
                                            },
                                            shape = RoundedCornerShape(10.dp),
                                            modifier = Modifier.height(34.dp),
                                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5)),
                                            contentPadding = PaddingValues(horizontal = 12.dp)
                                        ) {
                                            net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.ContentCopy, contentDescription = null, modifier = Modifier.size(14.dp))
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Text("Copy Link", fontSize = 11.sp, fontWeight = FontWeight.Bold)
                                        }
                                    }
                                }

                                Spacer(modifier = Modifier.height(6.dp))
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Text("Your referral code: ", fontSize = 12.sp, color = Color(0xFF64748B))
                                    Surface(color = Color(0xFFEEF2FF), shape = RoundedCornerShape(6.dp)) {
                                        Text(data.referralCode, modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), fontSize = 11.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF4F46E5))
                                    }
                                }
                            }
                        }
                    }

                    // 3D KPI Cards
                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            ReferralStatCard3D(
                                label = "TOTAL REFERRALS",
                                value = "${data.stats.totalReferrals}",
                                subtext = "Referred affiliates",
                                valueColor = Color(0xFF0F172A),
                                modifier = Modifier.weight(1f)
                            )
                            ReferralStatCard3D(
                                label = "COMMISSIONS",
                                value = "$${data.stats.commissionsEarned}",
                                subtext = "Total earned",
                                valueColor = Color(0xFF059669),
                                modifier = Modifier.weight(1f)
                            )
                            ReferralStatCard3D(
                                label = "RATE",
                                value = "${data.stats.commissionRate}%",
                                subtext = "Commission rate",
                                valueColor = Color(0xFF4F46E5),
                                modifier = Modifier.weight(1f)
                            )
                        }
                    }

                    // 3D Segmented Tab Pills
                    item {
                        Surface(
                            modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
                            shape = RoundedCornerShape(16.dp),
                            color = Color(0xFFF1F5F9),
                            border = BorderStroke(1.dp, Color(0xFFE2E8F0))
                        ) {
                            Row(
                                modifier = Modifier.fillMaxWidth().padding(4.dp),
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                val tabTitles = listOf("Referred Affiliates (${data.referredAffiliates.size})", "My Commissions (${data.commissions.size})")
                                tabTitles.forEachIndexed { index, title ->
                                    val isSelected = selectedTab == index
                                    Surface(
                                        onClick = { selectedTab = index },
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
                                                text = title,
                                                fontSize = 11.sp,
                                                fontWeight = if (isSelected) FontWeight.ExtraBold else FontWeight.Medium,
                                                color = if (isSelected) Color(0xFF4F46E5) else Color(0xFF64748B)
                                            )
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Tab Contents
                    if (selectedTab == 0) {
                        if (data.referredAffiliates.isEmpty()) {
                            item {
                                Box(modifier = Modifier.fillMaxWidth().height(200.dp), contentAlignment = Alignment.Center) {
                                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                        Box(
                                            modifier = Modifier
                                                .size(56.dp)
                                                .clip(CircleShape)
                                                .background(Color(0xFFF1F5F9)),
                                            contentAlignment = Alignment.Center
                                        ) {
                                            net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.Group, contentDescription = null, modifier = Modifier.size(28.dp), tint = Color(0xFF94A3B8))
                                        }
                                        Spacer(modifier = Modifier.height(8.dp))
                                        Text("No referrals yet. Share your link to start earning!", fontSize = 13.sp, color = Color(0xFF64748B))
                                    }
                                }
                            }
                        } else {
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
                    } else {
                        if (data.commissions.isEmpty()) {
                            item {
                                Box(modifier = Modifier.fillMaxWidth().height(200.dp), contentAlignment = Alignment.Center) {
                                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                        Box(
                                            modifier = Modifier
                                                .size(56.dp)
                                                .clip(CircleShape)
                                                .background(Color(0xFFF1F5F9)),
                                            contentAlignment = Alignment.Center
                                        ) {
                                            net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.MonetizationOn, contentDescription = null, modifier = Modifier.size(28.dp), tint = Color(0xFF94A3B8))
                                        }
                                        Spacer(modifier = Modifier.height(8.dp))
                                        Text("No referral commissions earned yet.", fontSize = 13.sp, color = Color(0xFF64748B))
                                    }
                                }
                            }
                        } else {
                            items(data.commissions) { comm ->
                                GlassCard(elevation = 2.dp) {
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Column {
                                            Text("Referred: ${comm.referredName}", fontWeight = FontWeight.Bold, fontSize = 13.sp, color = Color(0xFF0F172A))
                                            Text(comm.createdAt, fontSize = 11.sp, color = Color(0xFF64748B))
                                        }
                                        Text("+$${comm.commissionAmount}", fontWeight = FontWeight.ExtraBold, fontSize = 15.sp, color = Color(0xFF059669))
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

@Composable
fun ReferralStatCard3D(
    label: String,
    value: String,
    subtext: String,
    valueColor: Color,
    modifier: Modifier = Modifier
) {
    Surface(
        modifier = modifier,
        shape = RoundedCornerShape(14.dp),
        color = Color.White,
        border = BorderStroke(1.dp, Color(0xFFE2E8F0)),
        shadowElevation = 2.dp
    ) {
        Column(
            modifier = Modifier.padding(10.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(label, fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8), maxLines = 1, overflow = TextOverflow.Ellipsis)
            Spacer(modifier = Modifier.height(2.dp))
            Text(value, fontSize = 18.sp, fontWeight = FontWeight.ExtraBold, color = valueColor)
            Spacer(modifier = Modifier.height(2.dp))
            Text(subtext, fontSize = 10.sp, color = Color(0xFF64748B), maxLines = 1, overflow = TextOverflow.Ellipsis)
        }
    }
}
