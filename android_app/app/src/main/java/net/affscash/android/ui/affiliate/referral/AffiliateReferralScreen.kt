package net.affscash.android.ui.affiliate.referral

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.ReferredAffiliate
import net.affscash.android.data.model.ReferralCommission

@Composable
fun AffiliateReferralScreen(
    viewModel: AffiliateReferralViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    var selectedTab by remember { mutableStateOf(0) }

    when (val state = uiState) {
        is AffiliateReferralUiState.Loading -> {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
        }
        is AffiliateReferralUiState.Error -> {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Text(state.message, color = MaterialTheme.colorScheme.error)
            }
        }
        is AffiliateReferralUiState.Success -> {
            val data = state.data
            
            Column(modifier = Modifier.fillMaxSize()) {
                // Top Banner
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Text("Your Referral Link", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onPrimaryContainer)
                        Text(
                            "Share this link with other affiliates. When they sign up and earn, you get a ${data.stats.commissionRate}% commission on their payouts!",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onPrimaryContainer,
                            modifier = Modifier.padding(top = 4.dp, bottom = 12.dp)
                        )
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            OutlinedTextField(
                                value = data.referralLink,
                                onValueChange = {},
                                readOnly = true,
                                modifier = Modifier.weight(1f),
                                singleLine = true
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                            Button(onClick = {
                                val clipboardManager = context.getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
                                val clipData = ClipData.newPlainText("Referral Link", data.referralLink)
                                clipboardManager.setPrimaryClip(clipData)
                                Toast.makeText(context, "Link copied to clipboard", Toast.LENGTH_SHORT).show()
                            }) {
                                Text("Copy Link")
                            }
                        }
                        Spacer(modifier = Modifier.height(8.dp))
                        Text("Your referral code: ${data.referralCode}", style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onPrimaryContainer)
                    }
                }

                // Summary Cards
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Card(modifier = Modifier.weight(1f)) {
                        Column(modifier = Modifier.padding(10.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                            Text("TOTAL REFERRALS", style = MaterialTheme.typography.labelSmall, fontSize = 8.sp, maxLines = 1, overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis)
                            Text("${data.stats.totalReferrals}", style = MaterialTheme.typography.headlineMedium, fontSize = 20.sp, fontWeight = FontWeight.Bold)
                            Text("Affiliates you referred", style = MaterialTheme.typography.bodySmall, fontSize = 9.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }
                    Card(modifier = Modifier.weight(1f)) {
                        Column(modifier = Modifier.padding(10.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                            Text("COMMISSIONS", style = MaterialTheme.typography.labelSmall, fontSize = 8.sp, maxLines = 1, overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis)
                            Text("$${data.stats.commissionsEarned}", style = MaterialTheme.typography.headlineMedium, fontSize = 20.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
                            Text("Total earned", style = MaterialTheme.typography.bodySmall, fontSize = 9.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }
                    Card(modifier = Modifier.weight(1f)) {
                        Column(modifier = Modifier.padding(10.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                            Text("RATE", style = MaterialTheme.typography.labelSmall, fontSize = 8.sp)
                            Text("${data.stats.commissionRate}%", style = MaterialTheme.typography.headlineMedium, fontSize = 20.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.secondary)
                            Text("Commission rate", style = MaterialTheme.typography.bodySmall, fontSize = 9.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }
                }

                Spacer(modifier = Modifier.height(16.dp))

                // Tabs
                TabRow(selectedTabIndex = selectedTab) {
                    Tab(
                        selected = selectedTab == 0,
                        onClick = { selectedTab = 0 },
                        text = { Text("Referred Affiliates (${data.referredAffiliates.size})") }
                    )
                    Tab(
                        selected = selectedTab == 1,
                        onClick = { selectedTab = 1 },
                        text = { Text("My Commissions (${data.commissions.size})") }
                    )
                }

                // Content
                if (selectedTab == 0) {
                    if (data.referredAffiliates.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Text("No referrals yet. Share your link to start earning!", color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    } else {
                        LazyColumn(modifier = Modifier.fillMaxSize()) {
                            items(data.referredAffiliates) { aff ->
                                ReferredAffiliateItem(aff)
                                HorizontalDivider()
                            }
                        }
                    }
                } else {
                    if (data.commissions.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Text("No commissions earned yet.", color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    } else {
                        LazyColumn(modifier = Modifier.fillMaxSize()) {
                            items(data.commissions) { comm ->
                                CommissionItem(comm)
                                HorizontalDivider()
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun ReferredAffiliateItem(aff: ReferredAffiliate) {
    Column(modifier = Modifier.fillMaxWidth().padding(16.dp)) {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Text(aff.name, fontWeight = FontWeight.Bold)
            Text(aff.status.uppercase(), style = MaterialTheme.typography.labelSmall, color = if (aff.status == "active") MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant)
        }
        Text("Code: ${aff.affiliateCode} • Joined: ${aff.joinedAt}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Spacer(modifier = Modifier.height(4.dp))
        Text("Conversions: ${aff.convCount}", style = MaterialTheme.typography.bodySmall)
    }
}

@Composable
fun CommissionItem(comm: ReferralCommission) {
    Column(modifier = Modifier.fillMaxWidth().padding(16.dp)) {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Text("From: ${comm.referredName}", fontWeight = FontWeight.Bold)
            Text("+$${comm.commissionAmount}", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
        }
        Text("Conv ID: ${comm.conversionId} • Date: ${comm.createdAt}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Spacer(modifier = Modifier.height(4.dp))
        Text("Base Payout: $${comm.basePayout} (${comm.commissionRate}%) • Status: ${comm.status}", style = MaterialTheme.typography.bodySmall)
    }
}
