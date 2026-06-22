package net.affscash.android.ui.settings

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.compose.foundation.shape.RoundedCornerShape
import net.affscash.android.ui.components.QrCodeImage
import net.affscash.android.ui.dashboard.PremiumUI
import net.affscash.android.data.model.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SettingsScreen(
    role: String,
    onLogout: () -> Unit,
    onNavigateToInvoices: () -> Unit = {},
    onNavigateToRewards: () -> Unit = {},
    onNavigateToShop: () -> Unit = {},
    onRoleChange: (String) -> Unit = {},
    viewModel: SettingsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    var selectedTabIndex by remember { mutableIntStateOf(0) }
    val tabs = remember(role) {
        val list = mutableListOf("Profile", "Security", "Payment")
        if (role == "affiliate") {
            list.add("Global Postback")
        }
        list.addAll(listOf("My Manager", "2FA", "Delete Account"))
        list
    }

    Scaffold(
        topBar = {
            net.affscash.android.ui.components.CompactTopBar(
                title = { Text("My Settings") },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            ScrollableTabRow(
                selectedTabIndex = selectedTabIndex,
                edgePadding = 8.dp,
                containerColor = MaterialTheme.colorScheme.surfaceVariant,
                contentColor = MaterialTheme.colorScheme.primary
            ) {
                tabs.forEachIndexed { index, title ->
                    Tab(
                        selected = selectedTabIndex == index,
                        onClick = { selectedTabIndex = index },
                        text = { 
                            if (title == "Delete Account") {
                                Text(title, color = MaterialTheme.colorScheme.error)
                            } else {
                                Text(title)
                            }
                        }
                    )
                }
            }

            Box(modifier = Modifier.weight(1f)) {
                when (val state = uiState) {
                    is SettingsUiState.Loading -> {
                        CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                    }
                    is SettingsUiState.Error -> {
                        Column(
                            modifier = Modifier.align(Alignment.Center),
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            Text(state.message, color = MaterialTheme.colorScheme.error)
                            Spacer(modifier = Modifier.height(4.dp))
                            Button(onClick = { viewModel.loadSettings() }) {
                                Text("Retry")
                            }
                        }
                    }
                    is SettingsUiState.Success -> {
                        val scrollState = rememberScrollState()
                        Column(
                            modifier = Modifier
                                .fillMaxSize()
                                .verticalScroll(scrollState)
                                .padding(8.dp)
                        ) {
                            when (tabs[selectedTabIndex]) {
                                "Profile" -> ProfileTab(state.profile, viewModel)
                                "Security" -> SecurityTab(viewModel)
                                "Payment" -> PaymentTab(state.payment, state.paymentMethods, viewModel)
                                "Global Postback" -> GlobalPostbackTab(state.globalPostback, viewModel)
                                "My Manager" -> ManagerTab(state.manager)
                                "2FA" -> TwoFactorTab(state.twoFactorEnabled, viewModel)
                                "Delete Account" -> DeleteAccountTab(state.deleteRequest, viewModel)
                            }

                            Spacer(modifier = Modifier.height(32.dp))

                            // General Actions (Invoices, Impersonate, Logout)
                            if (selectedTabIndex == 0) { // Show general actions at bottom of Profile tab
                                Divider(modifier = Modifier.padding(vertical = 8.dp))
                                
                                if (role == "affiliate") {
                                    Button(
                                        onClick = onNavigateToInvoices,
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.secondary)
                                    ) {
                                        Icon(Icons.Default.PictureAsPdf, contentDescription = "Invoices")
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Text("My Invoices")
                                    }
                                    Spacer(modifier = Modifier.height(4.dp))

                                    Button(
                                        onClick = onNavigateToRewards,
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.secondary)
                                    ) {
                                        Icon(Icons.Default.MonetizationOn, contentDescription = "Rewards")
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Text("My Rewards (Milestones)")
                                    }
                                    Spacer(modifier = Modifier.height(4.dp))

                                    Button(
                                        onClick = onNavigateToShop,
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF6C43E8))
                                    ) {
                                        Icon(Icons.Default.LocalOffer, contentDescription = "Shop")
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Text("Rewards Shop")
                                    }
                                    Spacer(modifier = Modifier.height(4.dp))
                                }

                                if (state.isImpersonating) {
                                    Button(
                                        onClick = {
                                            viewModel.stopImpersonating(
                                                onSuccess = { newRole -> 
                                                    if (newRole != null) onRoleChange(newRole) else onLogout() 
                                                },
                                                onError = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() }
                                            )
                                        },
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.secondary)
                                    ) {
                                        Icon(Icons.Default.ExitToApp, contentDescription = "Return")
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Text("Return to Dashboard")
                                    }
                                    Spacer(modifier = Modifier.height(4.dp))
                                }

                                OutlinedButton(
                                    onClick = onLogout,
                                    modifier = Modifier.fillMaxWidth(),
                                    colors = ButtonDefaults.outlinedButtonColors(contentColor = MaterialTheme.colorScheme.error)
                                ) {
                                    Icon(Icons.Default.Logout, contentDescription = "Logout")
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text("Logout")
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun ProfileTab(profile: ProfileInfo?, viewModel: SettingsViewModel) {
    val context = LocalContext.current
    var firstName by remember { mutableStateOf(profile?.firstName ?: "") }
    var lastName by remember { mutableStateOf(profile?.lastName ?: "") }
    var company by remember { mutableStateOf(profile?.company ?: "") }
    var phone by remember { mutableStateOf(profile?.phone ?: "") }
    var isUpdating by remember { mutableStateOf(false) }

    Card(
        modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp),
        shape = PremiumUI.CardShape,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.4f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.3f))
    ) {
        Column(modifier = Modifier.padding(8.dp)) {
            Text("Profile Information", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold, fontSize = 15.sp)
            Spacer(modifier = Modifier.height(4.dp))

            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                StyledTextField(
                    value = firstName,
                    onValueChange = { firstName = it },
                    label = "First Name *",
                    modifier = Modifier.weight(1f)
                )
                StyledTextField(
                    value = lastName,
                    onValueChange = { lastName = it },
                    label = "Last Name *",
                    modifier = Modifier.weight(1f)
                )
            }
            Spacer(modifier = Modifier.height(4.dp))

            StyledTextField(
                value = profile?.email ?: "",
                onValueChange = { },
                label = "Email Address",
                readOnly = true,
                enabled = false
            )
            Text("Contact your manager to change your email address", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant, fontSize = 11.sp)
            Spacer(modifier = Modifier.height(4.dp))

            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                StyledTextField(
                    value = company,
                    onValueChange = { company = it },
                    label = "Company",
                    modifier = Modifier.weight(1f)
                )
                StyledTextField(
                    value = phone,
                    onValueChange = { phone = it },
                    label = "Phone",
                    modifier = Modifier.weight(1f)
                )
            }
            Spacer(modifier = Modifier.height(4.dp))

            StyledButton(
                text = if (isUpdating) "Saving..." else "Save Profile",
                enabled = !isUpdating,
                onClick = {
                    if (firstName.isBlank() || lastName.isBlank()) {
                        Toast.makeText(context, "First and last name are required", Toast.LENGTH_SHORT).show()
                        return@StyledButton
                    }
                    isUpdating = true
                    viewModel.updateProfile(
                        UpdateProfileRequest(firstName, lastName, company, phone),
                        onSuccess = { 
                            isUpdating = false
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show() 
                        },
                        onError = { 
                            isUpdating = false
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show() 
                        }
                    )
                }
            )
        }
    }
}

@Composable
fun SecurityTab(viewModel: SettingsViewModel) {
    val context = LocalContext.current
    var currentPass by remember { mutableStateOf("") }
    var newPass by remember { mutableStateOf("") }
    var confirmPass by remember { mutableStateOf("") }
    var isUpdating by remember { mutableStateOf(false) }

    Card(
        modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp),
        shape = PremiumUI.CardShape,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.4f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.3f))
    ) {
        Column(modifier = Modifier.padding(8.dp)) {
            Text("Change Password", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold, fontSize = 15.sp)
            Spacer(modifier = Modifier.height(4.dp))

            StyledTextField(
                value = currentPass,
                onValueChange = { currentPass = it },
                label = "Current Password *",
                visualTransformation = PasswordVisualTransformation()
            )
            Spacer(modifier = Modifier.height(4.dp))

            StyledTextField(
                value = newPass,
                onValueChange = { newPass = it },
                label = "New Password *",
                visualTransformation = PasswordVisualTransformation()
            )
            Spacer(modifier = Modifier.height(4.dp))

            StyledTextField(
                value = confirmPass,
                onValueChange = { confirmPass = it },
                label = "Confirm New Password *",
                visualTransformation = PasswordVisualTransformation()
            )
            Spacer(modifier = Modifier.height(4.dp))

            StyledButton(
                text = if (isUpdating) "Updating..." else "Change Password",
                enabled = !isUpdating,
                onClick = {
                    if (currentPass.isBlank() || newPass.isBlank() || confirmPass.isBlank()) {
                        Toast.makeText(context, "All fields are required", Toast.LENGTH_SHORT).show()
                        return@StyledButton
                    }
                    if (newPass != confirmPass) {
                        Toast.makeText(context, "Passwords do not match", Toast.LENGTH_SHORT).show()
                        return@StyledButton
                    }
                    if (newPass.length < 8) {
                        Toast.makeText(context, "Password must be at least 8 characters", Toast.LENGTH_SHORT).show()
                        return@StyledButton
                    }
                    isUpdating = true
                    viewModel.updateSecurity(
                        UpdateSecurityRequest(currentPass, newPass),
                        onSuccess = { 
                            isUpdating = false
                            currentPass = ""
                            newPass = ""
                            confirmPass = ""
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show() 
                        },
                        onError = { 
                            isUpdating = false
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show() 
                        }
                    )
                }
            )
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PaymentTab(payment: PaymentInfo?, methods: List<String>, viewModel: SettingsViewModel) {
    val context = LocalContext.current
    var selectedMethod by remember { mutableStateOf(payment?.method ?: "") }
    
    // Attempt to parse JSON details
    val rawDetails = payment?.details ?: ""
    var isJson = false
    var jsonDetails = org.json.JSONObject()
    try {
        if (rawDetails.startsWith("{")) {
            jsonDetails = org.json.JSONObject(rawDetails)
            isJson = true
        }
    } catch (e: Exception) {}

    // State for structured fields
    var accountHolderName by remember { mutableStateOf(jsonDetails.optString("account_holder_name", "")) }
    var emailId by remember { mutableStateOf(jsonDetails.optString("email", "")) }
    
    var bankName by remember { mutableStateOf(jsonDetails.optString("bank_name", "")) }
    var accountNumber by remember { mutableStateOf(jsonDetails.optString("account_number", "")) }
    var ibanSwift by remember { mutableStateOf(jsonDetails.optString("iban_swift", "")) }
    var routingNumber by remember { mutableStateOf(jsonDetails.optString("routing_number", "")) }
    var branchName by remember { mutableStateOf(jsonDetails.optString("branch_name", "")) }
    var bankAddress by remember { mutableStateOf(jsonDetails.optString("bank_address", "")) }
    
    var cryptoType by remember { mutableStateOf(jsonDetails.optString("crypto_type", "")) }
    var networkType by remember { mutableStateOf(jsonDetails.optString("network_type", "")) }
    var walletAddress by remember { mutableStateOf(jsonDetails.optString("wallet_address", "")) }
    
    var customDetails by remember { mutableStateOf(if (!isJson) rawDetails else "") }

    var expanded by remember { mutableStateOf(false) }
    var isUpdating by remember { mutableStateOf(false) }

    Card(
        modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp),
        shape = PremiumUI.CardShape,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.4f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.3f))
    ) {
        Column(modifier = Modifier.padding(8.dp)) {
            Text("Payment Settings", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold, fontSize = 15.sp)
            Spacer(modifier = Modifier.height(4.dp))

            ExposedDropdownMenuBox(
                expanded = expanded,
                onExpandedChange = { expanded = !expanded }
            ) {
                StyledTextField(
                    value = selectedMethod,
                    onValueChange = {},
                    readOnly = true,
                    label = "Payment Method",
                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                    modifier = Modifier.menuAnchor()
                )
                ExposedDropdownMenu(
                    expanded = expanded,
                    onDismissRequest = { expanded = false }
                ) {
                    methods.forEach { method ->
                        DropdownMenuItem(
                            text = { Text(method) },
                            onClick = {
                                selectedMethod = method
                                expanded = false
                            }
                        )
                    }
                }
            }
            Spacer(modifier = Modifier.height(4.dp))

            val lowerMethod = selectedMethod.lowercase()
            val type = when {
                lowerMethod.contains("crypto") -> "crypto"
                lowerMethod.contains("wire") || lowerMethod.contains("bank") -> "wire"
                lowerMethod.contains("paypal") || lowerMethod.contains("payoneer") || lowerMethod.contains("wise") -> "simple"
                else -> "custom"
            }

            when (type) {
                "simple" -> {
                    StyledTextField(
                        value = accountHolderName,
                        onValueChange = { accountHolderName = it },
                        label = "Account Holder Name *"
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    StyledTextField(
                        value = emailId,
                        onValueChange = { emailId = it },
                        label = "Email / Account ID *"
                    )
                }
                "wire" -> {
                    StyledTextField(
                        value = accountHolderName,
                        onValueChange = { accountHolderName = it },
                        label = "Account Holder Name *"
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    StyledTextField(
                        value = bankName,
                        onValueChange = { bankName = it },
                        label = "Bank Name *"
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    StyledTextField(
                        value = accountNumber,
                        onValueChange = { accountNumber = it },
                        label = "Account Number *"
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    StyledTextField(
                        value = ibanSwift,
                        onValueChange = { ibanSwift = it },
                        label = "IBAN / SWIFT Code"
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    StyledTextField(
                        value = bankAddress,
                        onValueChange = { bankAddress = it },
                        label = "Bank Address *"
                    )
                }
                "crypto" -> {
                    StyledTextField(
                        value = cryptoType,
                        onValueChange = { cryptoType = it },
                        label = "Cryptocurrency (e.g. USDT) *"
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    StyledTextField(
                        value = networkType,
                        onValueChange = { networkType = it },
                        label = "Network Type (e.g. TRC20) *"
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    StyledTextField(
                        value = walletAddress,
                        onValueChange = { walletAddress = it },
                        label = "Wallet Address *"
                    )
                }
                else -> {
                    StyledTextField(
                        value = customDetails,
                        onValueChange = { customDetails = it },
                        label = "Payment Details",
                        modifier = Modifier.height(120.dp),
                        singleLine = false,
                        placeholder = { Text("Enter your account numbers, crypto addresses, or emails here.", fontSize = 12.sp) }
                    )
                }
            }
            
            Spacer(modifier = Modifier.height(4.dp))

            StyledButton(
                text = if (isUpdating) "Saving..." else "Save Payment Details",
                enabled = !isUpdating,
                onClick = {
                    val updatedDetails = when (type) {
                        "simple" -> {
                            org.json.JSONObject().apply {
                                put("account_holder_name", accountHolderName)
                                put("email", emailId)
                            }.toString()
                        }
                        "wire" -> {
                            org.json.JSONObject().apply {
                                put("account_holder_name", accountHolderName)
                                put("bank_name", bankName)
                                put("account_number", accountNumber)
                                put("iban_swift", ibanSwift)
                                put("routing_number", routingNumber)
                                put("branch_name", branchName)
                                put("bank_address", bankAddress)
                            }.toString()
                        }
                        "crypto" -> {
                            org.json.JSONObject().apply {
                                put("crypto_type", cryptoType)
                                put("network_type", networkType)
                                put("wallet_address", walletAddress)
                            }.toString()
                        }
                        else -> customDetails
                    }

                    isUpdating = true
                    viewModel.updatePayment(
                        UpdatePaymentRequest(selectedMethod, updatedDetails),
                        onSuccess = { 
                            isUpdating = false
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show() 
                        },
                        onError = { 
                            isUpdating = false
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show() 
                        }
                    )
                }
            )
        }
    }
}

@Composable
fun GlobalPostbackTab(postback: GlobalPostbackInfo?, viewModel: SettingsViewModel) {
    val context = LocalContext.current
    var url by remember { mutableStateOf(postback?.url ?: "") }
    var isUpdating by remember { mutableStateOf(false) }

    Card(
        modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp),
        shape = PremiumUI.CardShape,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.4f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.3f))
    ) {
        Column(modifier = Modifier.padding(8.dp)) {
            Text("Global Postback Settings", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
            Spacer(modifier = Modifier.height(4.dp))
            
            Text(
                "The Global Postback URL is used to notify your tracking system of conversions across all offers. " +
                "Use macros like {click_id}, {payout}, {currency}, etc. which will be replaced by our system.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Spacer(modifier = Modifier.height(4.dp))

            StyledTextField(
                value = url,
                onValueChange = { url = it },
                label = "Global Postback URL",
                placeholder = { Text("https://your-tracker.com/postback?cid={click_id}", fontSize = 12.sp) }
            )
            Spacer(modifier = Modifier.height(4.dp))

            StyledButton(
                text = if (isUpdating) "Saving..." else "Save Postback URL",
                enabled = !isUpdating,
                onClick = {
                    if (url.isBlank()) {
                        Toast.makeText(context, "Postback URL cannot be empty", Toast.LENGTH_SHORT).show()
                        return@StyledButton
                    }
                    isUpdating = true
                    viewModel.updateGlobalPostback(
                        UpdateGlobalPostbackRequest(url),
                        onSuccess = { 
                            isUpdating = false
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show() 
                        },
                        onError = { 
                            isUpdating = false
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show() 
                        }
                    )
                }
            )

            Spacer(modifier = Modifier.height(4.dp))
            Text("📋 Example Postback URLs", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
            Spacer(modifier = Modifier.height(4.dp))
            Card(
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(8.dp)) {
                    TrackerExample("Binom", "https://binom.example.com/postback?click_id={click_id}&payout={payout}&sub1={aff_sub1}")
                    TrackerExample("Keitaro", "https://keitaro.example.com/postback?subid={click_id}&revenue={payout}")
                    TrackerExample("RedTrack", "https://postback.redtrack.io/postback?clickid={click_id}&cost={payout}")
                    TrackerExample("FunnelFlux", "https://i.funnelflux.pro/postback?tid={click_id}&payout={payout}")
                    TrackerExample("Voluum", "https://trk.voluum.com/postback?cid={click_id}&payout={payout}")
                    TrackerExample("Any tracker", "https://yourtracker.com/postback?click_id={click_id}&payout={payout}&affid={affid}")
                }
            }

            Spacer(modifier = Modifier.height(4.dp))
            Text("Supported Macros", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
            Spacer(modifier = Modifier.height(4.dp))
            Card(
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(8.dp)) {
                    MacroItem("{click_id}", "your tracker's click ID (passed as click_id= in offer link)")
                    MacroItem("{payout}", "conversion payout amount")
                    MacroItem("{sub_id_1}", "sub_id_1= from offer link (stored in sub2)")
                    MacroItem("{sub_id_2}", "sub_id_2= from offer link (stored in sub3)")
                    MacroItem("{sub_id_3}", "sub_id_3= from offer link (stored in sub4)")
                    MacroItem("{sub_id_4}", "sub_id_4= from offer link (stored in sub5)")
                    MacroItem("{sub_id_5}", "sub_id_5= from offer link (stored in sub6)")
                }
            }
        }
    }
}

@Composable
fun TrackerExample(name: String, url: String) {
    Column(modifier = Modifier.padding(bottom = 12.dp)) {
        Text(name, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.labelMedium)
        Text(url, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.primary)
    }
}

@Composable
fun MacroItem(macro: String, desc: String) {
    Row(modifier = Modifier.padding(bottom = 6.dp)) {
        Text(macro, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.bodySmall, modifier = Modifier.width(80.dp), color = MaterialTheme.colorScheme.onSurface)
        Text("= $desc", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
    }
}

@Composable
fun ManagerTab(manager: ManagerInfo?) {
    Column {
        Text("Your Affiliate Manager", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(4.dp))

        if (manager == null) {
            Text("No manager assigned to your account.")
        } else {
            // Header Banner
            Card(
                modifier = Modifier.fillMaxWidth().height(200.dp),
                shape = PremiumUI.CardShape,
                colors = CardDefaults.cardColors(containerColor = Color(0xFF1E213A))
            ) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text(
                            text = "YOUR DEDICATED MANAGER",
                            color = Color.Gray,
                            fontSize = 10.sp,
                            fontWeight = FontWeight.Bold,
                            letterSpacing = 1.5.sp
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        Box(
                            modifier = Modifier
                                .size(64.dp)
                                .background(Color(0xFF6B46C1), shape = androidx.compose.foundation.shape.CircleShape),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = manager.firstName?.take(1)?.uppercase() ?: "",
                                color = Color.White,
                                fontSize = 28.sp,
                                fontWeight = FontWeight.Bold
                            )
                        }
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = "${manager.firstName ?: ""} ${manager.lastName ?: ""}",
                            color = Color.White,
                            style = MaterialTheme.typography.titleSmall,
                            fontWeight = FontWeight.Bold
                        )
                        Text(
                            text = "Affiliate Manager",
                            color = Color.Gray,
                            fontSize = 12.sp
                        )
                    }
                }
            }

            Spacer(modifier = Modifier.height(4.dp))

            // Contact Cards
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                ContactCard(
                    modifier = Modifier.weight(1f),
                    icon = Icons.Default.Email,
                    title = "EMAIL",
                    value = manager.email ?: ""
                )
                if (!manager.telegram.isNullOrBlank()) {
                    ContactCard(
                        modifier = Modifier.weight(1f),
                        icon = Icons.Default.Send,
                        title = "TELEGRAM",
                        value = manager.telegram
                    )
                } else if (!manager.skype.isNullOrBlank()) {
                    ContactCard(
                        modifier = Modifier.weight(1f),
                        icon = Icons.Default.ChatBubble,
                        title = "SKYPE",
                        value = manager.skype
                    )
                }
            }

            Spacer(modifier = Modifier.height(4.dp))

            // Info Box
            Card(
                modifier = Modifier.fillMaxWidth(),
                colors = CardDefaults.cardColors(containerColor = Color(0xFFEEF2FF)),
                shape = PremiumUI.CardShape
            ) {
                Row(modifier = Modifier.padding(8.dp), verticalAlignment = Alignment.Top) {
                    Icon(
                        imageVector = Icons.Default.Info,
                        contentDescription = "Help",
                        tint = Color(0xFF6B46C1),
                        modifier = Modifier.size(24.dp)
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(
                        text = "Need help? Your affiliate manager is your dedicated point of contact for offers, payments, and account questions. Reach out via any channel above and we'll get back to you shortly.",
                        color = Color(0xFF4338CA),
                        fontSize = 12.sp,
                        lineHeight = 18.sp
                    )
                }
            }
        }
    }
}

@Composable
fun ContactCard(modifier: Modifier = Modifier, icon: androidx.compose.ui.graphics.vector.ImageVector, title: String, value: String) {
    Card(
        modifier = modifier,
        colors = CardDefaults.cardColors(containerColor = Color.White),
        border = androidx.compose.foundation.BorderStroke(1.dp, Color(0xFFE5E7EB)),
        shape = PremiumUI.CardShape
    ) {
        Row(
            modifier = Modifier.padding(8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Box(
                modifier = Modifier
                    .size(36.dp)
                    .background(Color(0xFFE0E7FF), shape = PremiumUI.CardShape),
                contentAlignment = Alignment.Center
            ) {
                Icon(icon, contentDescription = title, tint = Color(0xFF4F46E5), modifier = Modifier.size(20.dp))
            }
            Spacer(modifier = Modifier.width(4.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(title, color = Color.Gray, fontSize = 10.sp, fontWeight = FontWeight.Bold, letterSpacing = 1.sp)
                Text(value, color = Color.Black, fontSize = 12.sp, fontWeight = FontWeight.Bold, maxLines = 1, overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis)
            }
            Icon(Icons.Default.ChevronRight, contentDescription = "Go", tint = Color.LightGray, modifier = Modifier.size(16.dp))
        }
    }
}

@Composable
fun TwoFactorTab(isEnabled: Boolean, viewModel: SettingsViewModel) {
    val context = LocalContext.current
    var twoFaSecret by remember { mutableStateOf<String?>(null) }
    var pendingSecret by remember { mutableStateOf<String?>(null) }
    var otpCode by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var isLoading by remember { mutableStateOf(false) }

    Column {
        Text("Google Authenticator", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold, fontSize = 15.sp)
        Spacer(modifier = Modifier.height(4.dp))

        if (!isEnabled && pendingSecret == null) {
            Text("Protect your account with two-factor authentication.")
            Spacer(modifier = Modifier.height(4.dp))
            Button(
                onClick = {
                    isLoading = true
                    viewModel.start2fa(
                        onSuccess = { secret, url ->
                            pendingSecret = secret
                            twoFaSecret = secret
                            isLoading = false
                        },
                        onError = {
                            isLoading = false
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
                        }
                    )
                },
                enabled = !isLoading
            ) {
                Text(if (isLoading) "Loading..." else "Enable 2FA")
            }
        } else if (pendingSecret != null && !isEnabled) {
            Text("1. Scan this QR Code with Google Authenticator app:", fontSize = 12.sp)
            QrCodeImage(
                data = "otpauth://totp/Affscash?secret=$twoFaSecret&issuer=Affscash",
                modifier = Modifier.size(160.dp).align(Alignment.CenterHorizontally)
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text("Secret Key: $pendingSecret", style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Bold, fontSize = 12.sp)
            Spacer(modifier = Modifier.height(4.dp))
            Text("2. Enter the 6-digit code from the app to verify.", fontSize = 12.sp)
            OutlinedTextField(
                value = otpCode,
                onValueChange = { otpCode = it },
                label = { Text("6-digit Code", fontSize = 12.sp) },
                modifier = Modifier.fillMaxWidth().height(56.dp),
                textStyle = androidx.compose.ui.text.TextStyle(fontSize = 12.sp)
            )
            Spacer(modifier = Modifier.height(4.dp))
            Button(
                onClick = {
                    isLoading = true
                    viewModel.verify2fa(
                        TwoFactorVerifyRequest(pendingSecret!!, otpCode),
                        onSuccess = {
                            isLoading = false
                            pendingSecret = null
                            otpCode = ""
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
                        },
                        onError = {
                            isLoading = false
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
                        }
                    )
                },
                enabled = !isLoading,
                modifier = Modifier.fillMaxWidth()
            ) {
                Text(if (isLoading) "Verifying..." else "Verify and Enable")
            }
        } else {
            Card(
                colors = CardDefaults.cardColors(containerColor = Color(0xFF10B981).copy(alpha = 0.1f)),
                elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
                border = BorderStroke(1.dp, Color(0xFF10B981).copy(alpha = 0.2f)),
                modifier = Modifier.fillMaxWidth()
            ) {
                Row(
                    modifier = Modifier.padding(8.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Icon(Icons.Default.CheckCircle, contentDescription = "Enabled", tint = Color(0xFF10B981))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Two-Factor Authentication is ENABLED", color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                }
            }
            Spacer(modifier = Modifier.height(4.dp))
            Text("Disable 2FA", style = MaterialTheme.typography.titleSmall, fontSize = 13.sp)
            Spacer(modifier = Modifier.height(4.dp))
            Text("To disable, enter your account password OR a current 6-digit OTP code.", fontSize = 12.sp)
            Spacer(modifier = Modifier.height(4.dp))
            OutlinedTextField(
                value = password,
                onValueChange = { password = it },
                label = { Text("Account Password", fontSize = 12.sp) },
                modifier = Modifier.fillMaxWidth().height(56.dp),
                visualTransformation = PasswordVisualTransformation(),
                textStyle = androidx.compose.ui.text.TextStyle(fontSize = 12.sp)
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text("OR", modifier = Modifier.align(Alignment.CenterHorizontally), fontSize = 12.sp, fontWeight = FontWeight.Bold)
            Spacer(modifier = Modifier.height(4.dp))
            OutlinedTextField(
                value = otpCode,
                onValueChange = { otpCode = it },
                label = { Text("6-digit OTP Code", fontSize = 12.sp) },
                modifier = Modifier.fillMaxWidth().height(56.dp),
                textStyle = androidx.compose.ui.text.TextStyle(fontSize = 12.sp)
            )
            Spacer(modifier = Modifier.height(4.dp))
            Button(
                onClick = {
                    if (password.isBlank() && otpCode.isBlank()) {
                        Toast.makeText(context, "Enter password or OTP", Toast.LENGTH_SHORT).show()
                        return@Button
                    }
                    isLoading = true
                    viewModel.disable2fa(
                        TwoFactorDisableRequest(password, otpCode),
                        onSuccess = {
                            isLoading = false
                            password = ""
                            otpCode = ""
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
                        },
                        onError = {
                            isLoading = false
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
                        }
                    )
                },
                enabled = !isLoading,
                colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error),
                modifier = Modifier.fillMaxWidth()
            ) {
                Text(if (isLoading) "Disabling..." else "Disable 2FA")
            }
        }
    }
}

@Composable
fun DeleteAccountTab(deleteRequest: DeleteRequestInfo?, viewModel: SettingsViewModel) {
    val context = LocalContext.current
    var reason by remember { mutableStateOf("") }
    var isRequesting by remember { mutableStateOf(false) }

    Column {
        Text("Delete Account", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.error)
        Spacer(modifier = Modifier.height(4.dp))

        if (deleteRequest != null) {
            Card(
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(8.dp)) {
                    Text("Account Deletion Status", fontWeight = FontWeight.Bold)
                    Spacer(modifier = Modifier.height(4.dp))
                    Text("Status: ${deleteRequest.status.uppercase()}", color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Bold)
                    Spacer(modifier = Modifier.height(4.dp))
                    Text("Requested At: ${deleteRequest.requestedAt}", style = MaterialTheme.typography.bodySmall)
                    Spacer(modifier = Modifier.height(4.dp))
                    Text("Reason:", fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodySmall)
                    Text(deleteRequest.reason, style = MaterialTheme.typography.bodySmall)
                }
            }
        } else {
            Card(
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(8.dp)) {
                    Text(
                        "Warning: This action is permanent and cannot be undone. " +
                        "If you submit an account deletion request, an administrator will review it. " +
                        "Once approved, all your data, balance, and history will be permanently deleted.",
                        color = MaterialTheme.colorScheme.onErrorContainer,
                        style = MaterialTheme.typography.bodySmall
                    )
                }
            }

            Spacer(modifier = Modifier.height(4.dp))

            OutlinedTextField(
                value = reason,
                onValueChange = { reason = it },
                label = { Text("Reason for deletion (Optional but helpful)") },
                modifier = Modifier.fillMaxWidth().height(120.dp),
                maxLines = 4
            )
            Spacer(modifier = Modifier.height(4.dp))

            Button(
                onClick = {
                    if (reason.length < 10) {
                        Toast.makeText(context, "Please provide a reason (at least 10 characters).", Toast.LENGTH_SHORT).show()
                        return@Button
                    }
                    isRequesting = true
                    viewModel.requestAccountDelete(
                        DeleteAccountRequest(reason),
                        onSuccess = {
                            isRequesting = false
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
                        },
                        onError = {
                            isRequesting = false
                            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
                        }
                    )
                },
                enabled = !isRequesting,
                colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
            ) {
                if (isRequesting) {
                    CircularProgressIndicator(modifier = Modifier.size(24.dp), color = MaterialTheme.colorScheme.onError)
                } else {
                    Text("Request Account Deletion")
                }
            }
        }
    }
}

@Composable
fun StyledTextField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    modifier: Modifier = Modifier,
    readOnly: Boolean = false,
    enabled: Boolean = true,
    singleLine: Boolean = true,
    visualTransformation: androidx.compose.ui.text.input.VisualTransformation = androidx.compose.ui.text.input.VisualTransformation.None,
    placeholder: @Composable (() -> Unit)? = null,
    trailingIcon: @Composable (() -> Unit)? = null
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label, fontSize = 12.sp) },
        modifier = modifier.fillMaxWidth(),
        readOnly = readOnly,
        enabled = enabled,
        singleLine = singleLine,
        visualTransformation = visualTransformation,
        placeholder = placeholder,
        trailingIcon = trailingIcon,
        shape = MaterialTheme.shapes.medium,
        textStyle = androidx.compose.ui.text.TextStyle(fontSize = 12.sp),
        colors = OutlinedTextFieldDefaults.colors(
            unfocusedBorderColor = Color.Transparent,
            focusedBorderColor = MaterialTheme.colorScheme.primary,
            unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f),
            focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f)
        )
    )
}

@Composable
fun StyledButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true
) {
    Button(
        onClick = onClick,
        modifier = modifier.fillMaxWidth(),
        enabled = enabled,
        shape = MaterialTheme.shapes.medium,
        colors = ButtonDefaults.buttonColors(
            containerColor = MaterialTheme.colorScheme.primary
        )
    ) {
        Text(text, fontWeight = FontWeight.Bold)
    }
}
