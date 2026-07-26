package net.affscash.android.ui.screens.admin.invoices

import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.interaction.PressInteraction
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.DateRange
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminCreateInvoiceScreen(
    onNavigateBack: () -> Unit,
    viewModel: AdminCreateInvoiceViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            net.affscash.android.ui.components.CompactTopBar(
                title = { Text("Create Invoice") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primaryContainer,
                    titleContentColor = MaterialTheme.colorScheme.onPrimaryContainer
                )
            )
        }
    ) { paddingValues ->
        Box(modifier = Modifier.fillMaxSize().padding(paddingValues)) {
            LazyColumn(
                modifier = Modifier.fillMaxSize().padding(8.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                item {
                    if (uiState.error != null) {
                        Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer)) {
                            Text(text = uiState.error!!, color = MaterialTheme.colorScheme.onErrorContainer, modifier = Modifier.padding(8.dp))
                        }
                    }
                    if (uiState.successMessage != null) {
                        Card(colors = CardDefaults.cardColors(containerColor = Color(0xFFD4EDDA))) {
                            Text(text = uiState.successMessage!!, color = Color(0xFF155724), modifier = Modifier.padding(8.dp))
                        }
                    }
                }

                item {
                    Text("Step 1: Settings", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                    Card(modifier = Modifier.fillMaxWidth()) {
                        Column(modifier = Modifier.padding(8.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                            InvoiceTypeDropdown(
                                selectedType = uiState.invoiceType,
                                onTypeSelected = { viewModel.setInvoiceType(it) }
                            )

                            EntityDropdown(
                                invoiceType = uiState.invoiceType,
                                selectedEntityId = uiState.selectedEntityId,
                                formData = uiState.formData,
                                onEntitySelected = { viewModel.setSelectedEntity(it) }
                            )

                            if (uiState.entityBalance != null) {
                                Text("Balance: $${uiState.entityBalance}", color = MaterialTheme.colorScheme.primary)
                                if (uiState.entityThreshold != null) {
                                    Text("Threshold: $${uiState.entityThreshold}")
                                }
                            }

                            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                DatePickerField(
                                    label = "Period Start",
                                    selectedDate = uiState.periodStart,
                                    onDateSelected = { viewModel.setPeriodStart(it) },
                                    modifier = Modifier.weight(1f)
                                )
                                DatePickerField(
                                    label = "Period End",
                                    selectedDate = uiState.periodEnd,
                                    onDateSelected = { viewModel.setPeriodEnd(it) },
                                    modifier = Modifier.weight(1f)
                                )
                            }
                            
                            Button(onClick = { viewModel.loadOffers() }, modifier = Modifier.fillMaxWidth()) {
                                Text("Load Offers")
                            }
                        }
                    }
                }

                item {
                    Text("Step 2: Line Items", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                    Card(modifier = Modifier.fillMaxWidth()) {
                        Column(modifier = Modifier.padding(8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                            if (uiState.editableItems.isEmpty()) {
                                Text("No items added yet.")
                            }
                        }
                    }
                }

                itemsIndexed(uiState.editableItems) { index, item ->
                    Card(modifier = Modifier.fillMaxWidth()) {
                        Column(modifier = Modifier.padding(8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedTextField(
                                value = item.description,
                                onValueChange = { viewModel.updateLineItem(index, it, item.qty, item.rate) },
                                label = { Text("Description") },
                                modifier = Modifier.fillMaxWidth()
                            )
                            Row(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                                OutlinedTextField(
                                    value = item.qty,
                                    onValueChange = { viewModel.updateLineItem(index, item.description, it, item.rate) },
                                    label = { Text("Qty") },
                                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                                    modifier = Modifier.weight(1f)
                                )
                                OutlinedTextField(
                                    value = item.rate,
                                    onValueChange = { viewModel.updateLineItem(index, item.description, item.qty, it) },
                                    label = { Text("Rate") },
                                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                                    modifier = Modifier.weight(1f)
                                )
                                IconButton(onClick = { viewModel.removeLineItem(index) }) {
                                    net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.Delete, contentDescription = "Delete", tint = MaterialTheme.colorScheme.error)
                                }
                            }
                            
                            val q = item.qty.toDoubleOrNull() ?: 0.0
                            val r = item.rate.toDoubleOrNull() ?: 0.0
                            Text("Total: $${String.format("%.4f", q * r)}", fontWeight = FontWeight.SemiBold)
                        }
                    }
                }

                item {
                    Button(onClick = { viewModel.addLineItem() }) {
                        Text("Add Blank Item")
                    }
                }

                item {
                    Text("Step 3: Extras & Generate", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                    Card(modifier = Modifier.fillMaxWidth()) {
                        Column(modifier = Modifier.padding(8.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                            OutlinedTextField(
                                value = uiState.taxRate,
                                onValueChange = { viewModel.setTaxRate(it) },
                                label = { Text("Tax Rate (%)") },
                                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                                modifier = Modifier.fillMaxWidth()
                            )
                            
                            DatePickerField(
                                label = "Due Date",
                                selectedDate = uiState.dueDate,
                                onDateSelected = { viewModel.setDueDate(it) },
                                modifier = Modifier.fillMaxWidth()
                            )

                            OutlinedTextField(
                                value = uiState.customNotes,
                                onValueChange = { viewModel.setNotes(it) },
                                label = { Text("Notes") },
                                modifier = Modifier.fillMaxWidth(),
                                maxLines = 3
                            )
                            
                            OutlinedTextField(
                                value = uiState.paymentDetailsOverride,
                                onValueChange = { viewModel.setPaymentDetailsOverride(it) },
                                label = { Text("Payment Details Override") },
                                modifier = Modifier.fillMaxWidth(),
                                maxLines = 2
                            )
                            
                            OutlinedTextField(
                                value = uiState.totalOverride,
                                onValueChange = { viewModel.setTotalOverride(it) },
                                label = { Text("Total Override (Optional)") },
                                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                                modifier = Modifier.fillMaxWidth()
                            )

                            Spacer(modifier = Modifier.height(4.dp))

                            // Calculate totals
                            val subtotal = uiState.editableItems.sumOf { (it.qty.toDoubleOrNull() ?: 0.0) * (it.rate.toDoubleOrNull() ?: 0.0) }
                            val taxAmount = subtotal * (uiState.taxRate.toDoubleOrNull() ?: 0.0) / 100
                            val calcTotal = subtotal + taxAmount
                            val finalTotalStr = uiState.totalOverride.takeIf { it.isNotBlank() } ?: String.format("%.2f", calcTotal)

                            Text("Subtotal: $${String.format("%.2f", subtotal)}")
                            Text("Tax: $${String.format("%.2f", taxAmount)}")
                            Text("Invoice Total: $$finalTotalStr", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)

                            Button(
                                onClick = { viewModel.generateInvoice() },
                                modifier = Modifier.fillMaxWidth().height(50.dp),
                                enabled = !uiState.isSubmitting
                            ) {
                                Text(if (uiState.isSubmitting) "Generating..." else "Generate Invoice")
                            }
                        }
                    }
                }
            }
            if (uiState.isLoading) {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun InvoiceTypeDropdown(selectedType: String, onTypeSelected: (String) -> Unit) {
    var expanded by remember { mutableStateOf(false) }
    val types = mapOf(
        "affiliate_payout" to "Affiliate Payout",
        "advertiser_billing" to "Advertiser Billing",
        "manager_fee" to "Affiliate Manager Fee"
    )

    ExposedDropdownMenuBox(
        expanded = expanded,
        onExpandedChange = { expanded = !expanded }
    ) {
        OutlinedTextField(
            value = types[selectedType] ?: selectedType,
            onValueChange = {},
            readOnly = true,
            label = { Text("Invoice Type") },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            colors = ExposedDropdownMenuDefaults.outlinedTextFieldColors(),
            modifier = Modifier.menuAnchor().fillMaxWidth()
        )
        ExposedDropdownMenu(
            expanded = expanded,
            onDismissRequest = { expanded = false }
        ) {
            types.forEach { (key, label) ->
                DropdownMenuItem(
                    text = { Text(label) },
                    onClick = {
                        onTypeSelected(key)
                        expanded = false
                    }
                )
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EntityDropdown(
    invoiceType: String,
    selectedEntityId: Int?,
    formData: net.affscash.android.data.model.AdminInvoiceFormData?,
    onEntitySelected: (Int) -> Unit
) {
    var expanded by remember { mutableStateOf(false) }
    val options = when (invoiceType) {
        "affiliate_payout" -> formData?.affiliates ?: emptyList()
        "advertiser_billing" -> formData?.advertisers ?: emptyList()
        "manager_fee" -> formData?.managers ?: emptyList()
        else -> emptyList()
    }
    
    val selectedLabel = options.find { it.id == selectedEntityId }?.label ?: "Select Entity"

    ExposedDropdownMenuBox(
        expanded = expanded,
        onExpandedChange = { expanded = !expanded }
    ) {
        OutlinedTextField(
            value = selectedLabel,
            onValueChange = {},
            readOnly = true,
            label = { Text("Select Entity") },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            colors = ExposedDropdownMenuDefaults.outlinedTextFieldColors(),
            modifier = Modifier.menuAnchor().fillMaxWidth()
        )
        ExposedDropdownMenu(
            expanded = expanded,
            onDismissRequest = { expanded = false }
        ) {
            options.forEach { option ->
                DropdownMenuItem(
                    text = { Text(option.label) },
                    onClick = {
                        onEntitySelected(option.id)
                        expanded = false
                    }
                )
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DatePickerField(
    label: String,
    selectedDate: String,
    onDateSelected: (String) -> Unit,
    modifier: Modifier = Modifier
) {
    var showDialog by remember { mutableStateOf(false) }
    val datePickerState = rememberDatePickerState(
        initialSelectedDateMillis = System.currentTimeMillis()
    )

    OutlinedTextField(
        value = selectedDate,
        onValueChange = {},
        label = { Text(label) },
        readOnly = true,
        modifier = modifier,
        trailingIcon = {
            IconButton(onClick = { showDialog = true }) {
                net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.DateRange, contentDescription = "Select Date")
            }
        },
        interactionSource = remember { MutableInteractionSource() }.also { interactionSource ->
            LaunchedEffect(interactionSource) {
                interactionSource.interactions.collect {
                    if (it is PressInteraction.Release) {
                        showDialog = true
                    }
                }
            }
        }
    )

    if (showDialog) {
        DatePickerDialog(
            onDismissRequest = { showDialog = false },
            confirmButton = {
                TextButton(onClick = {
                    datePickerState.selectedDateMillis?.let { millis ->
                        val sdf = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault())
                        onDateSelected(sdf.format(Date(millis)))
                    }
                    showDialog = false
                }) {
                    Text("OK")
                }
            },
            dismissButton = {
                TextButton(onClick = { showDialog = false }) {
                    Text("Cancel")
                }
            }
        ) {
            DatePicker(state = datePickerState)
        }
    }
}
