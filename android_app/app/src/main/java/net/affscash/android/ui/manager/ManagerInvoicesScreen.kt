package net.affscash.android.ui.manager

import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.WindowInsetsSides
import androidx.compose.foundation.layout.only
import androidx.compose.foundation.layout.safeDrawing
import androidx.compose.foundation.layout.windowInsetsPadding


import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.border
import androidx.compose.foundation.BorderStroke
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.Invoice
import net.affscash.android.ui.dashboard.PremiumUI
import net.affscash.android.data.model.ManagerInvoiceTabTotals
import net.affscash.android.ui.invoices.ManagerInvoicesUiState
import net.affscash.android.ui.invoices.ManagerInvoicesViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerInvoicesScreen(
    viewModel: ManagerInvoicesViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val selectedTab by viewModel.selectedTab.collectAsState()
    val searchQuery by viewModel.searchQuery.collectAsState()

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
                            .padding(horizontal = 16.dp, vertical = 12.dp)
                    ) {
                        Text(
                        text = "Invoices & Earnings",
                        style = PremiumUI.HeaderStyle,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                    }
                }
            }
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize().background(PremiumUI.PageBackground)) {
            
            when (val state = uiState) {
                is ManagerInvoicesUiState.Loading -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator()
                    }
                }
                is ManagerInvoicesUiState.Error -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text(text = state.message, color = MaterialTheme.colorScheme.error)
                            Spacer(modifier = Modifier.height(4.dp))
                            Button(onClick = { viewModel.loadInvoices() }) {
                                Text("Retry")
                            }
                        }
                    }
                }
                is ManagerInvoicesUiState.Success -> {
                    val response = state.data
                    val tabTitles = listOf(
                        "Affiliate Invoices", 
                        "My Invoices $${"%.2f".format(response.totals?.myBalance ?: 0.0)}"
                    )
                    
                    // Tabs
                    TabRow(selectedTabIndex = selectedTab) {
                        tabTitles.forEachIndexed { index, title ->
                            Tab(
                                selected = selectedTab == index,
                                onClick = { viewModel.setTab(index) },
                                text = { Text(title, fontSize = 12.sp) }
                            )
                        }
                    }
                    
                    val invoices = if (selectedTab == 0) response.affiliateInvoices else response.myInvoices
                    val tabTotals = if (selectedTab == 0) response.totals?.affiliate else response.totals?.my
                    
                    // Summary Cards
                    tabTotals?.let { totals ->
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(8.dp)
                                .horizontalScroll(rememberScrollState()),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            InvoiceSummaryCard("TOTAL INVOICES", totals.totalInvoices.toString(), Color.Black)
                            InvoiceSummaryCard("PENDING AMOUNT", "$${"%.2f".format(totals.pending)}", Color(0xFFF59E0B))
                            InvoiceSummaryCard("TOTAL PAID", "$${"%.2f".format(totals.paid)}", Color(0xFF10B981))
                        }
                    }
                    
                    // Search Bar
                    OutlinedTextField(
                        value = searchQuery,
                        onValueChange = { viewModel.setSearchQuery(it) },
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp, vertical = 8.dp)
                            .height(48.dp),
                        placeholder = { Text("Search invoice, affiliate...", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f)) },
                        leadingIcon = { net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.Search, contentDescription = "Search", tint = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.size(18.dp)) },
                        trailingIcon = {
                            if (searchQuery.isNotEmpty()) {
                                IconButton(onClick = { viewModel.setSearchQuery("") }) {
                                    net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.Clear, contentDescription = "Clear", modifier = Modifier.size(18.dp))
                                }
                            }
                        },
                        singleLine = true,
                        shape = RoundedCornerShape(24.dp),
                        textStyle = LocalTextStyle.current.copy(fontSize = 12.sp),
                        colors = TextFieldDefaults.colors(
                            focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                            unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                            focusedIndicatorColor = Color.Transparent,
                            unfocusedIndicatorColor = Color.Transparent
                        )
                    )
                    
                    Spacer(modifier = Modifier.height(4.dp))
                    
                    // Invoice List
                    val filteredInvoices = invoices.filter { inv ->
                        val q = searchQuery.lowercase()
                        q.isEmpty() ||
                        inv.invoiceNumber.lowercase().contains(q) ||
                        (inv.entityName ?: "").lowercase().contains(q) ||
                        (inv.status).lowercase().contains(q)
                    }

                    if (filteredInvoices.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Text("No invoices found.", color = Color.Gray)
                        }
                    } else {
                        LazyColumn(
                            modifier = Modifier.fillMaxSize(),
                            contentPadding = PaddingValues(bottom = 16.dp)
                        ) {
                            items(filteredInvoices) { inv ->
                                ManagerInvoiceDetailedItem(inv)
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun InvoiceSummaryCard(title: String, value: String, valueColor: Color) {
    Surface(
        modifier = Modifier
            .width(125.dp)
            .height(84.dp),
        shape = PremiumUI.CardShape,
        color = Color.White,
        shadowElevation = 4.dp,
        border = PremiumUI.Card3DBorder
    ) {
        Column(
            modifier = Modifier
                .background(PremiumUI.CardGradient)
                .padding(10.dp)
                .fillMaxSize(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Text(
                value,
                fontSize = 19.sp,
                fontWeight = FontWeight.Black,
                color = valueColor
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                title,
                fontSize = 9.sp,
                color = Color(0xFF64748B),
                fontWeight = FontWeight.ExtraBold,
                letterSpacing = 0.5.sp
            )
        }
    }
}

@Composable
fun ManagerInvoiceDetailedItem(invoice: Invoice) {
    net.affscash.android.ui.dashboard.GlassCard(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 6.dp)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            // Header: Invoice Number & Status Badge
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    invoice.invoiceNumber,
                    fontWeight = FontWeight.ExtraBold,
                    fontSize = 13.sp,
                    color = Color(0xFF4338CA)
                )
                
                net.affscash.android.ui.dashboard.StatusBadge(status = invoice.status)
            }
            
            Spacer(modifier = Modifier.height(6.dp))
            
            // Affiliate / Entity Name
            Text(
                invoice.entityName ?: "Unknown Entity",
                fontSize = 15.sp,
                fontWeight = FontWeight.Black,
                color = Color(0xFF0F172A)
            )
            
            Spacer(modifier = Modifier.height(6.dp))
            HorizontalDivider(color = Color(0xFFE2E8F0))
            Spacer(modifier = Modifier.height(6.dp))
            
            // Details Row
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Period", fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF94A3B8))
                    val pStart = invoice.periodStart ?: "N/A"
                    val pEnd = invoice.periodEnd ?: "N/A"
                    Text("$pStart - $pEnd", fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFF334155))
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("Amount", fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF94A3B8))
                    Text("$${"%.2f".format(invoice.total)}", fontSize = 16.sp, fontWeight = FontWeight.Black, color = Color(0xFF4F46E5))
                }
            }
            
            Spacer(modifier = Modifier.height(6.dp))
            
            // Dates Row
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Created: ${invoice.createdAt?.take(10) ?: "N/A"}", fontSize = 11.sp, fontWeight = FontWeight.Medium, color = Color(0xFF64748B))
                Text("Due: ${invoice.dueDate?.take(10) ?: "N/A"}", fontSize = 11.sp, fontWeight = FontWeight.Medium, color = Color(0xFF64748B))
            }
        }
    }
}
