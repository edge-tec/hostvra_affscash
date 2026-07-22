package net.affscash.android.ui.admin

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.Conversion
import net.affscash.android.ui.conversions.AdminConversionsUiState
import net.affscash.android.ui.conversions.AdminConversionsViewModel
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI

@Composable
fun AdminConversionItem(conversion: Conversion) {
    var expandClickId by remember { mutableStateOf(false) }

    GlassCard(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        elevation = 2.dp
    ) {
        Column(modifier = Modifier.fillMaxWidth()) {
            // Header: Offer Name & Status
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Row(modifier = Modifier.weight(1f), verticalAlignment = Alignment.CenterVertically) {
                    Box(
                        modifier = Modifier
                            .size(30.dp)
                            .clip(CircleShape)
                            .background(Color(0xFFEEF2FF)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            Icons.Outlined.LocalOffer,
                            contentDescription = null,
                            tint = Color(0xFF4F46E5),
                            modifier = Modifier.size(16.dp)
                        )
                    }
                    Spacer(modifier = Modifier.width(8.dp))
                    Text(
                        text = conversion.offerName ?: "Unknown Offer",
                        fontSize = 14.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF0F172A),
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis
                    )
                }
                Spacer(modifier = Modifier.width(6.dp))
                
                val isApproved = conversion.status.lowercase() == "approved"
                val statusColor = if (isApproved) Color(0xFF059669) else Color(0xFFDC2626)
                val statusBg = if (isApproved) Color(0xFFD1FAE5) else Color(0xFFFEE2E2)
                
                Surface(
                    shape = RoundedCornerShape(6.dp),
                    color = statusBg
                ) {
                    Text(
                        text = conversion.status.uppercase(),
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                        color = statusColor,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.ExtraBold
                    )
                }
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            
            // Affiliate Info Pill
            Surface(
                color = Color(0xFFF8FAFC),
                shape = RoundedCornerShape(8.dp),
                border = BorderStroke(1.dp, Color(0xFFE2E8F0)),
                modifier = Modifier.fillMaxWidth()
            ) {
                Row(
                    modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Icon(Icons.Outlined.Person, contentDescription = null, tint = Color(0xFF64748B), modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(
                        text = "Affiliate:",
                        fontSize = 12.sp,
                        color = Color(0xFF64748B)
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(
                        text = "${conversion.affName ?: "N/A"} (${conversion.affiliateCode ?: ""})",
                        fontSize = 12.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF0F172A),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                }
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            
            // Details Grid
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                // Left Column: Click ID & Source
                Column(modifier = Modifier.weight(1f)) {
                    Text(text = "CLICK ID", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                    Text(
                        text = if (expandClickId) conversion.clickId else if (conversion.clickId.length > 12) conversion.clickId.take(12) + "..." else conversion.clickId,
                        fontSize = 11.sp,
                        color = Color(0xFF334155),
                        fontFamily = androidx.compose.ui.text.font.FontFamily.Monospace,
                        modifier = Modifier.clickable { expandClickId = !expandClickId }
                    )
                    
                    Spacer(modifier = Modifier.height(6.dp))
                    Text(text = "SOURCE", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                    Text(text = conversion.source?.ifEmpty { "Direct / Unknown" } ?: "Direct / Unknown", fontSize = 11.sp, color = Color(0xFF334155), maxLines = 1, overflow = TextOverflow.Ellipsis)
                }
                
                Spacer(modifier = Modifier.width(8.dp))

                // Right Column: IP & Location
                Column(modifier = Modifier.weight(1f)) {
                    Text(text = "IP ADDRESS", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                    Text(text = conversion.ipAddress, fontSize = 11.sp, color = Color(0xFF334155), maxLines = 1, overflow = TextOverflow.Ellipsis)
                    
                    Spacer(modifier = Modifier.height(6.dp))
                    Text(text = "LOCATION", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                    val loc = listOfNotNull(conversion.country, conversion.region, conversion.city).filter { it.isNotBlank() }.joinToString(", ")
                    Text(text = loc.ifEmpty { "Unknown" }, fontSize = 11.sp, color = Color(0xFF334155), maxLines = 1, overflow = TextOverflow.Ellipsis)
                }
            }
            
            Spacer(modifier = Modifier.height(6.dp))
            
            // Device & OS
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(text = "Device: ${conversion.deviceType?.ifEmpty { "Unknown" } ?: "Unknown"}", fontSize = 11.sp, color = Color(0xFF64748B))
                Text(text = "OS: ${conversion.os?.ifEmpty { "Unknown" } ?: "Unknown"}", fontSize = 11.sp, color = Color(0xFF64748B))
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            HorizontalDivider(color = Color(0xFFF1F5F9))
            Spacer(modifier = Modifier.height(6.dp))

            // Footer: Timestamp & Financials
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(text = conversion.convertedAt, fontSize = 11.sp, color = Color(0xFF94A3B8))
                
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(horizontalAlignment = Alignment.End) {
                        Text("Revenue", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                        Text(
                            text = "$${(( conversion.revenue )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                            fontSize = 13.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = Color(0xFF0F172A)
                        )
                    }
                    Spacer(modifier = Modifier.width(10.dp))
                    Column(horizontalAlignment = Alignment.End) {
                        Text("Payout", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = Color(0xFF94A3B8))
                        Text(
                            text = "$${(( conversion.payout )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }",
                            fontSize = 13.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = Color(0xFF059669)
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun AdminConversionsScreen(
    viewModel: AdminConversionsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val dateRangeState by viewModel.dateRangeState.collectAsState()

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(PremiumUI.PageBackground)
    ) {
        when (val state = uiState) {
            is AdminConversionsUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = Color(0xFF4F46E5))
                }
            }
            is AdminConversionsUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(state.message, color = Color(0xFFEF4444))
                }
            }
            is AdminConversionsUiState.Success -> {
                val conversions = state.data.data

                LazyColumn(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(horizontal = 12.dp),
                    contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    item {
                        net.affscash.android.ui.components.DateRangeFilterComponent(
                            state = dateRangeState,
                            onOptionSelected = { viewModel.setDateRangeOption(it) },
                            onCustomRangeSelected = { start, end -> viewModel.setCustomDateRange(start, end) }
                        )
                    }
                    
                    item {
                        Surface(
                            modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
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
                                        .size(38.dp)
                                        .clip(RoundedCornerShape(10.dp))
                                        .background(PremiumUI.HeaderGradient),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(
                                        Icons.Outlined.TrendingUp,
                                        contentDescription = null,
                                        tint = Color.White,
                                        modifier = Modifier.size(20.dp)
                                    )
                                }
                                Spacer(modifier = Modifier.width(10.dp))
                                Column {
                                    Text(
                                        text = "All Conversions",
                                        fontSize = 17.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = Color(0xFF0F172A)
                                    )
                                    Text(
                                        text = "Track network-wide affiliate performance",
                                        fontSize = 11.sp,
                                        color = Color(0xFF64748B)
                                    )
                                }
                            }
                        }
                    }

                    if (conversions.isEmpty()) {
                        item {
                            Box(modifier = Modifier.fillMaxWidth().height(250.dp), contentAlignment = Alignment.Center) {
                                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                    Icon(Icons.Outlined.Analytics, contentDescription = null, modifier = Modifier.size(48.dp), tint = Color(0xFF94A3B8))
                                    Spacer(modifier = Modifier.height(6.dp))
                                    Text("No conversions found", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                                }
                            }
                        }
                    } else {
                        items(conversions) { conversion ->
                            AdminConversionItem(conversion = conversion)
                        }
                    }
                }
            }
        }
    }
}
