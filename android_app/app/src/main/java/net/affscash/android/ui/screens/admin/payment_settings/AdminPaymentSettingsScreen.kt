package net.affscash.android.ui.screens.admin.payment_settings

import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import net.affscash.android.data.model.AffiliatePaymentInfo
import net.affscash.android.data.model.ManagerPaymentInfo
import net.affscash.android.data.model.OfferBasicItem
import net.affscash.android.data.model.OfferCommissionInfo
import net.affscash.android.data.model.PaymentMethodItem
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI

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
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back", tint = Color(0xFF0F172A))
                    }
                    Box(
                        modifier = Modifier
                            .size(38.dp)
                            .clip(RoundedCornerShape(10.dp))
                            .background(PremiumUI.HeaderGradient),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            Icons.Outlined.AccountBalance,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(20.dp)
                        )
                    }
                    Spacer(modifier = Modifier.width(10.dp))
                    Column {
                        Text(
                            text = "Payment Settings",
                            fontSize = 17.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF0F172A)
                        )
                        Text(
                            text = "Payment methods, terms & commissions",
                            fontSize = 11.sp,
                            color = Color(0xFF64748B)
                        )
                    }
                }
            }
        }

        // 3D Segmented Tab Row
        val tabs = listOf("Methods", "Terms", "Commission", "Payout Info")
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
                tabs.forEachIndexed { index, title ->
                    val isSelected = uiState.selectedTab == index
                    Surface(
                        onClick = { viewModel.setTab(index) },
                        modifier = Modifier.weight(1f),
                        shape = RoundedCornerShape(12.dp),
                        color = if (isSelected) Color.White else Color.Transparent,
                        shadowElevation = if (isSelected) 2.dp else 0.dp
                    ) {
                        Box(
                            modifier = Modifier.padding(vertical = 8.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = title,
                                fontSize = 11.sp,
                                fontWeight = if (isSelected) FontWeight.ExtraBold else FontWeight.Medium,
                                color = if (isSelected) Color(0xFF4F46E5) else Color(0xFF64748B)
                            )
                        }
                    }
                }
            }
        }

        if (uiState.isLoading && uiState.data == null) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = Color(0xFF4F46E5))
            }
        } else {
            uiState.data?.let { data ->
                Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
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

    LazyColumn(
        modifier = Modifier.fillMaxSize().padding(horizontal = 12.dp),
        contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp)
    ) {
        item {
            if (!showForm) {
                Button(
                    shape = PremiumUI.ButtonShape,
                    onClick = { 
                        editId = null
                        name = ""
                        type = "custom"
                        desc = ""
                        inst = ""
                        showForm = true 
                    },
                    modifier = Modifier.fillMaxWidth().height(44.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                ) {
                    Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Add Payment Method", fontWeight = FontWeight.Bold)
                }
            } else {
                GlassCard(elevation = 2.dp) {
                    Column(modifier = Modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text(if (editId == null) "Add Method" else "Edit Method", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                        
                        AdminStyledTextField(
                            value = name,
                            onValueChange = { name = it },
                            label = { Text("Method Name") }
                        )
                        AdminStyledTextField(
                            value = desc,
                            onValueChange = { desc = it },
                            label = { Text("Description") }
                        )
                        AdminStyledTextField(
                            value = inst,
                            onValueChange = { inst = it },
                            label = { Text("Instructions") },
                            minLines = 3
                        )
                        Row {
                            Button(
                                shape = PremiumUI.ButtonShape,
                                onClick = {
                                    onSave(editId, name, type, desc, inst)
                                    showForm = false
                                },
                                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF059669))
                            ) { Text("Save", fontWeight = FontWeight.Bold) }
                            Spacer(modifier = Modifier.width(8.dp))
                            TextButton(onClick = { showForm = false }) { Text("Cancel") }
                        }
                    }
                }
            }
        }

        item {
            Text("Active Payment Methods", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
        }

        items(methods) { method ->
            GlassCard(elevation = 2.dp) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Column(modifier = Modifier.weight(1f)) {
                        Text(method.name, fontWeight = FontWeight.Bold, fontSize = 14.sp, color = Color(0xFF0F172A))
                        if (!method.description.isNullOrEmpty()) {
                            Text(method.description, fontSize = 12.sp, color = Color(0xFF64748B))
                        }
                    }
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Switch(
                            checked = method.isActive == 1,
                            onCheckedChange = { onToggle(method.id) },
                            colors = SwitchDefaults.colors(checkedThumbColor = Color(0xFF4F46E5))
                        )
                        IconButton(onClick = {
                            editId = method.id
                            name = method.name
                            type = method.methodType
                            desc = method.description ?: ""
                            inst = method.instructions ?: ""
                            showForm = true
                        }) {
                            Icon(Icons.Default.Edit, contentDescription = "Edit", tint = Color(0xFF4F46E5), modifier = Modifier.size(18.dp))
                        }
                        IconButton(onClick = { onDelete(method.id) }) {
                            Icon(Icons.Default.Delete, contentDescription = "Delete", tint = Color(0xFFDC2626), modifier = Modifier.size(18.dp))
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun PaymentTermsTab(
    affiliates: List<AffiliatePaymentInfo>,
    onSaveTerms: (scope: String, terms: String, ids: List<Int>) -> Unit
) {
    var termsScope by remember { mutableStateOf("all") }
    var selectedTerms by remember { mutableStateOf("monthly") }

    LazyColumn(
        modifier = Modifier.fillMaxSize().padding(horizontal = 12.dp),
        contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp)
    ) {
        item {
            GlassCard(elevation = 2.dp) {
                Column(modifier = Modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text("Update Payment Terms", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                    
                    OutlinedTextField(
                        value = selectedTerms,
                        onValueChange = { selectedTerms = it },
                        label = { Text("Payment Terms (e.g. net15, net30, monthly, weekly)") },
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp)
                    )

                    Button(
                        shape = PremiumUI.ButtonShape,
                        onClick = { onSaveTerms(termsScope, selectedTerms, emptyList()) },
                        modifier = Modifier.fillMaxWidth().height(44.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5))
                    ) {
                        Text("Apply Terms to All Affiliates", fontWeight = FontWeight.Bold)
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
    onSaveManagerCommission: (managerId: Int, rate: Double) -> Unit,
    onSaveOfferCommission: (managerId: Int, offerId: Int, rate: Double) -> Unit,
    onDeleteOfferCommission: (id: Int) -> Unit
) {
    LazyColumn(
        modifier = Modifier.fillMaxSize().padding(horizontal = 12.dp),
        contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp)
    ) {
        items(managers) { manager ->
            GlassCard(elevation = 2.dp) {
                Column(modifier = Modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Text(manager.name, fontWeight = FontWeight.Bold, fontSize = 14.sp, color = Color(0xFF0F172A))
                    Text("Email: ${manager.email}", fontSize = 12.sp, color = Color(0xFF64748B))
                    Text("Commission Rate: ${manager.commissionRate}%", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = Color(0xFF059669))
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
    onSavePayout: (type: String, entityId: Int, method: String, details: String) -> Unit
) {
    LazyColumn(
        modifier = Modifier.fillMaxSize().padding(horizontal = 12.dp),
        contentPadding = PaddingValues(top = 4.dp, bottom = 80.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp)
    ) {
        items(affiliates) { aff ->
            GlassCard(elevation = 2.dp) {
                Column(modifier = Modifier.fillMaxWidth()) {
                    Text("#${aff.id} • ${aff.name}", fontWeight = FontWeight.Bold, fontSize = 14.sp, color = Color(0xFF0F172A))
                    Text(aff.email, fontSize = 12.sp, color = Color(0xFF64748B))
                    Spacer(modifier = Modifier.height(4.dp))
                    Text("Payment Method: ${aff.paymentMethod ?: "Not set"}", fontSize = 12.sp, color = Color(0xFF334155))
                    Text("Payout Details: ${aff.paymentDetails ?: "Not set"}", fontSize = 12.sp, color = Color(0xFF334155))
                }
            }
        }
    }
}

@Composable
private fun AdminStyledTextField(
    value: String,
    onValueChange: (String) -> Unit,
    label: @Composable (() -> Unit)? = null,
    modifier: Modifier = Modifier,
    minLines: Int = 1
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = label,
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        minLines = minLines,
        singleLine = minLines == 1,
        textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp),
        colors = OutlinedTextFieldDefaults.colors(
            focusedBorderColor = Color(0xFF4F46E5),
            unfocusedBorderColor = Color(0xFFE2E8F0),
            focusedContainerColor = Color(0xFFF8FAFC),
            unfocusedContainerColor = Color(0xFFF8FAFC)
        )
    )
}
