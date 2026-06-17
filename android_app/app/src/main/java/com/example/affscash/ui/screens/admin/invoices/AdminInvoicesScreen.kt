package com.example.affscash.ui.screens.admin.invoices

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminInvoicesScreen(
    viewModel: AdminInvoicesViewModel = viewModel(),
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

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (uiState.invoiceDetail != null) "Invoice Details" else "Invoices") },
                navigationIcon = {
                    IconButton(onClick = {
                        if (uiState.invoiceDetail != null) {
                            viewModel.closeDetails()
                        } else {
                            onNavigateBack()
                        }
                    }) {
                        Icon(Icons.Default.ArrowBack, "Back")
                    }
                }
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { padding ->
        Box(modifier = Modifier.fillMaxSize().padding(padding)) {
            if (uiState.isLoading || uiState.isDetailLoading) {
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            }

            if (uiState.invoiceDetail != null) {
                AdminInvoiceDetailView(
                    detail = uiState.invoiceDetail!!,
                    onStatusChange = { viewModel.updateInvoiceStatus(uiState.invoiceDetail!!.id, it) },
                    onDelete = { viewModel.deleteInvoice(uiState.invoiceDetail!!.id) }
                )
            } else {
                LazyColumn(contentPadding = PaddingValues(8.dp)) {
                    items(uiState.invoices) { invoice ->
                        AdminInvoiceCard(
                            invoice = invoice,
                            onView = { viewModel.viewInvoiceDetails(invoice.id) },
                            onStatusChange = { viewModel.updateInvoiceStatus(invoice.id, it) },
                            onDelete = { viewModel.deleteInvoice(invoice.id) }
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun AdminInvoiceCard(
    invoice: com.example.affscash.data.model.AdminInvoiceRow,
    onView: () -> Unit,
    onStatusChange: (String) -> Unit,
    onDelete: () -> Unit
) {
    var menuExpanded by remember { mutableStateOf(false) }

    val statusColor = when(invoice.status) {
        "paid" -> Color(0xFF388E3C)
        "sent" -> Color(0xFF1976D2)
        "void" -> Color.Gray
        else -> Color(0xFFF57C00)
    }

    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text(invoice.invoice_number, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                Box {
                    IconButton(onClick = { menuExpanded = true }) {
                        Icon(Icons.Default.MoreVert, "More Options")
                    }
                    DropdownMenu(expanded = menuExpanded, onDismissRequest = { menuExpanded = false }) {
                        DropdownMenuItem(text = { Text("View Details") }, onClick = { menuExpanded = false; onView() })
                        if (invoice.status != "paid") {
                            DropdownMenuItem(text = { Text("Mark Paid") }, onClick = { menuExpanded = false; onStatusChange("paid") })
                        }
                        if (invoice.status != "void") {
                            DropdownMenuItem(text = { Text("Mark Void") }, onClick = { menuExpanded = false; onStatusChange("void") })
                        }
                        DropdownMenuItem(text = { Text("Delete", color = Color.Red) }, onClick = { menuExpanded = false; onDelete() })
                    }
                }
            }
            Text("Recipient: ${invoice.recipient_name ?: "Unknown"}", style = MaterialTheme.typography.bodyMedium)
            Text("Type: ${invoice.type.uppercase()}", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
            
            Divider(modifier = Modifier.padding(vertical = 8.dp))
            
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Total", style = MaterialTheme.typography.labelSmall)
                    Text("$${invoice.total}", fontWeight = FontWeight.Bold)
                }
                Column {
                    Text("Period", style = MaterialTheme.typography.labelSmall)
                    Text("${invoice.period_start ?: "N/A"} to ${invoice.period_end ?: "N/A"}", style = MaterialTheme.typography.bodySmall)
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("Status", style = MaterialTheme.typography.labelSmall)
                    Badge(containerColor = statusColor) {
                        Text(invoice.status.uppercase(), color = Color.White, modifier = Modifier.padding(horizontal = 4.dp))
                    }
                }
            }
        }
    }
}

@Composable
fun AdminInvoiceDetailView(
    detail: com.example.affscash.data.model.AdminInvoiceDetail,
    onStatusChange: (String) -> Unit,
    onDelete: () -> Unit
) {
    val statusColor = when(detail.status) {
        "paid" -> Color(0xFF388E3C)
        "sent" -> Color(0xFF1976D2)
        "void" -> Color.Gray
        else -> Color(0xFFF57C00)
    }

    LazyColumn(contentPadding = PaddingValues(16.dp)) {
        item {
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text(detail.invoice_number, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
                Badge(containerColor = statusColor) {
                    Text(detail.status.uppercase(), color = Color.White, modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp))
                }
            }
            Spacer(Modifier.height(8.dp))
            Text("Recipient: ${detail.recipient_name} (${detail.recipient_email})")
            Text("Type: ${detail.type.uppercase()}")
            Text("Date: ${detail.created_at}")
            Text("Period: ${detail.period_start} to ${detail.period_end}")
            Text("Due: ${detail.due_date ?: "N/A"}")
            if (detail.paid_at != null) {
                Text("Paid At: ${detail.paid_at}", color = Color(0xFF388E3C))
            }
            if (!detail.notes.isNullOrEmpty()) {
                Text("Notes: ${detail.notes}", modifier = Modifier.padding(top = 8.dp), color = Color.Gray)
            }
            
            Spacer(Modifier.height(16.dp))
            Text("Line Items", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            Divider(modifier = Modifier.padding(vertical = 8.dp))
        }

        items(detail.items) { item ->
            Row(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(item.description, fontWeight = FontWeight.Medium)
                    Text("Qty: ${item.qty} @ $${item.rate}", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                }
                Text("$${item.amount}", fontWeight = FontWeight.Bold)
            }
            Divider(color = Color.LightGray.copy(alpha = 0.5f))
        }

        item {
            Spacer(Modifier.height(16.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                Text("Subtotal: ", color = Color.Gray)
                Text("$${detail.subtotal ?: "0.00"}", fontWeight = FontWeight.Bold)
            }
            if ((detail.tax_amount?.toFloatOrNull() ?: 0f) > 0f) {
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                    Text("Tax (${detail.tax_rate}%): ", color = Color.Gray)
                    Text("$${detail.tax_amount}", fontWeight = FontWeight.Bold)
                }
            }
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                Text("Total: ", style = MaterialTheme.typography.titleMedium)
                Text("$${detail.total}", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = Color(0xFF1976D2))
            }

            Spacer(Modifier.height(32.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceEvenly) {
                if (detail.status != "paid") {
                    Button(onClick = { onStatusChange("paid") }, colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF388E3C))) {
                        Text("Mark Paid")
                    }
                }
                Button(onClick = onDelete, colors = ButtonDefaults.buttonColors(containerColor = Color.Red)) {
                    Text("Delete")
                }
            }
        }
    }
}
