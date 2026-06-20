package net.affscash.android.ui.manager

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
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.Invoice
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
                color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.6f),
                shape = androidx.compose.foundation.shape.RoundedCornerShape(bottomStart = 24.dp, bottomEnd = 24.dp)
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(start = 16.dp, end = 16.dp, top = 48.dp, bottom = 16.dp)
                ) {
                    Text(
                        text = "Invoices & Earnings",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                }
            }
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            
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
                            Spacer(modifier = Modifier.height(8.dp))
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
                                text = { Text(title, fontSize = 13.sp) }
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
                        placeholder = { Text("Search invoice, affiliate...", fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f)) },
                        leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search", tint = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.size(18.dp)) },
                        trailingIcon = {
                            if (searchQuery.isNotEmpty()) {
                                IconButton(onClick = { viewModel.setSearchQuery("") }) {
                                    Icon(Icons.Default.Clear, contentDescription = "Clear", modifier = Modifier.size(18.dp))
                                }
                            }
                        },
                        singleLine = true,
                        shape = RoundedCornerShape(24.dp),
                        textStyle = LocalTextStyle.current.copy(fontSize = 13.sp),
                        colors = TextFieldDefaults.colors(
                            focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                            unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                            focusedIndicatorColor = Color.Transparent,
                            unfocusedIndicatorColor = Color.Transparent
                        )
                    )
                    
                    Spacer(modifier = Modifier.height(8.dp))
                    
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
    Card(
        modifier = Modifier.width(120.dp).height(80.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.4f)),
        shape = RoundedCornerShape(12.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(
            modifier = Modifier.padding(12.dp).fillMaxSize(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Text(value, fontSize = 18.sp, fontWeight = FontWeight.ExtraBold, color = valueColor)
            Spacer(modifier = Modifier.height(4.dp))
            Text(title, fontSize = 9.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
fun ManagerInvoiceDetailedItem(invoice: Invoice) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 6.dp),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            // Header: Invoice Number & Status
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text(invoice.invoiceNumber, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.primary)
                
                val statusColor = when(invoice.status) {
                    "paid" -> Color(0xFF059669)
                    "sent", "pending" -> Color(0xFFF59E0B)
                    "void", "rejected" -> Color(0xFFEF4444)
                    else -> MaterialTheme.colorScheme.onSurfaceVariant
                }
                val statusBg = when(invoice.status) {
                    "paid" -> Color(0xFFD1FAE5)
                    "sent", "pending" -> Color(0xFFFEF3C7)
                    "void", "rejected" -> Color(0xFFFEE2E2)
                    else -> MaterialTheme.colorScheme.surfaceVariant
                }
                
                Surface(
                    color = statusBg,
                    shape = androidx.compose.foundation.shape.RoundedCornerShape(8.dp)
                ) {
                    Text(
                        invoice.status.uppercase(), 
                        style = MaterialTheme.typography.labelSmall, 
                        fontWeight = FontWeight.Bold, 
                        color = statusColor,
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                    )
                }
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            
            // Affiliate Name
            Text(invoice.entityName ?: "Unknown", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
            
            Spacer(modifier = Modifier.height(12.dp))
            HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
            Spacer(modifier = Modifier.height(12.dp))
            
            // Details Row
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Period", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    val pStart = invoice.periodStart ?: "N/A"
                    val pEnd = invoice.periodEnd ?: "N/A"
                    Text("$pStart - $pEnd", style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("Amount", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text("$${"%.2f".format(invoice.total)}", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
                }
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            
            // Dates Row
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Created: ${invoice.createdAt?.take(10) ?: "N/A"}", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f))
                Text("Due: ${invoice.dueDate?.take(10) ?: "N/A"}", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.7f))
            }
        }
    }
}
