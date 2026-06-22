package net.affscash.android.ui.screens.admin.payment_settings

import android.widget.Toast
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import net.affscash.android.data.model.AffiliatePaymentInfo
import net.affscash.android.ui.dashboard.PremiumUI
import net.affscash.android.data.model.ManagerPaymentInfo
import net.affscash.android.data.model.OfferBasicItem
import net.affscash.android.data.model.OfferCommissionInfo
import net.affscash.android.data.model.PaymentMethodItem

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminPaymentSettingsScreen(
    viewModel: AdminPaymentSettingsViewModel,
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current

    LaunchedEffect(uiState.successMessage) {
        uiState.successMessage?.let {
            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
            viewModel.clearMessage()
        }
    }

    LaunchedEffect(uiState.error) {
        uiState.error?.let {
            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
            viewModel.clearMessage()
        }
    }

    Scaffold(
        topBar = {
            net.affscash.android.ui.components.CompactTopBar(
                title = { Text("Payment Settings") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            val tabs = listOf("Methods", "Terms", "Commission", "Payout Info")
            ScrollableTabRow(
                selectedTabIndex = uiState.selectedTab,
                edgePadding = 8.dp
            ) {
                tabs.forEachIndexed { index, title ->
                    Tab(
                        selected = uiState.selectedTab == index,
                        onClick = { viewModel.setTab(index) },
                        text = { Text(title) }
                    )
                }
            }

            if (uiState.isLoading && uiState.data == null) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            } else {
                uiState.data?.let { data ->
                    when (uiState.selectedTab) {
                        0 -> PaymentMethodsTab(
                            methods = data.paymentMethods,
                            onSave = { id, name, type, desc, inst -> 
                                viewModel.savePaymentMethod(if(id==null) "add" else "edit", id, name, type, desc, inst) 
                            },
                            onToggle = { viewModel.togglePaymentMethod(it) },
                            onDelete = { viewModel.deletePaymentMethod(it) }
                        )
                        1 -> PaymentTermsTab(
                            affiliates = data.affiliates,
                            onSaveTerms = { scope, terms, ids -> viewModel.savePaymentTerms(scope, terms, ids) }
                        )
                        2 -> ManagerCommissionTab(
                            managers = data.managers,
                            offers = data.offers,
                            offerCommissions = data.offerCommissions,
                            onSaveManagerCommission = { id, rate -> viewModel.saveManagerCommission(id, rate) },
                            onSaveOfferCommission = { mId, oId, rate -> viewModel.saveOfferCommission(mId, oId, rate) },
                            onDeleteOfferCommission = { viewModel.deleteOfferCommission(it) }
                        )
                        3 -> PayoutInfoTab(
                            affiliates = data.affiliates,
                            managers = data.managers,
                            paymentMethods = data.paymentMethods,
                            onSavePayout = { type, entityId, method, details -> viewModel.savePayoutInfo(type, entityId, method, details) }
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun PaymentMethodsTab(
    methods: List<PaymentMethodItem>,
    onSave: (id: Int?, name: String, type: String, desc: String, inst: String) -> Unit,
    onToggle: (Int) -> Unit,
    onDelete: (Int) -> Unit
) {
    var showForm by remember { mutableStateOf(false) }
    var editId by remember { mutableStateOf<Int?>(null) }
    var name by remember { mutableStateOf("") }
    var type by remember { mutableStateOf("custom") }
    var desc by remember { mutableStateOf("") }
    var inst by remember { mutableStateOf("") }

    LazyColumn(modifier = Modifier.fillMaxSize().padding(8.dp)) {
        item {
            if (!showForm) {
                Button(
                    shape = MaterialTheme.shapes.medium,
                    onClick = { 
                        editId = null
                        name = ""
                        type = "custom"
                        desc = ""
                        inst = ""
                        showForm = true 
                    },
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Icon(Icons.Default.Add, contentDescription = null)
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Add Payment Method")
                }
                Spacer(modifier = Modifier.height(4.dp))
            } else {
                Card(modifier = Modifier.fillMaxWidth()) {
                    Column(modifier = Modifier.padding(8.dp)) {
                        Text(if (editId == null) "Add Method" else "Edit Method", style = MaterialTheme.typography.titleSmall)
                        Spacer(modifier = Modifier.height(4.dp))
                AdminStyledTextField(
                            value = name,
                            onValueChange = { name = it },
                            label = { Text("Method Name") },
                            modifier = Modifier.fillMaxWidth()
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                AdminStyledTextField(
                            value = desc,
                            onValueChange = { desc = it },
                            label = { Text("Description") },
                            modifier = Modifier.fillMaxWidth()
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                AdminStyledTextField(
                            value = inst,
                            onValueChange = { inst = it },
                            label = { Text("Instructions") },
                            modifier = Modifier.fillMaxWidth(),
                            minLines = 3
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        Row {
                            Button(shape = MaterialTheme.shapes.medium, onClick = {
                                onSave(editId, name, type, desc, inst)
                                showForm = false
                            }) { Text("Save") }
                            Spacer(modifier = Modifier.width(4.dp))
                            TextButton(shape = MaterialTheme.shapes.medium, onClick = { showForm = false }) { Text("Cancel") }
                        }
                    }
                }
                Spacer(modifier = Modifier.height(4.dp))
            }
        }

        item {
            Text("Active Payment Methods", style = MaterialTheme.typography.titleSmall)
            Spacer(modifier = Modifier.height(4.dp))
        }

        items(methods) { pm ->
            Card(
                modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
            ) {
                Column(modifier = Modifier.padding(8.dp)) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(pm.name, style = MaterialTheme.typography.titleSmall, modifier = Modifier.weight(1f))
                        Badge(containerColor = if (pm.isActive == 1) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.error) {
                            Text(if (pm.isActive == 1) "ACTIVE" else "DISABLED")
                        }
                    }
                    Text("Type: ${pm.methodType}", style = MaterialTheme.typography.bodySmall)
                    pm.description?.let { Text(it, style = MaterialTheme.typography.bodyMedium) }
                    
                    Spacer(modifier = Modifier.height(4.dp))
                    Row(horizontalArrangement = Arrangement.End, modifier = Modifier.fillMaxWidth()) {
                        IconButton(onClick = { 
                            editId = pm.id
                            name = pm.name
                            type = pm.methodType
                            desc = pm.description ?: ""
                            inst = pm.instructions ?: ""
                            showForm = true
                        }) {
                            Icon(Icons.Default.Edit, contentDescription = "Edit")
                        }
                        Button(shape = MaterialTheme.shapes.medium, onClick = { onToggle(pm.id) }, modifier = Modifier.padding(horizontal = 4.dp)) {
                            Text(if (pm.isActive == 1) "Disable" else "Enable")
                        }
                        if (pm.isDefault == 0) {
                            IconButton(onClick = { onDelete(pm.id) }) {
                                Icon(Icons.Default.Delete, contentDescription = "Delete", tint = MaterialTheme.colorScheme.error)
                            }
                        }
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun PaymentTermsTab(
    affiliates: List<AffiliatePaymentInfo>,
    onSaveTerms: (String, String, List<Int>) -> Unit
) {
    var terms by remember { mutableStateOf("monthly") }
    var scope by remember { mutableStateOf("all") }
    var selectedIds by remember { mutableStateOf(setOf<Int>()) }
    var expanded by remember { mutableStateOf(false) }

    val termOptions = listOf("weekly" to "Weekly", "net15" to "Net-15", "net30" to "Net-30", "monthly" to "Monthly")

    LazyColumn(modifier = Modifier.fillMaxSize().padding(8.dp)) {
        item {
            Card(modifier = Modifier.fillMaxWidth()) {
                Column(modifier = Modifier.padding(8.dp)) {
                    Text("Update Payment Terms", style = MaterialTheme.typography.titleSmall)
                    Spacer(modifier = Modifier.height(4.dp))
                    
                    ExposedDropdownMenuBox(
                        expanded = expanded,
                        onExpandedChange = { expanded = !expanded }
                    ) {
                AdminStyledTextField(
                            value = termOptions.find { it.first == terms }?.second ?: terms,
                            onValueChange = {},
                            readOnly = true,
                            label = { Text("Payment Terms") },
                            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                            modifier = Modifier.menuAnchor().fillMaxWidth()
                        )
                        ExposedDropdownMenu(
                            expanded = expanded,
                            onDismissRequest = { expanded = false }
                        ) {
                            termOptions.forEach { option ->
                                DropdownMenuItem(
                                    text = { Text(option.second) },
                                    onClick = { 
                                        terms = option.first
                                        expanded = false 
                                    }
                                )
                            }
                        }
                    }
                    Spacer(modifier = Modifier.height(4.dp))
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        RadioButton(selected = scope == "all", onClick = { scope = "all" })
                        Text("All Affiliates")
                        Spacer(modifier = Modifier.width(4.dp))
                        RadioButton(selected = scope == "selected", onClick = { scope = "selected" })
                        Text("Selected Only")
                    }
                    Spacer(modifier = Modifier.height(4.dp))
                    Button(shape = MaterialTheme.shapes.medium, onClick = { onSaveTerms(scope, terms, selectedIds.toList()) }) {
                        Text("Save Terms")
                    }
                }
            }
            Spacer(modifier = Modifier.height(4.dp))
            Text("Affiliates List", style = MaterialTheme.typography.titleSmall)
            Spacer(modifier = Modifier.height(4.dp))
        }

        items(affiliates) { aff ->
            Card(modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp)) {
                Row(modifier = Modifier.padding(8.dp), verticalAlignment = Alignment.CenterVertically) {
                    if (scope == "selected") {
                        Checkbox(
                            checked = selectedIds.contains(aff.id),
                            onCheckedChange = { checked ->
                                selectedIds = if (checked) selectedIds + aff.id else selectedIds - aff.id
                            }
                        )
                    }
                    Column(modifier = Modifier.weight(1f)) {
                        Text("${aff.name} (#${aff.id})", style = MaterialTheme.typography.titleSmall)
                        Text(aff.email, style = MaterialTheme.typography.bodySmall)
                    }
                    Badge {
                        Text(aff.paymentTerms.uppercase())
                    }
                }
            }
        }
    }
}

@Composable
private fun ManagerCommissionTab(
    managers: List<ManagerPaymentInfo>,
    offers: List<OfferBasicItem>,
    offerCommissions: List<OfferCommissionInfo>,
    onSaveManagerCommission: (Int, Double) -> Unit,
    onSaveOfferCommission: (Int, Int, Double) -> Unit,
    onDeleteOfferCommission: (Int) -> Unit
) {
    var expandedMgr by remember { mutableStateOf(false) }
    var expandedOffer by remember { mutableStateOf(false) }
    var selectedMgrId by remember { mutableStateOf<Int?>(null) }
    var selectedOfferId by remember { mutableStateOf<Int?>(null) }
    var rateText by remember { mutableStateOf("") }

    LazyColumn(modifier = Modifier.fillMaxSize().padding(8.dp)) {
        item {
            Text("Base Commission Rates", style = MaterialTheme.typography.titleSmall)
            Spacer(modifier = Modifier.height(4.dp))
        }
        items(managers) { mgr ->
            Card(modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp)) {
                Column(modifier = Modifier.padding(8.dp)) {
                    Text(mgr.name, style = MaterialTheme.typography.titleSmall)
                    var locRate by remember { mutableStateOf(mgr.commissionRate.toString()) }
                    Row(verticalAlignment = Alignment.CenterVertically) {
                AdminStyledTextField(
                            value = locRate,
                            onValueChange = { locRate = it },
                            label = { Text("Rate (%)") },
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                            modifier = Modifier.weight(1f)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Button(shape = MaterialTheme.shapes.medium, onClick = { onSaveManagerCommission(mgr.userId, locRate.toDoubleOrNull() ?: 0.0) }) {
                            Text("Save")
                        }
                    }
                }
            }
        }
        item {
            Spacer(modifier = Modifier.height(4.dp))
            Text("Offer Specific Commission Overrides", style = MaterialTheme.typography.titleSmall)
            Spacer(modifier = Modifier.height(4.dp))
            Card(modifier = Modifier.fillMaxWidth()) {
                Column(modifier = Modifier.padding(8.dp)) {
                    Text("Add Override")
                    Spacer(modifier = Modifier.height(4.dp))
                    // Simplified: We would normally use ExposedDropdownMenu here, but for brevity we'll just allow setting.
                    // In a real app we'd use fully searchable dropdowns.
                    if (managers.isNotEmpty() && offers.isNotEmpty()) {
                        selectedMgrId = selectedMgrId ?: managers.first().mgrId
                        selectedOfferId = selectedOfferId ?: offers.first().id
                        
                        Text("Manager ID: $selectedMgrId", style = MaterialTheme.typography.bodySmall)
                        Text("Offer ID: $selectedOfferId", style = MaterialTheme.typography.bodySmall)
                        // Note: For full UX, implement Dropdown for Managers and Offers here.
                AdminStyledTextField(
                            value = rateText,
                            onValueChange = { rateText = it },
                            label = { Text("Commission %") },
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                            modifier = Modifier.fillMaxWidth()
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        Button(shape = MaterialTheme.shapes.medium, onClick = { 
                            onSaveOfferCommission(selectedMgrId!!, selectedOfferId!!, rateText.toDoubleOrNull() ?: 0.0) 
                        }) {
                            Text("Add Override")
                        }
                    }
                }
            }
            Spacer(modifier = Modifier.height(4.dp))
        }
        items(offerCommissions) { oc ->
            Card(modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp)) {
                Row(modifier = Modifier.padding(8.dp), verticalAlignment = Alignment.CenterVertically) {
                    Column(modifier = Modifier.weight(1f)) {
                        Text(oc.managerName, style = MaterialTheme.typography.titleSmall)
                        Text(oc.offerName, style = MaterialTheme.typography.bodyMedium)
                        Text("Commission: ${oc.commissionRate}%", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.primary)
                    }
                    IconButton(onClick = { onDeleteOfferCommission(oc.id) }) {
                        Icon(Icons.Default.Delete, contentDescription = "Delete", tint = MaterialTheme.colorScheme.error)
                    }
                }
            }
        }
    }
}

@Composable
private fun PayoutInfoTab(
    affiliates: List<AffiliatePaymentInfo>,
    managers: List<ManagerPaymentInfo>,
    paymentMethods: List<PaymentMethodItem>,
    onSavePayout: (String, Int, String, String) -> Unit
) {
    var type by remember { mutableStateOf("affiliate") }
    
    LazyColumn(modifier = Modifier.fillMaxSize().padding(8.dp)) {
        item {
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.Center) {
                FilterChip(
modifier = Modifier.height(32.dp),selected = type == "affiliate", onClick = { type = "affiliate" }, label = { Text("Affiliates") })
                Spacer(modifier = Modifier.width(4.dp))
                FilterChip(
modifier = Modifier.height(32.dp),selected = type == "manager", onClick = { type = "manager" }, label = { Text("Managers") })
            }
            Spacer(modifier = Modifier.height(4.dp))
        }

        if (type == "affiliate") {
            items(affiliates) { aff ->
                PayoutEntityCard(
                    name = aff.name,
                    email = aff.email,
                    pm = aff.paymentMethod,
                    pd = aff.paymentDetails,
                    paymentMethods = paymentMethods,
                    onSave = { pm, pd -> onSavePayout("affiliate", aff.id, pm, pd) }
                )
            }
        } else {
            items(managers) { mgr ->
                PayoutEntityCard(
                    name = mgr.name,
                    email = mgr.email,
                    pm = mgr.paymentMethod,
                    pd = mgr.paymentDetails,
                    paymentMethods = paymentMethods,
                    onSave = { pm, pd -> onSavePayout("manager", mgr.userId, pm, pd) }
                )
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun PayoutEntityCard(
    name: String,
    email: String,
    pm: String?,
    pd: String?,
    paymentMethods: List<PaymentMethodItem>,
    onSave: (String, String) -> Unit
) {
    var editMode by remember { mutableStateOf(false) }
    var locPm by remember { mutableStateOf(pm ?: "") }
    var locPd by remember { mutableStateOf(pd ?: "") }
    var expanded by remember { mutableStateOf(false) }

    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp)) {
        Column(modifier = Modifier.padding(8.dp)) {
            Text(name, style = MaterialTheme.typography.titleSmall)
            Text(email, style = MaterialTheme.typography.bodySmall)
            Spacer(modifier = Modifier.height(4.dp))
            
            if (editMode) {
                ExposedDropdownMenuBox(
                    expanded = expanded,
                    onExpandedChange = { expanded = !expanded }
                ) {
                    val displayValue = paymentMethods.find { it.methodType == locPm }?.name ?: locPm
                    AdminStyledTextField(
                        value = displayValue.ifEmpty { "Select Payment Method" },
                        onValueChange = {},
                        readOnly = true,
                        label = { Text("Payment Method") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                        modifier = Modifier.menuAnchor().fillMaxWidth()
                    )
                    ExposedDropdownMenu(
                        expanded = expanded,
                        onDismissRequest = { expanded = false }
                    ) {
                        paymentMethods.forEach { method ->
                            DropdownMenuItem(
                                text = { Text(method.name) },
                                onClick = { 
                                    locPm = method.methodType
                                    expanded = false 
                                }
                            )
                        }
                        DropdownMenuItem(
                            text = { Text("Custom/Other") },
                            onClick = { 
                                locPm = "custom"
                                expanded = false 
                            }
                        )
                    }
                }
                Spacer(modifier = Modifier.height(4.dp))
                AdminStyledTextField(
                    value = locPd,
                    onValueChange = { locPd = it },
                    label = { Text("Payment Details") },
                    modifier = Modifier.fillMaxWidth(),
                    minLines = 3
                )
                Spacer(modifier = Modifier.height(4.dp))
                Row {
                    Button(shape = MaterialTheme.shapes.medium, onClick = { 
                        onSave(locPm, locPd)
                        editMode = false 
                    }) { Text("Save") }
                    Spacer(modifier = Modifier.width(4.dp))
                    TextButton(shape = MaterialTheme.shapes.medium, onClick = { editMode = false }) { Text("Cancel") }
                }
            } else {
                Text("Method: ${pm.takeIf { !it.isNullOrBlank() } ?: "Not Set"}")
                Text("Details: ${pd.takeIf { !it.isNullOrBlank() } ?: "Not Set"}")
                Spacer(modifier = Modifier.height(4.dp))
                OutlinedButton(shape = MaterialTheme.shapes.medium, onClick = { editMode = true }) {
                    Text("Edit Info")
                }
            }
        }
    }
}

@Composable
fun AdminStyledTextField(
    value: String,
    onValueChange: (String) -> Unit,
    label: @Composable (() -> Unit)? = null,
    modifier: Modifier = Modifier,
    keyboardOptions: KeyboardOptions = KeyboardOptions.Default,
    minLines: Int = 1,
    readOnly: Boolean = false,
    trailingIcon: @Composable (() -> Unit)? = null
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = label,
        modifier = modifier,
        shape = MaterialTheme.shapes.medium,
        singleLine = minLines == 1,
        minLines = minLines,
        readOnly = readOnly,
        trailingIcon = trailingIcon,
        keyboardOptions = keyboardOptions,
        textStyle = androidx.compose.ui.text.TextStyle(fontSize = 12.sp),
        colors = OutlinedTextFieldDefaults.colors(
            unfocusedBorderColor = androidx.compose.ui.graphics.Color.Transparent,
            focusedBorderColor = MaterialTheme.colorScheme.primary,
            unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f),
            focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f)
        )
    )
}
