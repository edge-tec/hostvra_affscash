package net.affscash.android.ui.screens.admin.invoices

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.AdminInvoiceRequestRow
import net.affscash.android.data.model.AdminInvoiceRow

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminInvoicesScreen(
    viewModel: AdminInvoicesViewModel = hiltViewModel(),
    onNavigateBack: () -> Unit,
    onCreateInvoice: () -> Unit = {}
) {
    val uiState by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    var requestToApprove by remember { mutableStateOf<AdminInvoiceRequestRow?>(null) }
    var requestToReject by remember { mutableStateOf<AdminInvoiceRequestRow?>(null) }

    LaunchedEffect(uiState.error, uiState.successMessage) {
        uiState.error?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearMessages()
        }
        uiState.successMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearMessages()
        }
    }

    Scaffold(
        topBar = {
            Column {
                net.affscash.android.ui.components.CompactTopBar(
                    title = { Text(if (uiState.invoiceDetail != null) "Invoice Details" else "Invoices & Requests") },
                    navigationIcon = {
                        IconButton(onClick = {
                            if (uiState.invoiceDetail != null) {
                                viewModel.closeDetails()
                            } else {
                                onNavigateBack()
                            }
                        }) {
                            net.affscash.android.ui.dashboard.GradientIcon(Icons.AutoMirrored.Filled.ArrowBack, "Back")
                        }
                    }
                )

                if (uiState.invoiceDetail == null) {
                    val pendingCount = uiState.requests.count { it.status == "pending" }
                    PrimaryTabRow(selectedTabIndex = uiState.selectedTab) {
                        Tab(
                            selected = uiState.selectedTab == 0,
                            onClick = { viewModel.setTab(0) },
                            text = { Text("Invoices (${uiState.invoices.size})") }
                        )
                        Tab(
                            selected = uiState.selectedTab == 1,
                            onClick = { viewModel.setTab(1) },
                            text = {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Text("Invoice Requests")
                                    if (pendingCount > 0) {
                                        Spacer(modifier = Modifier.width(6.dp))
                                        Badge(containerColor = MaterialTheme.colorScheme.error) {
                                            Text("$pendingCount", color = Color.White)
                                        }
                                    }
                                }
                            }
                        )
                    }
                }
            }
        },
        floatingActionButton = {
            if (uiState.invoiceDetail == null && uiState.selectedTab == 0) {
                FloatingActionButton(onClick = onCreateInvoice) {
                    net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.Add, contentDescription = "Create Invoice")
                }
            }
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
            } else if (uiState.selectedTab == 0) {
                if (uiState.invoices.isEmpty() && !uiState.isLoading) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Text("No generated invoices found", color = Color.Gray)
                    }
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
            } else {
                if (uiState.requests.isEmpty() && !uiState.isLoading) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Text("No invoice requests found", color = Color.Gray)
                    }
                } else {
                    LazyColumn(contentPadding = PaddingValues(8.dp)) {
                        items(uiState.requests) { request ->
                            AdminInvoiceRequestCard(
                                request = request,
                                onApprove = { requestToApprove = request },
                                onReject = { requestToReject = request }
                            )
                        }
                    }
                }
            }
        }
    }

    requestToApprove?.let { req ->
        var amountText by remember { mutableStateOf(req.amount) }
        var adminNote by remember { mutableStateOf("") }

        AlertDialog(
            onDismissRequest = { requestToApprove = null },
            title = { Text("Approve Invoice Request") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text("Manager: ${req.managerName ?: "N/A"}")
                    if (!req.affiliateName.isNullOrEmpty()) {
                        Text("Affiliate: ${req.affiliateName} (${req.affiliateCode ?: ""})")
                    }
                    Text("Period: ${req.periodStart} to ${req.periodEnd}")

                    OutlinedTextField(
                        value = amountText,
                        onValueChange = { amountText = it },
                        label = { Text("Approved Amount ($)") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )

                    OutlinedTextField(
                        value = adminNote,
                        onValueChange = { adminNote = it },
                        label = { Text("Admin Note (Optional)") },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        val amount = amountText.toDoubleOrNull()
                        viewModel.approveRequest(req.id, amount, adminNote.ifBlank { null })
                        requestToApprove = null
                    }
                ) {
                    Text("Approve & Create Invoice")
                }
            },
            dismissButton = {
                TextButton(onClick = { requestToApprove = null }) {
                    Text("Cancel")
                }
            }
        )
    }

    requestToReject?.let { req ->
        var adminNote by remember { mutableStateOf("") }

        AlertDialog(
            onDismissRequest = { requestToReject = null },
            title = { Text("Reject Invoice Request") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text("Manager: ${req.managerName ?: "N/A"}")
                    Text("Amount: $${req.amount}")

                    OutlinedTextField(
                        value = adminNote,
                        onValueChange = { adminNote = it },
                        label = { Text("Reason for Rejection (Optional)") },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.rejectRequest(req.id, adminNote.ifBlank { null })
                        requestToReject = null
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    Text("Reject Request")
                }
            },
            dismissButton = {
                TextButton(onClick = { requestToReject = null }) {
                    Text("Cancel")
                }
            }
        )
    }
}

@Composable
fun AdminInvoiceRequestCard(
    request: AdminInvoiceRequestRow,
    onApprove: () -> Unit,
    onReject: () -> Unit
) {
    val statusColor = when (request.status) {
        "approved" -> Color(0xFF388E3C)
        "rejected" -> Color(0xFFD32F2F)
        else -> Color(0xFFF57C00)
    }

    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = "Request #${request.id}",
                        fontWeight = FontWeight.Bold,
                        style = MaterialTheme.typography.titleMedium
                    )
                    Text(
                        text = "Manager: ${request.managerName ?: "Manager #${request.managerId}"}",
                        style = MaterialTheme.typography.bodyMedium,
                        fontWeight = FontWeight.Medium
                    )
                    if (!request.managerEmail.isNullOrEmpty()) {
                        Text(
                            text = request.managerEmail,
                            style = MaterialTheme.typography.bodySmall,
                            color = Color.Gray
                        )
                    }
                }

                Badge(containerColor = statusColor) {
                    Text(
                        text = request.status.uppercase(),
                        color = Color.White,
                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                    )
                }
            }

            HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp))

            if (!request.affiliateName.isNullOrEmpty()) {
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Text("For Affiliate:", fontSize = 12.sp, color = Color.Gray)
                    Text("${request.affiliateName} (${request.affiliateCode ?: ""})", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                }
            }

            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Requested Amount:", fontSize = 12.sp, color = Color.Gray)
                Text("$${request.amount}", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
            }

            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Earnings Period:", fontSize = 12.sp, color = Color.Gray)
                Text("${request.periodStart} to ${request.periodEnd}", fontSize = 12.sp)
            }

            if (!request.notes.isNullOrEmpty()) {
                Spacer(modifier = Modifier.height(4.dp))
                Text("Notes: ${request.notes}", fontSize = 11.sp, color = Color.Gray)
            }

            if (!request.adminNote.isNullOrEmpty()) {
                Spacer(modifier = Modifier.height(4.dp))
                Text("Admin Note: ${request.adminNote}", fontSize = 11.sp, color = MaterialTheme.colorScheme.tertiary)
            }

            if (request.status == "pending") {
                Spacer(modifier = Modifier.height(10.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    OutlinedButton(
                        onClick = onReject,
                        colors = ButtonDefaults.outlinedButtonColors(contentColor = MaterialTheme.colorScheme.error)
                    ) {
                        net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.Close, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Reject")
                    }

                    Spacer(modifier = Modifier.width(8.dp))

                    Button(
                        onClick = onApprove,
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF388E3C))
                    ) {
                        net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Approve")
                    }
                }
            }
        }
    }
}

@Composable
fun AdminInvoiceCard(
    invoice: AdminInvoiceRow,
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
                Text(invoice.invoice_number, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleSmall)
                Box {
                    IconButton(onClick = { menuExpanded = true }) {
                        net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.MoreVert, "More Options")
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
            
            HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp))
            
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Total", style = MaterialTheme.typography.labelSmall)
                    Text("$${(( invoice.total )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontWeight = FontWeight.Bold)
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
    detail: net.affscash.android.data.model.AdminInvoiceDetail,
    onStatusChange: (String) -> Unit,
    onDelete: () -> Unit
) {
    val statusColor = when(detail.status) {
        "paid" -> Color(0xFF388E3C)
        "sent" -> Color(0xFF1976D2)
        "void" -> Color.Gray
        else -> Color(0xFFF57C00)
    }

    LazyColumn(contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp)) {
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
            Text("Line Items", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
            HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp))
        }

        items(detail.items) { item ->
            Row(modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(item.description, fontWeight = FontWeight.Medium)
                    Text("Qty: ${item.qty} @ $${(( item.rate )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                }
                Text("$${(( item.amount )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontWeight = FontWeight.Bold)
            }
            HorizontalDivider(color = Color.LightGray.copy(alpha = 0.5f))
        }

        item {
            Spacer(Modifier.height(16.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                Text("Subtotal: ", color = Color.Gray)
                Text("$${(( detail.subtotal ?: "0.00" )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontWeight = FontWeight.Bold)
            }
            if ((detail.tax_amount?.toFloatOrNull() ?: 0f) > 0f) {
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                    Text("Tax (${detail.tax_rate}%): ", color = Color.Gray)
                    Text("$${(( detail.tax_amount )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontWeight = FontWeight.Bold)
                }
            }
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                Text("Total: ", style = MaterialTheme.typography.titleSmall)
                Text("$${(( detail.total )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold, color = Color(0xFF1976D2))
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
