package net.affscash.android.ui.admin.referral

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.NavController
import net.affscash.android.data.model.*
import java.text.NumberFormat
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminReferralScreen(
    navController: NavController,
    viewModel: AdminReferralViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val dashboardData by viewModel.dashboardData.collectAsState()
    val currentTab by viewModel.currentTab.collectAsState()

    val tabs = listOf("Signups", "Commissions", "Codes")

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Referral System") },
                navigationIcon = {
                    IconButton(onClick = { navController.popBackStack() }) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { navController.navigate("platform_settings?tab=commission") }) {
                        Icon(Icons.Default.Settings, contentDescription = "Settings")
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
            dashboardData?.let { data ->
                AdminReferralDashboardCards(data)
            }

            TabRow(selectedTabIndex = currentTab) {
                tabs.forEachIndexed { index, title ->
                    Tab(
                        selected = currentTab == index,
                        onClick = { viewModel.setTab(index) },
                        text = { Text(title) }
                    )
                }
            }

            Box(modifier = Modifier.weight(1f)) {
                when (uiState) {
                    is AdminReferralUiState.Loading -> {
                        CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                    }
                    is AdminReferralUiState.Error -> {
                        Text(
                            text = (uiState as AdminReferralUiState.Error).message,
                            color = MaterialTheme.colorScheme.error,
                            modifier = Modifier
                                .align(Alignment.Center)
                                .padding(16.dp)
                        )
                    }
                    is AdminReferralUiState.Success -> {
                        when (currentTab) {
                            0 -> SignupsTab(viewModel)
                            1 -> CommissionsTab(viewModel)
                            2 -> CodesTab(viewModel)
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun AdminReferralDashboardCards(data: AdminReferralDashboardData) {
    val numberFormat = NumberFormat.getCurrencyInstance(Locale.US)
    Column(modifier = Modifier.padding(12.dp)) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            StatCard(
                title = "Total Links",
                value = data.totalCodes.toString(),
                subtitle = "Active codes",
                modifier = Modifier.weight(1f)
            )
            StatCard(
                title = "Signups",
                value = data.totalSignups.toString(),
                subtitle = "Via links",
                modifier = Modifier.weight(1f)
            )
        }
        Spacer(modifier = Modifier.height(8.dp))
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            StatCard(
                title = "Paid Commissions",
                value = numberFormat.format(data.totalCommissionPaid),
                subtitle = "${data.pendingCommissions} pending",
                valueColor = Color(0xFF10B981),
                modifier = Modifier.weight(1f)
            )
            val typeStr = if (data.commissionType == "percent") "%" else " USD"
            StatCard(
                title = "Current Rate",
                value = "${data.commissionRate}$typeStr",
                subtitle = if (data.commissionType == "percent") "Of each payout" else "Fixed",
                valueColor = Color(0xFF4F46E5),
                modifier = Modifier.weight(1f)
            )
        }
    }
}

@Composable
fun StatCard(
    title: String,
    value: String,
    subtitle: String,
    modifier: Modifier = Modifier,
    valueColor: Color = MaterialTheme.colorScheme.onSurface
) {
    Card(
        modifier = modifier,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(
                text = title.uppercase(),
                fontSize = 11.sp,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                text = value,
                fontSize = 20.sp,
                fontWeight = FontWeight.ExtraBold,
                color = valueColor
            )
            Spacer(modifier = Modifier.height(2.dp))
            Text(
                text = subtitle,
                fontSize = 12.sp,
                color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f)
            )
        }
    }
}

@Composable
fun SignupsTab(viewModel: AdminReferralViewModel) {
    val signups by viewModel.signups.collectAsState()
    if (signups.isEmpty()) {
        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Text("No signups found.")
        }
    } else {
        LazyColumn(contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp)) {
            items(signups) { signup ->
                SignupItem(signup)
                Divider(modifier = Modifier.padding(vertical = 8.dp))
            }
        }
    }
}

@Composable
fun SignupItem(signup: AdminReferralSignup) {
    Column {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Text(signup.referrerName ?: "Unknown", fontWeight = FontWeight.Bold)
            Badge(
                containerColor = if (signup.referrerRole == "affiliate_manager") Color(0xFFEFF6FF) else Color(0xFFF0FDF4),
                contentColor = if (signup.referrerRole == "affiliate_manager") Color(0xFF1D4ED8) else Color(0xFF15803D)
            ) {
                Text(if (signup.referrerRole == "affiliate_manager") "Manager" else "Affiliate", modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp))
            }
        }
        Text(signup.referrerEmail ?: "", fontSize = 12.sp, color = Color.Gray)
        Spacer(modifier = Modifier.height(8.dp))
        Text("Referred: ${signup.referredName}", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
        Text("${signup.referredEmail} • Code: ${signup.referredAffCode}", fontSize = 12.sp, color = Color.Gray)
        Spacer(modifier = Modifier.height(4.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            val statusColor = when (signup.referredStatus) {
                "active" -> Color(0xFF10B981)
                "pending" -> Color(0xFFF59E0B)
                else -> Color.Gray
            }
            Text(signup.referredStatus?.uppercase() ?: "", fontSize = 12.sp, color = statusColor, fontWeight = FontWeight.Bold)
            Text("Balance: $${signup.referredBalance}", fontSize = 12.sp, fontWeight = FontWeight.Bold)
        }
        Text(signup.createdAt ?: "", fontSize = 10.sp, color = Color.LightGray, modifier = Modifier.padding(top = 4.dp))
    }
}

@Composable
fun CommissionsTab(viewModel: AdminReferralViewModel) {
    val commissions by viewModel.commissions.collectAsState()
    val totals by viewModel.commissionTotals.collectAsState()

    Column {
        totals?.let {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text("Approved: ${it.approved}", color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                Text("Pending: ${it.pending}", color = Color(0xFFF59E0B), fontWeight = FontWeight.Bold)
            }
        }
        if (commissions.isEmpty()) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Text("No commissions found.")
            }
        } else {
            LazyColumn(contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp)) {
                items(commissions) { commission ->
                    CommissionItem(commission, onApprove = { viewModel.approveCommission(it) }, onReject = { viewModel.rejectCommission(it) })
                    Divider(modifier = Modifier.padding(vertical = 8.dp))
                }
            }
        }
    }
}

@Composable
fun CommissionItem(commission: AdminReferralCommission, onApprove: (Int) -> Unit, onReject: (Int) -> Unit) {
    Column {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Column {
                Text(commission.referrerName ?: "Unknown", fontWeight = FontWeight.Bold)
                Text(commission.referrerCode ?: "", fontSize = 12.sp, color = Color.Gray)
            }
            Column(horizontalAlignment = Alignment.End) {
                Text(commission.referredName ?: "Unknown", fontWeight = FontWeight.Bold)
                Text(commission.referredAffCode ?: "", fontSize = 12.sp, color = Color.Gray)
            }
        }
        Spacer(modifier = Modifier.height(8.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Text("Base: $${commission.basePayout}", fontSize = 13.sp)
            val typeStr = if (commission.commissionType == "percent") "%" else " USD"
            Text("Rate: ${commission.commissionRate}$typeStr", fontSize = 13.sp)
        }
        Spacer(modifier = Modifier.height(4.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
            val statusColor = when (commission.status) {
                "approved" -> Color(0xFF10B981)
                "pending" -> Color(0xFFF59E0B)
                "rejected" -> Color(0xFFEF4444)
                else -> Color.Gray
            }
            Text(commission.status?.uppercase() ?: "", color = statusColor, fontWeight = FontWeight.Bold, fontSize = 12.sp)
            Text("Earned: $${commission.commissionAmount}", fontWeight = FontWeight.ExtraBold, color = Color(0xFF4F46E5))
        }
        
        if (commission.status == "pending") {
            Spacer(modifier = Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                OutlinedButton(
                    onClick = { onReject(commission.id) },
                    modifier = Modifier.padding(end = 8.dp),
                    colors = ButtonDefaults.outlinedButtonColors(contentColor = Color.Red)
                ) {
                    Icon(Icons.Default.Close, contentDescription = "Reject", modifier = Modifier.size(16.dp))
                    Spacer(Modifier.width(4.dp))
                    Text("Reject")
                }
                Button(
                    onClick = { onApprove(commission.id) },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF10B981))
                ) {
                    Icon(Icons.Default.Check, contentDescription = "Approve", modifier = Modifier.size(16.dp))
                    Spacer(Modifier.width(4.dp))
                    Text("Approve")
                }
            }
        }
        Text(commission.createdAt ?: "", fontSize = 10.sp, color = Color.LightGray, modifier = Modifier.padding(top = 4.dp))
    }
}

@Composable
fun CodesTab(viewModel: AdminReferralViewModel) {
    val codes by viewModel.codes.collectAsState()
    if (codes.isEmpty()) {
        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Text("No referral codes found.")
        }
    } else {
        LazyColumn(contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp)) {
            items(codes) { code ->
                CodeItem(code)
                Divider(modifier = Modifier.padding(vertical = 8.dp))
            }
        }
    }
}

@Composable
fun CodeItem(code: AdminReferralCode) {
    Column {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Text(code.userName ?: "Unknown", fontWeight = FontWeight.Bold)
            Badge(
                containerColor = if (code.role == "affiliate_manager") Color(0xFFEFF6FF) else Color(0xFFF0FDF4),
                contentColor = if (code.role == "affiliate_manager") Color(0xFF1D4ED8) else Color(0xFF15803D)
            ) {
                Text(if (code.role == "affiliate_manager") "Manager" else "Affiliate", modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp))
            }
        }
        Text(code.email ?: "", fontSize = 12.sp, color = Color.Gray)
        Spacer(modifier = Modifier.height(8.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
            Text(code.code ?: "", modifier = Modifier
                .background(Color(0xFFF1F5F9), RoundedCornerShape(4.dp))
                .padding(horizontal = 8.dp, vertical = 4.dp),
                fontSize = 14.sp,
                fontWeight = FontWeight.Bold
            )
            Column(horizontalAlignment = Alignment.End) {
                Text("Signups: ${code.signupCount}", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                Text("Earned: $${code.totalEarned}", fontSize = 13.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF10B981))
            }
        }
        Text(code.createdAt ?: "", fontSize = 10.sp, color = Color.LightGray, modifier = Modifier.padding(top = 4.dp))
    }
}
