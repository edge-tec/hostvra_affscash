package net.affscash.android.ui.screens.admin.points

import android.app.DatePickerDialog
import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.DateRange
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import net.affscash.android.data.model.AdminPointsBalance
import net.affscash.android.data.model.AdminPointsTransaction
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI
import java.util.Calendar

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminPointsScreen(
    viewModel: AdminPointsViewModel,
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    var selectedTab by remember { mutableStateOf(0) }
    val tabs = listOf("Balances", "Recent Activity", "Tools")

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
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back", tint = Color(0xFF0F172A))
                    }
                    Box(
                        modifier = Modifier
                            .size(38.dp)
                            .clip(RoundedCornerShape(10.dp))
                            .background(PremiumUI.HeaderGradient),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            Icons.Outlined.Stars,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(20.dp)
                        )
                    }
                    Spacer(modifier = Modifier.width(10.dp))
                    Column {
                        Text(
                            text = "Points Module",
                            fontSize = 17.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF0F172A)
                        )
                        Text(
                            text = "Rewards, balances & sync tools",
                            fontSize = 11.sp,
                            color = Color(0xFF64748B)
                        )
                    }
                }
            }
        }

        // 3D Segmented Tab Pills
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
                tabs.forEachIndexed { index, title ->
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
                                fontSize = 12.sp,
                                fontWeight = if (isSelected) FontWeight.ExtraBold else FontWeight.Medium,
                                color = if (isSelected) Color(0xFF4F46E5) else Color(0xFF64748B)
                            )
                        }
                    }
                }
            }
        }

        if (uiState.isLoading && uiState.balances.isEmpty() && uiState.recent.isEmpty()) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = Color(0xFF4F46E5))
            }
        } else if (uiState.error != null) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(uiState.error!!, color = Color(0xFFEF4444))
                    Spacer(modifier = Modifier.height(6.dp))
                    Button(
                        onClick = { viewModel.loadData() },
                        shape = PremiumUI.ButtonShape,
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                    ) {
                        Text("Retry")
                    }
                }
            }
        } else {
            Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
                when (selectedTab) {
                    0 -> BalancesList3D(uiState.balances)
                    1 -> TransactionsList3D(uiState.recent)
                    2 -> ToolsSection3D(uiState, viewModel, context)
                }
            }
        }
        
        // Show Sync Log Dialog
        if (uiState.syncLog.isNotEmpty()) {
            AlertDialog(
                onDismissRequest = { viewModel.clearSyncLog() },
                title = { Text("Sync Log", fontWeight = FontWeight.Bold) },
                text = {
                    LazyColumn(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                        items(uiState.syncLog) { log ->
                            Text(log, fontSize = 12.sp, color = Color(0xFF334155))
                            HorizontalDivider(color = Color(0xFFF1F5F9))
                        }
                    }
                },
                confirmButton = {
                    Button(
                        onClick = { viewModel.clearSyncLog() },
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                    ) {
                        Text("Close")
                    }
                }
            )
        }
    }
}

@Composable
fun BalancesList3D(balances: List<AdminPointsBalance>) {
    if (balances.isEmpty()) {
        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Text("No balances found.", color = Color(0xFF64748B))
        }
        return
    }
    
    val numberFormat = java.text.NumberFormat.getNumberInstance(java.util.Locale.US)
    
    LazyColumn(
        modifier = Modifier.fillMaxSize().padding(horizontal = 12.dp),
        contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp)
    ) {
        items(balances) { balance ->
            GlassCard(elevation = 2.dp) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Column(modifier = Modifier.weight(1f)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Surface(
                                shape = RoundedCornerShape(6.dp),
                                color = Color(0xFFEEF2FF)
                            ) {
                                Text("#${balance.affiliateId}", modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), fontSize = 11.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF4F46E5))
                            }
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(balance.name ?: "Unknown Affiliate", fontWeight = FontWeight.Bold, fontSize = 14.sp, color = Color(0xFF0F172A), maxLines = 1, overflow = TextOverflow.Ellipsis)
                        }
                        
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(balance.email ?: "N/A", fontSize = 12.sp, color = Color(0xFF64748B))
                        
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = "Earned: ${numberFormat.format(balance.lifetimeEarned)} • Spent: ${numberFormat.format(balance.lifetimeSpent)}",
                            fontSize = 11.sp,
                            color = Color(0xFF475569)
                        )
                    }

                    Column(horizontalAlignment = Alignment.End) {
                        Text(
                            text = numberFormat.format(balance.balance),
                            fontWeight = FontWeight.ExtraBold,
                            fontSize = 18.sp,
                            color = Color(0xFF059669)
                        )
                        Text(
                            text = "POINTS",
                            fontSize = 9.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF94A3B8)
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun TransactionsList3D(transactions: List<AdminPointsTransaction>) {
    if (transactions.isEmpty()) {
        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Text("No recent transactions.", color = Color(0xFF64748B))
        }
        return
    }
    
    LazyColumn(
        modifier = Modifier.fillMaxSize().padding(horizontal = 12.dp),
        contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp)
    ) {
        items(transactions) { tx ->
            GlassCard(elevation = 2.dp) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Column(modifier = Modifier.weight(1f)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            val isPositive = tx.amount > 0
                            val badgeBg = if (isPositive) Color(0xFFD1FAE5) else Color(0xFFFEE2E2)
                            val badgeColor = if (isPositive) Color(0xFF059669) else Color(0xFFDC2626)
                            
                            Surface(
                                shape = RoundedCornerShape(6.dp),
                                color = badgeBg
                            ) {
                                Text(
                                    text = tx.type.uppercase(),
                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                                    fontSize = 10.sp,
                                    fontWeight = FontWeight.ExtraBold,
                                    color = badgeColor
                                )
                            }
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(
                                text = "Affiliate #${tx.affiliateId}",
                                fontSize = 13.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF0F172A)
                            )
                        }
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = tx.reason ?: "No reason provided",
                            fontSize = 12.sp,
                            color = Color(0xFF475569)
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = tx.createdAt,
                            fontSize = 11.sp,
                            color = Color(0xFF94A3B8)
                        )
                    }

                    Text(
                        text = if (tx.amount > 0) "+${tx.amount}" else tx.amount.toString(),
                        fontWeight = FontWeight.ExtraBold,
                        fontSize = 16.sp,
                        color = if (tx.amount > 0) Color(0xFF059669) else Color(0xFFDC2626)
                    )
                }
            }
        }
    }
}

@Composable
fun ToolsSection3D(
    uiState: AdminPointsUiState,
    viewModel: AdminPointsViewModel,
    context: android.content.Context
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(horizontal = 12.dp, vertical = 4.dp)
            .verticalScroll(rememberScrollState()),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        // Config Card
        var enabled by remember(uiState.config) { mutableStateOf(uiState.config?.enabled ?: false) }
        var usdPerPointStr by remember(uiState.config) { mutableStateOf(uiState.config?.usdPerPoint?.toString() ?: "1") }

        GlassCard(elevation = 2.dp) {
            Column(modifier = Modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text("Conversion Rule", fontSize = 15.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF0F172A))
                
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Switch(
                        checked = enabled,
                        onCheckedChange = { enabled = it },
                        colors = SwitchDefaults.colors(checkedThumbColor = Color(0xFF4F46E5), checkedTrackColor = Color(0xFFEEF2FF))
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Points module enabled", fontSize = 13.sp, fontWeight = FontWeight.Medium, color = Color(0xFF334155))
                }
                
                OutlinedTextField(
                    value = usdPerPointStr,
                    onValueChange = { usdPerPointStr = it },
                    placeholder = { Text("USD per Point", fontSize = 13.sp) },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    shape = RoundedCornerShape(12.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = Color(0xFF4F46E5),
                        unfocusedBorderColor = Color(0xFFE2E8F0),
                        focusedContainerColor = Color(0xFFF8FAFC),
                        unfocusedContainerColor = Color(0xFFF8FAFC)
                    )
                )

                Button(
                    onClick = {
                        val usd = usdPerPointStr.toIntOrNull() ?: 1
                        viewModel.saveConfig(
                            enabled = enabled,
                            usdPerPoint = usd,
                            onSuccess = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() },
                            onError = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() }
                        )
                    },
                    modifier = Modifier.fillMaxWidth().height(44.dp),
                    shape = PremiumUI.ButtonShape,
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                ) {
                    Icon(Icons.Outlined.Save, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Save Rule", fontWeight = FontWeight.Bold)
                }
            }
        }

        // Manual Adjustment Card
        var adjustAffId by remember { mutableStateOf("") }
        var adjustDelta by remember { mutableStateOf("") }
        var adjustReason by remember { mutableStateOf("") }

        GlassCard(elevation = 2.dp) {
            Column(modifier = Modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text("Manual Adjustment", fontSize = 15.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF0F172A))
                
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = adjustAffId,
                        onValueChange = { adjustAffId = it },
                        placeholder = { Text("Affiliate ID", fontSize = 13.sp) },
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        modifier = Modifier.weight(1f),
                        singleLine = true,
                        shape = RoundedCornerShape(12.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFF4F46E5),
                            unfocusedBorderColor = Color(0xFFE2E8F0),
                            focusedContainerColor = Color(0xFFF8FAFC),
                            unfocusedContainerColor = Color(0xFFF8FAFC)
                        )
                    )
                    OutlinedTextField(
                        value = adjustDelta,
                        onValueChange = { adjustDelta = it },
                        placeholder = { Text("Delta (+ or -)", fontSize = 13.sp) },
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        modifier = Modifier.weight(1f),
                        singleLine = true,
                        shape = RoundedCornerShape(12.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFF4F46E5),
                            unfocusedBorderColor = Color(0xFFE2E8F0),
                            focusedContainerColor = Color(0xFFF8FAFC),
                            unfocusedContainerColor = Color(0xFFF8FAFC)
                        )
                    )
                }

                OutlinedTextField(
                    value = adjustReason,
                    onValueChange = { adjustReason = it },
                    placeholder = { Text("Reason", fontSize = 13.sp) },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    shape = RoundedCornerShape(12.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = Color(0xFF4F46E5),
                        unfocusedBorderColor = Color(0xFFE2E8F0),
                        focusedContainerColor = Color(0xFFF8FAFC),
                        unfocusedContainerColor = Color(0xFFF8FAFC)
                    )
                )

                Button(
                    onClick = {
                        val affId = adjustAffId.toIntOrNull() ?: 0
                        val delta = adjustDelta.toIntOrNull() ?: 0
                        if (affId > 0 && delta != 0) {
                            viewModel.adjustPoints(
                                affiliateId = affId,
                                delta = delta,
                                reason = adjustReason,
                                onSuccess = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() },
                                onError = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() }
                            )
                        } else {
                            Toast.makeText(context, "Invalid Affiliate ID or Delta", Toast.LENGTH_SHORT).show()
                        }
                    },
                    modifier = Modifier.fillMaxWidth().height(44.dp),
                    shape = PremiumUI.ButtonShape,
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF475569))
                ) {
                    Icon(Icons.Outlined.Tune, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Apply Adjustment", fontWeight = FontWeight.Bold)
                }
            }
        }

        // Auto Sync Card
        var syncSince by remember { mutableStateOf("") }
        var dryRun by remember { mutableStateOf(true) }

        GlassCard(elevation = 2.dp) {
            Column(modifier = Modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text("Auto-Points Sync", fontSize = 15.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF0F172A))
                Text("Scans all approved conversions and automatically credits missing points following the active rule.", fontSize = 12.sp, color = Color(0xFF64748B))

                var showDatePicker by remember { mutableStateOf(false) }

                if (showDatePicker) {
                    val calendar = Calendar.getInstance()
                    DatePickerDialog(
                        context,
                        { _, year, month, dayOfMonth ->
                            syncSince = String.format("%04d-%02d-%02d", year, month + 1, dayOfMonth)
                            showDatePicker = false
                        },
                        calendar.get(Calendar.YEAR),
                        calendar.get(Calendar.MONTH),
                        calendar.get(Calendar.DAY_OF_MONTH)
                    ).apply {
                        setOnDismissListener { showDatePicker = false }
                    }.show()
                }

                OutlinedTextField(
                    value = syncSince,
                    onValueChange = { syncSince = it },
                    placeholder = { Text("Only since date (YYYY-MM-DD)", fontSize = 13.sp) },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    shape = RoundedCornerShape(12.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = Color(0xFF4F46E5),
                        unfocusedBorderColor = Color(0xFFE2E8F0),
                        focusedContainerColor = Color(0xFFF8FAFC),
                        unfocusedContainerColor = Color(0xFFF8FAFC)
                    ),
                    trailingIcon = {
                        IconButton(onClick = { showDatePicker = true }) {
                            Icon(Icons.Default.DateRange, contentDescription = "Select Date", tint = Color(0xFF4F46E5))
                        }
                    }
                )

                Row(verticalAlignment = Alignment.CenterVertically) {
                    Checkbox(
                        checked = dryRun,
                        onCheckedChange = { dryRun = it },
                        colors = CheckboxDefaults.colors(checkedColor = Color(0xFF4F46E5))
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Dry Run (preview only)", fontSize = 13.sp, color = Color(0xFF334155))
                }

                Button(
                    onClick = {
                        viewModel.syncPoints(
                            dryRun = dryRun,
                            since = syncSince.takeIf { it.isNotBlank() },
                            onSuccess = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() },
                            onError = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() }
                        )
                    },
                    modifier = Modifier.fillMaxWidth().height(44.dp),
                    shape = PremiumUI.ButtonShape,
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF0284C7))
                ) {
                    Icon(Icons.Outlined.Sync, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Run Auto-Sync", fontWeight = FontWeight.Bold)
                }
            }
        }
        
        Spacer(modifier = Modifier.height(80.dp))
    }
}
