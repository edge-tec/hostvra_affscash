package net.affscash.android.ui.screens.admin.autohide

import androidx.compose.animation.AnimatedVisibility
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
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import net.affscash.android.data.model.AdminAutoHideCreateRequest
import net.affscash.android.ui.components.CustomDropdownMenu
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminAutoHideScreen(
    viewModel: AdminAutoHideViewModel = viewModel(),
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(uiState.error) {
        uiState.error?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearError()
        }
    }
    LaunchedEffect(uiState.successMessage) {
        uiState.successMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearSuccessMessage()
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .background(PremiumUI.PageBackground)
                .padding(padding)
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
                            net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.ArrowBack, contentDescription = "Back", tint = Color(0xFF0F172A))
                        }
                        Box(
                            modifier = Modifier
                                .size(38.dp)
                                .clip(RoundedCornerShape(10.dp))
                                .background(PremiumUI.HeaderGradient),
                            contentAlignment = Alignment.Center
                        ) {
                            net.affscash.android.ui.dashboard.GradientIcon(
                                Icons.Outlined.VisibilityOff,
                                contentDescription = null,
                                tint = Color.White,
                                modifier = Modifier.size(20.dp)
                            )
                        }
                        Spacer(modifier = Modifier.width(10.dp))
                        Column {
                            Text(
                                text = "Auto Hide Conversions",
                                fontSize = 17.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF0F172A)
                            )
                            Text(
                                text = "Automated rules & payout control",
                                fontSize = 11.sp,
                                color = Color(0xFF64748B)
                            )
                        }
                    }
                }
            }

            // Stats Cards Row
            uiState.stats?.let { stats ->
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .horizontalScroll(rememberScrollState())
                        .padding(horizontal = 12.dp, vertical = 4.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    AutoHideStatCard3D("Total Hidden", "${stats.total_hidden}", Color(0xFFEF4444), Modifier.width(115.dp))
                    AutoHideStatCard3D("Payout Saved", "$${(( stats.payout_saved )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", Color(0xFF10B981), Modifier.width(125.dp))
                    AutoHideStatCard3D("Active Rules", "${stats.active_rules}", Color(0xFF4F46E5), Modifier.width(115.dp))
                    AutoHideStatCard3D("Total Rules", "${stats.total_rules}", Color(0xFF8B5CF6), Modifier.width(115.dp))
                }
            }

            // 3D Segmented Tab Pills
            Surface(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 12.dp, vertical = 6.dp),
                shape = RoundedCornerShape(16.dp),
                color = Color(0xFFF1F5F9),
                border = BorderStroke(1.dp, Color(0xFFE2E8F0))
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth().padding(4.dp),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    val tabs = listOf("rules" to "Rules", "hidden" to "Hidden", "new_rule" to "+ New Rule")
                    tabs.forEach { tabItem ->
                        val isSelected = uiState.tab == tabItem.first
                        Surface(
                            onClick = { viewModel.updateTab(tabItem.first) },
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
                                    text = tabItem.second,
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
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth(), color = Color(0xFF4F46E5))
            }

            // Content Area
            Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
                when (uiState.tab) {
                    "rules" -> {
                        if (uiState.rules.isEmpty()) {
                            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                                Text("No auto-hide rules created yet.", color = Color(0xFF64748B))
                            }
                        } else {
                            LazyColumn(
                                modifier = Modifier.fillMaxSize().padding(horizontal = 12.dp),
                                contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
                                verticalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                items(uiState.rules) { rule ->
                                    GlassCard(elevation = 2.dp) {
                                        Column(modifier = Modifier.fillMaxWidth()) {
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.SpaceBetween,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Text(rule.name, fontSize = 15.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF0F172A))
                                                
                                                val isActive = rule.is_active == 1
                                                Surface(
                                                    shape = RoundedCornerShape(6.dp),
                                                    color = if (isActive) Color(0xFFD1FAE5) else Color(0xFFF1F5F9)
                                                ) {
                                                    Text(
                                                        text = if (isActive) "ACTIVE" else "INACTIVE",
                                                        fontSize = 10.sp,
                                                        fontWeight = FontWeight.ExtraBold,
                                                        color = if (isActive) Color(0xFF059669) else Color(0xFF64748B),
                                                        modifier = Modifier.padding(horizontal = 7.dp, vertical = 3.dp)
                                                    )
                                                }
                                            }
                                            
                                            Spacer(modifier = Modifier.height(6.dp))
                                            
                                            val target = when(rule.type) {
                                                "offer" -> rule.offer_name ?: "Unknown Offer"
                                                "affiliate" -> rule.aff_name ?: "Unknown Affiliate"
                                                else -> "All Conversions"
                                            }
                                            
                                            Text("Type: ${rule.type.uppercase()} • Target: $target", fontSize = 12.sp, color = Color(0xFF475569))
                                            
                                            Spacer(modifier = Modifier.height(4.dp))
                                            Row(verticalAlignment = Alignment.CenterVertically) {
                                                Surface(color = Color(0xFFFEE2E2), shape = RoundedCornerShape(6.dp)) {
                                                    Text("Hide: ${rule.hide_percent}%", modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color(0xFFDC2626))
                                                }
                                                if (!rule.reason.isNullOrEmpty()) {
                                                    Spacer(modifier = Modifier.width(6.dp))
                                                    Text("Reason: ${rule.reason}", fontSize = 11.sp, color = Color(0xFF64748B), maxLines = 1, overflow = TextOverflow.Ellipsis)
                                                }
                                            }
                                            
                                            Spacer(modifier = Modifier.height(10.dp))
                                            
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.End,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Button(
                                                    onClick = { viewModel.toggleRule(rule.id) },
                                                    shape = RoundedCornerShape(10.dp),
                                                    modifier = Modifier.height(34.dp),
                                                    colors = ButtonDefaults.buttonColors(
                                                        containerColor = if (rule.is_active == 1) Color(0xFF64748B) else Color(0xFF059669)
                                                    ),
                                                    contentPadding = PaddingValues(horizontal = 14.dp)
                                                ) {
                                                    Text(if (rule.is_active == 1) "Disable" else "Enable", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                                                }
                                                Spacer(modifier = Modifier.width(6.dp))
                                                Button(
                                                    onClick = { viewModel.deleteRule(rule.id) },
                                                    shape = RoundedCornerShape(10.dp),
                                                    modifier = Modifier.height(34.dp),
                                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626)),
                                                    contentPadding = PaddingValues(horizontal = 14.dp)
                                                ) {
                                                    Text("Delete", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    "hidden" -> {
                        if (uiState.hiddenConversions.isEmpty()) {
                            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                                Text("No hidden conversions recorded.", color = Color(0xFF64748B))
                            }
                        } else {
                            LazyColumn(
                                modifier = Modifier.fillMaxSize().padding(horizontal = 12.dp),
                                contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
                                verticalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                items(uiState.hiddenConversions) { conv ->
                                    GlassCard(elevation = 2.dp) {
                                        Column(modifier = Modifier.fillMaxWidth()) {
                                            Surface(color = Color(0xFFF8FAFC), shape = RoundedCornerShape(6.dp), border = BorderStroke(1.dp, Color(0xFFE2E8F0))) {
                                                Text(
                                                    text = "ID: ${conv.conversion_id}",
                                                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                                                    fontSize = 11.sp,
                                                    fontWeight = FontWeight.ExtraBold,
                                                    color = Color(0xFF0F172A),
                                                    maxLines = 1,
                                                    overflow = TextOverflow.Ellipsis
                                                )
                                            }
                                            
                                            Spacer(modifier = Modifier.height(6.dp))
                                            Text("Offer: ${conv.offer_name}", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A), maxLines = 1, overflow = TextOverflow.Ellipsis)
                                            Text("Affiliate: ${conv.aff_name} (${conv.affiliate_code})", fontSize = 12.sp, color = Color(0xFF475569))
                                            
                                            Spacer(modifier = Modifier.height(4.dp))
                                            Row(verticalAlignment = Alignment.CenterVertically) {
                                                Text(
                                                    text = "Payout Saved: $${(( conv.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                                                    fontSize = 12.sp,
                                                    fontWeight = FontWeight.ExtraBold,
                                                    color = Color(0xFF059669)
                                                )
                                                if (!conv.hide_reason.isNullOrEmpty()) {
                                                    Spacer(modifier = Modifier.width(8.dp))
                                                    Text("Reason: ${conv.hide_reason}", fontSize = 11.sp, color = Color(0xFF64748B))
                                                }
                                            }
                                            
                                            Spacer(modifier = Modifier.height(10.dp))
                                            Button(
                                                onClick = { viewModel.unhideConversion(conv.conversion_id) },
                                                shape = PremiumUI.ButtonShape,
                                                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5)),
                                                modifier = Modifier.fillMaxWidth().height(38.dp)
                                            ) {
                                                net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.Restore, contentDescription = null, modifier = Modifier.size(16.dp))
                                                Spacer(modifier = Modifier.width(6.dp))
                                                Text("Unhide & Restore Balance", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    "new_rule" -> {
                        CreateRuleForm3D(uiState, viewModel)
                    }
                }
            }
        }
    }
}

@Composable
fun AutoHideStatCard3D(title: String, value: String, accentColor: Color, modifier: Modifier = Modifier) {
    Surface(
        modifier = modifier,
        shape = RoundedCornerShape(14.dp),
        color = Color.White,
        shadowElevation = 2.dp,
        border = BorderStroke(1.dp, Color(0xFFE2E8F0))
    ) {
        Column(
            modifier = Modifier.padding(vertical = 10.dp, horizontal = 8.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(title, fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF64748B))
            Spacer(modifier = Modifier.height(2.dp))
            Text(value, fontSize = 15.sp, fontWeight = FontWeight.ExtraBold, color = accentColor)
        }
    }
}

@Composable
fun CreateRuleForm3D(uiState: AdminAutoHideState, viewModel: AdminAutoHideViewModel) {
    var name by remember { mutableStateOf("") }
    var type by remember { mutableStateOf("global") }
    var offerId by remember { mutableStateOf<String?>(null) }
    var affiliateId by remember { mutableStateOf<String?>(null) }
    var hidePercent by remember { mutableStateOf(10f) }
    var reason by remember { mutableStateOf("") }
    var applyExisting by remember { mutableStateOf(false) }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(horizontal = 12.dp, vertical = 4.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp)
    ) {
        GlassCard(elevation = 2.dp) {
            Column(
                modifier = Modifier.fillMaxWidth(),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                OutlinedTextField(
                    value = name,
                    onValueChange = { name = it },
                    placeholder = { Text("Rule Name *", fontSize = 13.sp) },
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

                CustomDropdownMenu(
                    options = listOf("global" to "Global - All conversions", "offer" to "Offer", "affiliate" to "Affiliate"),
                    selectedOption = type,
                    onOptionSelected = { type = it },
                    label = "Rule Type",
                    modifier = Modifier.fillMaxWidth()
                )

                if (type == "offer") {
                    val offerOptions = uiState.offers.map { it.id.toString() to (it.name ?: "") }
                    CustomDropdownMenu(
                        options = offerOptions,
                        selectedOption = offerId ?: "",
                        onOptionSelected = { offerId = it },
                        label = "Select Offer *",
                        modifier = Modifier.fillMaxWidth()
                    )
                }

                if (type == "affiliate") {
                    val affOptions = uiState.affiliates.map { it.id.toString() to (it.name ?: "") }
                    CustomDropdownMenu(
                        options = affOptions,
                        selectedOption = affiliateId ?: "",
                        onOptionSelected = { affiliateId = it },
                        label = "Select Affiliate *",
                        modifier = Modifier.fillMaxWidth()
                    )
                }

                Column(modifier = Modifier.fillMaxWidth()) {
                    Text("Hide Percentage: ${hidePercent.toInt()}%", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                    Slider(
                        value = hidePercent,
                        onValueChange = { hidePercent = it },
                        valueRange = 1f..100f,
                        colors = SliderDefaults.colors(
                            thumbColor = Color(0xFF4F46E5),
                            activeTrackColor = Color(0xFF4F46E5)
                        )
                    )
                }

                OutlinedTextField(
                    value = reason,
                    onValueChange = { reason = it },
                    placeholder = { Text("Internal Reason (Optional)", fontSize = 13.sp) },
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

                Surface(
                    color = Color(0xFFFFF7ED),
                    shape = RoundedCornerShape(10.dp),
                    border = BorderStroke(1.dp, Color(0xFFFFEDD5))
                ) {
                    Row(
                        modifier = Modifier.padding(10.dp),
                        verticalAlignment = Alignment.Top
                    ) {
                        Checkbox(
                            checked = applyExisting,
                            onCheckedChange = { applyExisting = it },
                            colors = CheckboxDefaults.colors(checkedColor = Color(0xFFEA580C))
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Column {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.Warning, contentDescription = null, tint = Color(0xFFEA580C), modifier = Modifier.size(16.dp))
                                Spacer(Modifier.width(4.dp))
                                Text("Apply to existing conversions retroactively", fontWeight = FontWeight.Bold, fontSize = 12.sp, color = Color(0xFFEA580C))
                            }
                            Text(
                                "Will hide past conversions matching this rule at the set percentage. Affiliate balances will be adjusted. This cannot be undone automatically.",
                                fontSize = 11.sp,
                                color = Color(0xFFC2410C),
                                lineHeight = 15.sp
                            )
                        }
                    }
                }

                Button(
                    onClick = {
                        viewModel.createRule(
                            AdminAutoHideCreateRequest(
                                name = name,
                                type = type,
                                offer_id = offerId?.toIntOrNull(),
                                affiliate_id = affiliateId?.toIntOrNull(),
                                hide_percent = hidePercent.toDouble(),
                                reason = reason,
                                apply_existing = applyExisting
                            )
                        )
                    },
                    modifier = Modifier.fillMaxWidth().height(46.dp),
                    shape = PremiumUI.ButtonShape,
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5)),
                    enabled = name.isNotBlank() && (type == "global" || (type == "offer" && offerId != null) || (type == "affiliate" && affiliateId != null))
                ) {
                    net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.AddCircle, contentDescription = null, modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Create Auto-Hide Rule", fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}
