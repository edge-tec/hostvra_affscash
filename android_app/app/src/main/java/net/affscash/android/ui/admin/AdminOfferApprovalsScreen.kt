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
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.filled.FilterList
import androidx.compose.material.icons.filled.Search
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
import net.affscash.android.data.model.ManagerOfferApprovalRequest
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminOfferApprovalsScreen(
    viewModel: AdminOfferApprovalsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    LaunchedEffect(uiState) {
        if (uiState is AdminOfferApprovalsUiState.Error) {
            Toast.makeText(context, (uiState as AdminOfferApprovalsUiState.Error).message, Toast.LENGTH_SHORT).show()
        }
    }

    var showFilters by remember { mutableStateOf(false) }

    if (showFilters && uiState is AdminOfferApprovalsUiState.Success) {
        val state = uiState as AdminOfferApprovalsUiState.Success
        ModalBottomSheet(
            onDismissRequest = { showFilters = false },
            sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true),
            dragHandle = { BottomSheetDefaults.DragHandle() },
            modifier = Modifier.fillMaxHeight(0.85f),
            containerColor = Color.White
        ) {
            Column(
                modifier = Modifier
                    .padding(16.dp)
                    .fillMaxWidth(),
                verticalArrangement = Arrangement.spacedBy(14.dp)
            ) {
                Text("Filter Approval Requests", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                
                var searchQuery by remember { mutableStateOf(state.filterAff) }
                OutlinedTextField(
                    value = searchQuery,
                    onValueChange = { searchQuery = it },
                    placeholder = { Text("Search by name, email or code", fontSize = 13.sp) },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search", tint = Color(0xFF64748B)) },
                    trailingIcon = {
                        if (searchQuery.isNotEmpty()) {
                            IconButton(onClick = { 
                                searchQuery = ""
                                viewModel.loadData(aff = "") 
                            }) {
                                Icon(Icons.Default.Clear, contentDescription = "Clear", tint = Color(0xFF64748B))
                            }
                        }
                    },
                    shape = RoundedCornerShape(12.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = Color(0xFF4F46E5),
                        unfocusedBorderColor = Color(0xFFE2E8F0),
                        focusedContainerColor = Color(0xFFF8FAFC),
                        unfocusedContainerColor = Color(0xFFF8FAFC)
                    )
                )

                var expanded by remember { mutableStateOf(false) }
                ExposedDropdownMenuBox(
                    expanded = expanded,
                    onExpandedChange = { expanded = it },
                    modifier = Modifier.fillMaxWidth()
                ) {
                    val selectedOffer = state.allOffers.find { it.id == state.filterOfferId }
                    OutlinedTextField(
                        value = selectedOffer?.name ?: "All Offers",
                        onValueChange = {},
                        readOnly = true,
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                        modifier = Modifier.menuAnchor().fillMaxWidth(),
                        singleLine = true,
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
                        DropdownMenuItem(
                            text = { Text("All Offers") },
                            onClick = {
                                viewModel.loadData(offerId = null)
                                expanded = false
                            }
                        )
                        state.allOffers.forEach { offer ->
                            DropdownMenuItem(
                                text = { Text(offer.name) },
                                onClick = {
                                    viewModel.loadData(offerId = offer.id)
                                    expanded = false
                                }
                            )
                        }
                    }
                }

                Button(
                    onClick = { 
                        viewModel.loadData(aff = searchQuery)
                        showFilters = false
                    },
                    modifier = Modifier.fillMaxWidth().height(46.dp),
                    shape = PremiumUI.ButtonShape,
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                ) {
                    Icon(Icons.Default.FilterList, contentDescription = "Apply Filters", modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Apply Filters", fontWeight = FontWeight.Bold)
                }
            }
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
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Box(
                        modifier = Modifier
                            .size(42.dp)
                            .clip(RoundedCornerShape(12.dp))
                            .background(PremiumUI.HeaderGradient),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            Icons.Outlined.AssignmentTurnedIn,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(22.dp)
                        )
                    }
                    Spacer(modifier = Modifier.width(10.dp))
                    Column {
                        Text(
                            text = "Offer Approvals",
                            fontSize = 18.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF0F172A)
                        )
                        Text(
                            text = "Review affiliate access requests",
                            fontSize = 12.sp,
                            color = Color(0xFF64748B)
                        )
                    }
                }

                Surface(
                    onClick = { showFilters = true },
                    shape = RoundedCornerShape(12.dp),
                    color = Color(0xFFF8FAFC),
                    border = BorderStroke(1.dp, Color(0xFFE2E8F0))
                ) {
                    Box(
                        modifier = Modifier.padding(10.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            Icons.Outlined.FilterList,
                            contentDescription = "Filters",
                            tint = Color(0xFF4F46E5),
                            modifier = Modifier.size(20.dp)
                        )
                    }
                }
            }
        }

        when (val state = uiState) {
            is AdminOfferApprovalsUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = Color(0xFF4F46E5))
                }
            }
            is AdminOfferApprovalsUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(state.message, color = Color(0xFFEF4444))
                }
            }
            is AdminOfferApprovalsUiState.Success -> {
                // Status Tabs
                val tabs = listOf("pending" to "Pending", "approved" to "Approved", "rejected" to "Rejected", "all" to "All")
                
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
                        tabs.forEach { (key, title) ->
                            val isSelected = state.filterStatus == key
                            val badgeCount = if (key == "pending") state.pendingCount else null

                            Surface(
                                onClick = { viewModel.loadData(status = key) },
                                modifier = Modifier.weight(1f),
                                shape = RoundedCornerShape(12.dp),
                                color = if (isSelected) Color.White else Color.Transparent,
                                shadowElevation = if (isSelected) 2.dp else 0.dp
                            ) {
                                Row(
                                    modifier = Modifier.padding(vertical = 8.dp),
                                    horizontalArrangement = Arrangement.Center,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Text(
                                        text = title,
                                        fontSize = 12.sp,
                                        fontWeight = if (isSelected) FontWeight.ExtraBold else FontWeight.Medium,
                                        color = if (isSelected) Color(0xFF4F46E5) else Color(0xFF64748B)
                                    )
                                    if (badgeCount != null && badgeCount > 0) {
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Surface(
                                            color = Color(0xFFEF4444),
                                            shape = CircleShape
                                        ) {
                                            Text(
                                                text = "$badgeCount",
                                                color = Color.White,
                                                fontSize = 10.sp,
                                                fontWeight = FontWeight.Bold,
                                                modifier = Modifier.padding(horizontal = 5.dp, vertical = 1.dp)
                                            )
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // List Area
                Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
                    if (state.requests.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                Box(
                                    modifier = Modifier
                                        .size(64.dp)
                                        .clip(CircleShape)
                                        .background(Color(0xFFF1F5F9)),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(Icons.Outlined.Search, contentDescription = null, modifier = Modifier.size(32.dp), tint = Color(0xFF94A3B8))
                                }
                                Spacer(modifier = Modifier.height(10.dp))
                                Text("No requests found", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                                Text("No approval requests at this time.", fontSize = 12.sp, color = Color(0xFF64748B))
                            }
                        }
                    } else {
                        LazyColumn(
                            modifier = Modifier.fillMaxSize().padding(horizontal = 12.dp),
                            contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            items(state.requests) { request ->
                                AdminApprovalRequestCard(
                                    request = request,
                                    onReview = { action -> viewModel.reviewRequest(request.affiliateId, request.offerId, action) }
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun AdminApprovalRequestCard(
    request: ManagerOfferApprovalRequest,
    onReview: (String) -> Unit
) {
    GlassCard(elevation = 2.dp) {
        Column(modifier = Modifier.fillMaxWidth()) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = request.offerName,
                        fontWeight = FontWeight.Bold,
                        fontSize = 15.sp,
                        color = Color(0xFF0F172A),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    Spacer(modifier = Modifier.height(2.dp))
                    Text(
                        text = "Payout: $${(( request.payoutAmount )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } } (${request.payoutType})",
                        fontSize = 12.sp,
                        color = Color(0xFF059669),
                        fontWeight = FontWeight.SemiBold
                    )
                    if (request.offerCategory != null) {
                        Text(text = "Category: ${request.offerCategory}", fontSize = 11.sp, color = Color(0xFF64748B))
                    }
                }
                Spacer(modifier = Modifier.width(6.dp))
                AdminApprovalStatusBadge(status = request.status)
            }

            Spacer(modifier = Modifier.height(8.dp))
            HorizontalDivider(color = Color(0xFFF1F5F9))
            Spacer(modifier = Modifier.height(8.dp))

            Text("Affiliate Details", fontWeight = FontWeight.Bold, fontSize = 12.sp, color = Color(0xFF94A3B8))
            Spacer(modifier = Modifier.height(2.dp))
            Text(text = "${request.affiliateName} (${request.affiliateCode})", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
            Text(text = request.affiliateEmail, fontSize = 12.sp, color = Color(0xFF64748B))
            
            Spacer(modifier = Modifier.height(6.dp))
            
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Surface(color = Color(0xFFF8FAFC), shape = RoundedCornerShape(6.dp), border = BorderStroke(1.dp, Color(0xFFE2E8F0))) {
                    Text(text = "Clicks: ${request.totalClicks}", modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), fontSize = 11.sp, color = Color(0xFF475569))
                }
                Surface(color = Color(0xFFEEF2FF), shape = RoundedCornerShape(6.dp)) {
                    Text(text = "Conversions: ${request.totalConversions}", modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color(0xFF4F46E5))
                }
            }

            if (!request.promotionDescription.isNullOrBlank()) {
                Spacer(modifier = Modifier.height(6.dp))
                Text("Promotion Plan:", fontWeight = FontWeight.SemiBold, fontSize = 11.sp, color = Color(0xFF64748B))
                Text(text = request.promotionDescription, fontSize = 12.sp, color = Color(0xFF334155))
            }

            if (request.status == "pending") {
                Spacer(modifier = Modifier.height(10.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    OutlinedButton(
                        onClick = { onReview("reject") },
                        shape = RoundedCornerShape(10.dp),
                        modifier = Modifier.height(34.dp),
                        border = BorderStroke(1.dp, Color(0xFFFCA5A5)),
                        contentPadding = PaddingValues(horizontal = 14.dp)
                    ) {
                        Text("Reject", fontSize = 12.sp, color = Color(0xFFDC2626), fontWeight = FontWeight.Bold)
                    }
                    Spacer(modifier = Modifier.width(6.dp))
                    Button(
                        onClick = { onReview("approve") },
                        shape = RoundedCornerShape(10.dp),
                        modifier = Modifier.height(34.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF059669)),
                        contentPadding = PaddingValues(horizontal = 14.dp)
                    ) {
                        Text("Approve", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                    }
                }
            } else if (request.approvedAt != null) {
                Spacer(modifier = Modifier.height(6.dp))
                Text(text = "Actioned at: ${request.approvedAt}", fontSize = 11.sp, color = Color(0xFF94A3B8))
            }
        }
    }
}

@Composable
fun AdminApprovalStatusBadge(status: String) {
    val (color, text) = when (status) {
        "approved" -> Color(0xFFD1FAE5) to Color(0xFF059669)
        "pending" -> Color(0xFFFEF3C7) to Color(0xFFD97706)
        "rejected" -> Color(0xFFFEE2E2) to Color(0xFFDC2626)
        else -> Color(0xFFF1F5F9) to Color(0xFF64748B)
    }
    
    Surface(
        color = color,
        shape = RoundedCornerShape(6.dp)
    ) {
        Text(
            text = status.uppercase(),
            color = text,
            fontSize = 10.sp,
            fontWeight = FontWeight.ExtraBold,
            modifier = Modifier.padding(horizontal = 7.dp, vertical = 3.dp)
        )
    }
}
