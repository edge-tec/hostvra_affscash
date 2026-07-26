package net.affscash.android.ui.admin

import android.widget.Toast
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
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
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminPrivateOffersScreen(
    onNavigateToDetail: (Int) -> Unit,
    viewModel: AdminPrivateOffersViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val actionMessage by viewModel.actionMessage.collectAsState()
    val context = LocalContext.current

    LaunchedEffect(Unit) {
        viewModel.loadDashboard()
    }

    LaunchedEffect(actionMessage) {
        actionMessage?.let {
            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
            viewModel.clearActionMessage()
        }
    }

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
                verticalAlignment = Alignment.CenterVertically
            ) {
                Box(
                    modifier = Modifier
                        .size(42.dp)
                        .clip(RoundedCornerShape(12.dp))
                        .background(PremiumUI.HeaderGradient),
                    contentAlignment = Alignment.Center
                ) {
                    net.affscash.android.ui.dashboard.GradientIcon(
                        Icons.Outlined.VpnKey,
                        contentDescription = null,
                        tint = Color.White,
                        modifier = Modifier.size(22.dp)
                    )
                }
                Spacer(modifier = Modifier.width(10.dp))
                Column {
                    Text(
                        text = "Private Offers",
                        fontSize = 18.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF0F172A)
                    )
                    Text(
                        text = "Manage restricted-access offers",
                        fontSize = 12.sp,
                        color = Color(0xFF64748B)
                    )
                }
            }
        }

        when (val state = uiState) {
            is AdminPrivateOffersUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = Color(0xFF4F46E5))
                }
            }
            is AdminPrivateOffersUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(state.message, color = Color(0xFFEF4444))
                }
            }
            is AdminPrivateOffersUiState.Success -> {
                val data = state.data

                LazyColumn(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(horizontal = 12.dp),
                    contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
                    verticalArrangement = Arrangement.spacedBy(14.dp)
                ) {
                    // Convert Section
                    item {
                        GlassCard(elevation = 3.dp) {
                            Column(modifier = Modifier.fillMaxWidth()) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Box(
                                        modifier = Modifier
                                            .size(32.dp)
                                            .clip(CircleShape)
                                            .background(Color(0xFFEEF2FF)),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.Security, contentDescription = null, tint = Color(0xFF4F46E5), modifier = Modifier.size(18.dp))
                                    }
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Text("Convert Offer to Private", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                                }
                                
                                Spacer(modifier = Modifier.height(12.dp))
                                
                                var expanded by remember { mutableStateOf(false) }
                                var selectedOfferId by remember { mutableStateOf<Int?>(null) }
                                
                                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                                    ExposedDropdownMenuBox(
                                        expanded = expanded,
                                        onExpandedChange = { expanded = !expanded },
                                        modifier = Modifier.fillMaxWidth()
                                    ) {
                                        val selectedOffer = data.convertableOffers.find { it.id == selectedOfferId }
                                        OutlinedTextField(
                                            value = selectedOffer?.let { "OFF-${it.id} - ${it.name}" } ?: "Select an offer...",
                                            onValueChange = {},
                                            readOnly = true,
                                            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                                            leadingIcon = { net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.LocalOffer, contentDescription = null, tint = Color(0xFF64748B)) },
                                            modifier = Modifier.menuAnchor().fillMaxWidth(),
                                            shape = RoundedCornerShape(12.dp),
                                            colors = OutlinedTextFieldDefaults.colors(
                                                focusedBorderColor = Color(0xFF4F46E5),
                                                unfocusedBorderColor = Color(0xFFE2E8F0),
                                                focusedContainerColor = Color(0xFFF8FAFC),
                                                unfocusedContainerColor = Color(0xFFF8FAFC)
                                            )
                                        )
                                        ExposedDropdownMenu(
                                            expanded = expanded,
                                            onDismissRequest = { expanded = false }
                                        ) {
                                            data.convertableOffers.forEach { offer ->
                                                DropdownMenuItem(
                                                    text = { Text("OFF-${offer.id} - ${offer.name}", maxLines = 1, overflow = TextOverflow.Ellipsis) },
                                                    onClick = {
                                                        selectedOfferId = offer.id
                                                        expanded = false
                                                    }
                                                )
                                            }
                                        }
                                    }
                                    
                                    Button(
                                        onClick = {
                                            selectedOfferId?.let { viewModel.markOfferPrivate(it) }
                                            selectedOfferId = null
                                        },
                                        enabled = selectedOfferId != null,
                                        modifier = Modifier.fillMaxWidth().height(46.dp),
                                        shape = PremiumUI.ButtonShape,
                                        colors = ButtonDefaults.buttonColors(
                                            containerColor = Color(0xFF4F46E5),
                                            disabledContainerColor = Color(0xFFE2E8F0)
                                        )
                                    ) {
                                        net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.Lock, contentDescription = null, modifier = Modifier.size(18.dp))
                                        Spacer(modifier = Modifier.width(6.dp))
                                        Text("Mark as Private", fontWeight = FontWeight.Bold)
                                    }
                                }
                                
                                Spacer(modifier = Modifier.height(10.dp))
                                Surface(
                                    color = Color(0xFFF8FAFC),
                                    shape = RoundedCornerShape(10.dp),
                                    border = BorderStroke(1.dp, Color(0xFFE2E8F0))
                                ) {
                                    Row(
                                        verticalAlignment = Alignment.Top,
                                        modifier = Modifier.padding(10.dp)
                                    ) {
                                        net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.Info, contentDescription = null, tint = Color(0xFF64748B), modifier = Modifier.size(16.dp))
                                        Spacer(modifier = Modifier.width(6.dp))
                                        Text(
                                            "Once private, the offer is hidden from all affiliates. You must manually grant access from the offer's manage page.",
                                            fontSize = 12.sp,
                                            color = Color(0xFF64748B),
                                            lineHeight = 16.sp
                                        )
                                    }
                                }
                            }
                        }
                    }

                    // Active Private Offers Section
                    item {
                        GlassCard(elevation = 3.dp) {
                            Column(modifier = Modifier.fillMaxWidth()) {
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        Box(
                                            modifier = Modifier
                                                .size(32.dp)
                                                .clip(CircleShape)
                                                .background(Color(0xFFD1FAE5)),
                                            contentAlignment = Alignment.Center
                                        ) {
                                            net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.VerifiedUser, contentDescription = null, tint = Color(0xFF059669), modifier = Modifier.size(18.dp))
                                        }
                                        Spacer(modifier = Modifier.width(8.dp))
                                        Text("Active Private Offers", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                                    }
                                    
                                    Surface(
                                        shape = RoundedCornerShape(12.dp),
                                        color = Color(0xFFEEF2FF)
                                    ) {
                                        Text(
                                            "${data.privateOffers.size}",
                                            modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp),
                                            fontSize = 12.sp,
                                            fontWeight = FontWeight.ExtraBold,
                                            color = Color(0xFF4F46E5)
                                        )
                                    }
                                }
                                
                                Spacer(modifier = Modifier.height(10.dp))
                                HorizontalDivider(color = Color(0xFFF1F5F9))
                                Spacer(modifier = Modifier.height(10.dp))

                                if (data.privateOffers.isEmpty()) {
                                    Box(modifier = Modifier.fillMaxWidth().padding(24.dp), contentAlignment = Alignment.Center) {
                                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                            net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.VisibilityOff, contentDescription = null, tint = Color(0xFF94A3B8), modifier = Modifier.size(40.dp))
                                            Spacer(modifier = Modifier.height(4.dp))
                                            Text("No private offers currently.", color = Color(0xFF64748B), fontSize = 13.sp)
                                        }
                                    }
                                } else {
                                    data.privateOffers.forEachIndexed { index, offer ->
                                        Column(modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp)) {
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.SpaceBetween,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.weight(1f)) {
                                                    Surface(
                                                        shape = RoundedCornerShape(6.dp),
                                                        color = Color(0xFFEEF2FF)
                                                    ) {
                                                        Text("OFF-${offer.id}", modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), fontSize = 11.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF4F46E5))
                                                    }
                                                    Spacer(modifier = Modifier.width(6.dp))
                                                    Text(offer.name, fontWeight = FontWeight.Bold, fontSize = 14.sp, color = Color(0xFF0F172A), maxLines = 1, overflow = TextOverflow.Ellipsis)
                                                }
                                            }
                                            
                                            Spacer(modifier = Modifier.height(4.dp))
                                            Row(verticalAlignment = Alignment.CenterVertically) {
                                                Text("${offer.payout_type} ${offer.payout ?: "0"}", fontSize = 12.sp, color = Color(0xFF64748B))
                                                Spacer(modifier = Modifier.width(10.dp))
                                                Surface(color = Color(0xFFECFDF5), shape = RoundedCornerShape(4.dp)) {
                                                    Text("${offer.access_count ?: 0} Granted", modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), fontSize = 11.sp, color = Color(0xFF059669), fontWeight = FontWeight.Bold)
                                                }
                                            }
                                            
                                            Spacer(modifier = Modifier.height(8.dp))
                                            
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.End,
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                OutlinedButton(
                                                    onClick = { viewModel.makeOfferPublic(offer.id) },
                                                    shape = RoundedCornerShape(10.dp),
                                                    modifier = Modifier.height(34.dp),
                                                    contentPadding = PaddingValues(horizontal = 12.dp),
                                                    border = BorderStroke(1.dp, Color(0xFFCBD5E1))
                                                ) {
                                                    net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.Public, contentDescription = null, modifier = Modifier.size(14.dp), tint = Color(0xFF475569))
                                                    Spacer(modifier = Modifier.width(4.dp))
                                                    Text("Make Public", fontSize = 12.sp, color = Color(0xFF475569))
                                                }
                                                Spacer(modifier = Modifier.width(6.dp))
                                                Button(
                                                    onClick = { onNavigateToDetail(offer.id) },
                                                    shape = RoundedCornerShape(10.dp),
                                                    modifier = Modifier.height(34.dp),
                                                    contentPadding = PaddingValues(horizontal = 12.dp),
                                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                                                ) {
                                                    net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.ManageAccounts, contentDescription = null, modifier = Modifier.size(14.dp))
                                                    Spacer(modifier = Modifier.width(4.dp))
                                                    Text("Manage Access", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                                                }
                                            }
                                        }
                                        if (index < data.privateOffers.lastIndex) {
                                            HorizontalDivider(color = Color(0xFFF1F5F9), modifier = Modifier.padding(vertical = 6.dp))
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Recent Activity Log
                    item {
                        GlassCard(elevation = 3.dp) {
                            Column(modifier = Modifier.fillMaxWidth()) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Box(
                                        modifier = Modifier
                                            .size(32.dp)
                                            .clip(CircleShape)
                                            .background(Color(0xFFFEF3C7)),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        net.affscash.android.ui.dashboard.GradientIcon(Icons.Outlined.History, contentDescription = null, tint = Color(0xFFD97706), modifier = Modifier.size(18.dp))
                                    }
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Text("Recent Activity Log", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                                }
                                
                                Spacer(modifier = Modifier.height(10.dp))
                                HorizontalDivider(color = Color(0xFFF1F5F9))
                                Spacer(modifier = Modifier.height(10.dp))
                                
                                if (data.recentLog.isEmpty()) {
                                    Box(modifier = Modifier.fillMaxWidth().padding(24.dp), contentAlignment = Alignment.Center) {
                                        Text("No recent activity.", color = Color(0xFF64748B), fontSize = 13.sp)
                                    }
                                } else {
                                    data.recentLog.forEachIndexed { index, log ->
                                        Row(
                                            modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp),
                                            verticalAlignment = Alignment.Top
                                        ) {
                                            val isNegative = log.action == "deny" || log.action == "disable" || log.action == "remove"
                                            val badgeBg = if (isNegative) Color(0xFFFEE2E2) else Color(0xFFD1FAE5)
                                            val badgeText = if (isNegative) Color(0xFFDC2626) else Color(0xFF059669)
                                            
                                            Surface(
                                                shape = RoundedCornerShape(6.dp),
                                                color = badgeBg
                                            ) {
                                                Text(
                                                    text = log.action.uppercase(),
                                                    fontSize = 10.sp,
                                                    fontWeight = FontWeight.ExtraBold,
                                                    color = badgeText,
                                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 3.dp)
                                                )
                                            }
                                            
                                            Spacer(modifier = Modifier.width(8.dp))
                                            
                                            Column(modifier = Modifier.weight(1f)) {
                                                Row(
                                                    modifier = Modifier.fillMaxWidth(),
                                                    horizontalArrangement = Arrangement.SpaceBetween,
                                                    verticalAlignment = Alignment.CenterVertically
                                                ) {
                                                    Text(log.offer_name ?: "Unknown Offer", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A), maxLines = 1, overflow = TextOverflow.Ellipsis)
                                                    Text(log.created_at, fontSize = 11.sp, color = Color(0xFF94A3B8))
                                                }
                                                if (log.aff_name != null) {
                                                    Text("Affiliate: ${log.aff_name} (${log.affiliate_code})", fontSize = 12.sp, color = Color(0xFF475569))
                                                }
                                                if (log.details != null) {
                                                    Text(log.details, fontSize = 11.sp, color = Color(0xFF64748B))
                                                }
                                            }
                                        }
                                        if (index < data.recentLog.lastIndex) {
                                            HorizontalDivider(color = Color(0xFFF1F5F9), modifier = Modifier.padding(vertical = 4.dp))
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            else -> {}
        }
    }
}
