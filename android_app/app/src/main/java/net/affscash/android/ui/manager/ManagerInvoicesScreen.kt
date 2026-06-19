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
import androidx.compose.foundation.shape.CircleShape
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
            TopAppBar(
                title = { Text("Invoices & Earnings") },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
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
                            .padding(horizontal = 16.dp, vertical = 8.dp),
                        placeholder = { Text("Search invoice, affiliate...") },
                        leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search", tint = MaterialTheme.colorScheme.primary) },
                        trailingIcon = {
                            if (searchQuery.isNotEmpty()) {
                                IconButton(onClick = { viewModel.setSearchQuery("") }) {
                                    Icon(Icons.Default.Clear, contentDescription = "Clear")
                                }
                            }
                        },
                        singleLine = true,
                        shape = CircleShape,
                        colors = OutlinedTextFieldDefaults.colors(
                            unfocusedBorderColor = Color.Transparent,
                            focusedBorderColor = MaterialTheme.colorScheme.primary,
                            unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                            focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
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
    Card(modifier = Modifier.width(140.dp)) {
        Column(
            modifier = Modifier.padding(12.dp).fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(value, fontSize = 18.sp, fontWeight = FontWeight.Bold, color = valueColor)
            Spacer(modifier = Modifier.height(4.dp))
            Text(title, fontSize = 10.sp, color = Color.Gray, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
fun ManagerInvoiceDetailedItem(invoice: Invoice) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 8.dp, vertical = 4.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            // Header: Invoice Number & Status
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text(invoice.invoiceNumber, fontWeight = FontWeight.Bold, fontSize = 14.sp, color = MaterialTheme.colorScheme.primary)
                
                val statusColor = when(invoice.status) {
                    "paid" -> Color(0xFF10B981)
                    "sent", "pending" -> Color(0xFFF59E0B)
                    "void", "rejected" -> Color(0xFFDC2626)
                    else -> Color.Gray
                }
                Text(invoice.status.uppercase(), fontSize = 11.sp, fontWeight = FontWeight.Bold, color = statusColor)
            }
            
            Spacer(modifier = Modifier.height(4.dp))
            
            // Affiliate Name
            Text(invoice.entityName ?: "Unknown", fontSize = 13.sp, fontWeight = FontWeight.Bold)
            
            Spacer(modifier = Modifier.height(6.dp))
            
            // Details Row
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Period", fontSize = 13.sp, color = Color.Gray)
                    val pStart = invoice.periodStart ?: "N/A"
                    val pEnd = invoice.periodEnd ?: "N/A"
                    Text("$pStart - $pEnd", fontSize = 11.sp)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("Amount", fontSize = 13.sp, color = Color.Gray)
                    Text("$${"%.2f".format(invoice.total)}", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                }
            }
            
            Spacer(modifier = Modifier.height(6.dp))
            
            // Dates Row
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Created: ${invoice.createdAt?.take(10) ?: "N/A"}", fontSize = 10.sp, color = Color.Gray)
                Text("Due: ${invoice.dueDate?.take(10) ?: "N/A"}", fontSize = 10.sp, color = Color.Gray)
            }
        }
    }
}
