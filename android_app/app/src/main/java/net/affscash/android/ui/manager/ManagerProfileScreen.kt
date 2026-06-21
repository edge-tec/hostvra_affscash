@file:OptIn(androidx.compose.material3.ExperimentalMaterial3Api::class)
package net.affscash.android.ui.manager

import android.net.Uri
import android.widget.Toast
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ExitToApp
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.compose.foundation.shape.RoundedCornerShape
import net.affscash.android.ui.components.QrCodeImage
import coil.compose.AsyncImage
import net.affscash.android.data.model.ManagerProfileResponse
import androidx.compose.material3.ExperimentalMaterial3Api

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerProfileScreen(
    onLogout: () -> Unit = {},
    viewModel: ManagerProfileViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val selectedTab by viewModel.selectedTab.collectAsState()
    val actionMessage by viewModel.actionMessage.collectAsState()
    val isSubmitting by viewModel.isSubmitting.collectAsState()
    val context = LocalContext.current

    LaunchedEffect(actionMessage) {
        actionMessage?.let {
            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
            viewModel.clearActionMessage()
        }
    }

    Scaffold(
        topBar = {
            Surface(
                modifier = Modifier.fillMaxWidth().statusBarsPadding(),
                color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.6f),
                shape = androidx.compose.foundation.shape.RoundedCornerShape(bottomStart = 24.dp, bottomEnd = 24.dp)
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(start = 16.dp, end = 16.dp, top = 8.dp, bottom = 8.dp)
                ) {
                    Text(
                        text = "Profile Settings",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                }
            }
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            val tabs = listOf("Profile", "Security", "Payment", "Google Authenticator")
            SecondaryScrollableTabRow(
                selectedTabIndex = selectedTab,
                edgePadding = 8.dp,
                containerColor = MaterialTheme.colorScheme.surface
            ) {
                tabs.forEachIndexed { index, title ->
                    val icon = when (index) {
                        0 -> Icons.Default.Person
                        1 -> Icons.Default.Lock
                        2 -> Icons.Default.ShoppingCart
                        else -> Icons.Default.VerifiedUser
                    }
                    Tab(
                        selected = selectedTab == index,
                        onClick = { viewModel.setTab(index) },
                        text = { Text(title, fontSize = 13.sp) },
                        icon = { Icon(icon, contentDescription = null, modifier = Modifier.size(20.dp)) }
                    )
                }
            }

            when (val state = uiState) {
                is ManagerProfileUiState.Loading -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator()
                    }
                }
                is ManagerProfileUiState.Error -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text(text = state.message, color = MaterialTheme.colorScheme.error)
                            Spacer(modifier = Modifier.height(8.dp))
                            Button(onClick = { viewModel.loadProfile() }) {
                                Text("Retry")
                            }
                        }
                    }
                }
                is ManagerProfileUiState.Success -> {
                    val data = state.data
                    Box(modifier = Modifier.fillMaxSize()) {
                        when (selectedTab) {
                            0 -> ProfileTabContent(data, viewModel, isSubmitting, onLogout)
                            1 -> SecurityTabContent(viewModel, isSubmitting)
                            2 -> PaymentTabContent(data, viewModel, isSubmitting)
                            3 -> GoogleAuthenticatorTabContent(data, viewModel, isSubmitting)
                        }
                        
                        if (isSubmitting) {
                            Box(
                                modifier = Modifier
                                    .fillMaxSize()
                                    .background(Color.Black.copy(alpha = 0.3f)),
                                contentAlignment = Alignment.Center
                            ) {
                                CircularProgressIndicator()
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun ProfileTabContent(
    data: ManagerProfileResponse,
    viewModel: ManagerProfileViewModel,
    isSubmitting: Boolean,
    onLogout: () -> Unit
) {
    val context = LocalContext.current
    var firstName by remember { mutableStateOf(data.profile?.firstName ?: "") }
    var lastName by remember { mutableStateOf(data.profile?.lastName ?: "") }
    var email by remember { mutableStateOf(data.profile?.email ?: "") }
    var company by remember { mutableStateOf(data.profile?.company ?: "") }
    var phone by remember { mutableStateOf(data.profile?.phone ?: "") }
    var skype by remember { mutableStateOf(data.profile?.skype ?: "") }
    var telegram by remember { mutableStateOf(data.profile?.telegram ?: "") }
    var discord by remember { mutableStateOf(data.profile?.discord ?: "") }
    
    var selectedImageUri by remember { mutableStateOf<Uri?>(null) }
    
    val launcher = rememberLauncherForActivityResult(contract = ActivityResultContracts.GetContent()) { uri: Uri? ->
        selectedImageUri = uri
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(8.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Card(
            modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp, vertical = 4.dp),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.4f)),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text("Profile Information", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
                
                Text("Profile Picture", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Box(
                        modifier = Modifier
                            .size(50.dp)
                            .clip(CircleShape)
                            .background(Color.LightGray),
                        contentAlignment = Alignment.Center
                    ) {
                        if (selectedImageUri != null) {
                            AsyncImage(
                                model = selectedImageUri,
                                contentDescription = "Profile Pic",
                                modifier = Modifier.fillMaxSize(),
                                contentScale = ContentScale.Crop
                            )
                        } else if (!data.profile?.profilePic.isNullOrEmpty()) {
                            AsyncImage(
                                model = data.profile.profilePic,
                                contentDescription = "Profile Pic",
                                modifier = Modifier.fillMaxSize(),
                                contentScale = ContentScale.Crop
                            )
                        } else {
                            Icon(Icons.Default.Person, contentDescription = null, tint = Color.White)
                        }
                    }
                    Spacer(modifier = Modifier.width(16.dp))
                    OutlinedButton(
                        onClick = { launcher.launch("image/*") },
                        shape = androidx.compose.foundation.shape.RoundedCornerShape(12.dp)
                    ) {
                        Text("Choose File")
                    }
                }
                
                Spacer(modifier = Modifier.height(8.dp))
                
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = firstName,
                        onValueChange = { firstName = it },
                        label = { Text("First Name *", fontSize = 12.sp) },
                        modifier = Modifier.weight(1f).height(56.dp),
                        singleLine = true,
                        textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                    )
                    OutlinedTextField(
                        value = lastName,
                        onValueChange = { lastName = it },
                        label = { Text("Last Name *", fontSize = 12.sp) },
                        modifier = Modifier.weight(1f).height(56.dp),
                        singleLine = true,
                        textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                    )
                }

                OutlinedTextField(
                    value = email,
                    onValueChange = { email = it },
                    label = { Text("Email Address *", fontSize = 12.sp) },
                    modifier = Modifier.fillMaxWidth().height(56.dp),
                    singleLine = true,
                    textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                )

                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = company,
                        onValueChange = { company = it },
                        label = { Text("Company", fontSize = 12.sp) },
                        modifier = Modifier.weight(1f).height(56.dp),
                        singleLine = true,
                        textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                    )
                    OutlinedTextField(
                        value = phone,
                        onValueChange = { phone = it },
                        label = { Text("Phone", fontSize = 12.sp) },
                        modifier = Modifier.weight(1f).height(56.dp),
                        singleLine = true,
                        textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                    )
                }
                
                Spacer(modifier = Modifier.height(4.dp))
                Text("Contact Handles", fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = skype,
                        onValueChange = { skype = it },
                        label = { Text("Skype", fontSize = 12.sp) },
                        modifier = Modifier.weight(1f).height(56.dp),
                        singleLine = true,
                        textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                    )
                    OutlinedTextField(
                        value = telegram,
                        onValueChange = { telegram = it },
                        label = { Text("Telegram", fontSize = 12.sp) },
                        modifier = Modifier.weight(1f).height(56.dp),
                        singleLine = true,
                        textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                    )
                }
                OutlinedTextField(
                    value = discord,
                    onValueChange = { discord = it },
                    label = { Text("Discord", fontSize = 12.sp) },
                    modifier = Modifier.fillMaxWidth().height(56.dp),
                    singleLine = true,
                    textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                )

                Button(
                    onClick = { 
                        viewModel.updateProfile(context, firstName, lastName, email, company, phone, skype, telegram, discord, selectedImageUri)
                    },
                    modifier = Modifier.padding(top = 8.dp),
                    enabled = !isSubmitting
                ) {
                    Text("Save Profile")
                }
                
                HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp))
                
                Button(
                    onClick = onLogout,
                    modifier = Modifier.fillMaxWidth(),
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    Icon(Icons.AutoMirrored.Filled.ExitToApp, contentDescription = "Logout")
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Logout")
                }
            }
        }
    }
}

@Composable
fun SecurityTabContent(
    viewModel: ManagerProfileViewModel,
    isSubmitting: Boolean
) {
    var currentPass by remember { mutableStateOf("") }
    var newPass by remember { mutableStateOf("") }
    var confirmPass by remember { mutableStateOf("") }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(8.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Card(
            modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp, vertical = 4.dp),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.4f)),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text("Change Password", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))

                OutlinedTextField(
                    value = currentPass,
                    onValueChange = { currentPass = it },
                    label = { Text("Current Password", fontSize = 12.sp) },
                    modifier = Modifier.fillMaxWidth().height(56.dp),
                    visualTransformation = PasswordVisualTransformation(),
                    singleLine = true,
                    textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                )

                OutlinedTextField(
                    value = newPass,
                    onValueChange = { newPass = it },
                    label = { Text("New Password", fontSize = 12.sp) },
                    modifier = Modifier.fillMaxWidth().height(56.dp),
                    visualTransformation = PasswordVisualTransformation(),
                    singleLine = true,
                    textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                )

                OutlinedTextField(
                    value = confirmPass,
                    onValueChange = { confirmPass = it },
                    label = { Text("Confirm New Password", fontSize = 12.sp) },
                    modifier = Modifier.fillMaxWidth().height(56.dp),
                    visualTransformation = PasswordVisualTransformation(),
                    singleLine = true,
                    textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                )

                Button(
                    onClick = { viewModel.updateSecurity(currentPass, newPass, confirmPass) },
                    modifier = Modifier.padding(top = 8.dp),
                    enabled = !isSubmitting && currentPass.isNotEmpty() && newPass.isNotEmpty()
                ) {
                    Text("Update Password")
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PaymentTabContent(
    data: ManagerProfileResponse,
    viewModel: ManagerProfileViewModel,
    isSubmitting: Boolean
) {
    var method by remember { mutableStateOf(data.payment?.method ?: "") }
    var details by remember { mutableStateOf(data.payment?.details ?: "") }

    val methods = listOf("PayPal", "Payoneer", "Wise", "Bank Wire", "Cryptocurrency", "Other")
    var expanded by remember { mutableStateOf(false) }
    var selectedMethod by remember { mutableStateOf(method) }

    val detailsJson = remember(details) {
        try {
            org.json.JSONObject(details)
        } catch (_: Exception) {
            org.json.JSONObject()
        }
    }

    var accountHolderName by remember { mutableStateOf(detailsJson.optString("account_holder_name", "")) }
    var emailId by remember { mutableStateOf(detailsJson.optString("email", "")) }
    var bankName by remember { mutableStateOf(detailsJson.optString("bank_name", "")) }
    var accountNumber by remember { mutableStateOf(detailsJson.optString("account_number", "")) }
    var ibanSwift by remember { mutableStateOf(detailsJson.optString("iban_swift", "")) }
    var routingNumber by remember { mutableStateOf(detailsJson.optString("routing_number", "")) }
    var branchName by remember { mutableStateOf(detailsJson.optString("branch_name", "")) }
    var bankAddress by remember { mutableStateOf(detailsJson.optString("bank_address", "")) }
    var cryptoType by remember { mutableStateOf(detailsJson.optString("crypto_type", "")) }
    var networkType by remember { mutableStateOf(detailsJson.optString("network_type", "")) }
    var walletAddress by remember { mutableStateOf(detailsJson.optString("wallet_address", "")) }
    var customDetails by remember { mutableStateOf(if (detailsJson.length() == 0) details else "") }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(8.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Card(
            modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp, vertical = 4.dp),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.4f)),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text("Payment Information", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))

                ExposedDropdownMenuBox(
                    expanded = expanded,
                    onExpandedChange = { expanded = !expanded }
                ) {
                    OutlinedTextField(
                        value = selectedMethod,
                        onValueChange = { selectedMethod = it },
                        label = { Text("Payment Method") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                        modifier = Modifier.menuAnchor().fillMaxWidth()
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
                
                val lowerMethod = selectedMethod.lowercase()
                val type = when {
                    lowerMethod.contains("crypto") -> "crypto"
                    lowerMethod.contains("wire") || lowerMethod.contains("bank") -> "wire"
                    lowerMethod.contains("paypal") || lowerMethod.contains("payoneer") || lowerMethod.contains("wise") -> "simple"
                    else -> "custom"
                }

                when (type) {
                    "simple" -> {
                        OutlinedTextField(
                            value = accountHolderName,
                            onValueChange = { accountHolderName = it },
                            label = { Text("Account Holder Name *", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            singleLine = true,
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                        OutlinedTextField(
                            value = emailId,
                            onValueChange = { emailId = it },
                            label = { Text("Email / Account ID *", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            singleLine = true,
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                    }
                    "wire" -> {
                        OutlinedTextField(
                            value = accountHolderName,
                            onValueChange = { accountHolderName = it },
                            label = { Text("Account Holder Name *", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            singleLine = true,
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                        OutlinedTextField(
                            value = bankName,
                            onValueChange = { bankName = it },
                            label = { Text("Bank Name *", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            singleLine = true,
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                        OutlinedTextField(
                            value = accountNumber,
                            onValueChange = { accountNumber = it },
                            label = { Text("Account Number *", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            singleLine = true,
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                        OutlinedTextField(
                            value = ibanSwift,
                            onValueChange = { ibanSwift = it },
                            label = { Text("IBAN / SWIFT Code", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            singleLine = true,
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                        OutlinedTextField(
                            value = routingNumber,
                            onValueChange = { routingNumber = it },
                            label = { Text("Routing Number", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            singleLine = true,
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                        OutlinedTextField(
                            value = branchName,
                            onValueChange = { branchName = it },
                            label = { Text("Branch Name", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            singleLine = true,
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                        OutlinedTextField(
                            value = bankAddress,
                            onValueChange = { bankAddress = it },
                            label = { Text("Bank Address *", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                    }
                    "crypto" -> {
                        OutlinedTextField(
                            value = cryptoType,
                            onValueChange = { cryptoType = it },
                            label = { Text("Cryptocurrency (e.g. USDT) *", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            singleLine = true,
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                        OutlinedTextField(
                            value = networkType,
                            onValueChange = { networkType = it },
                            label = { Text("Network Type (e.g. TRC20) *", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            singleLine = true,
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                        OutlinedTextField(
                            value = walletAddress,
                            onValueChange = { walletAddress = it },
                            label = { Text("Wallet Address *", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            singleLine = true,
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                    }
                    else -> {
                        OutlinedTextField(
                            value = customDetails,
                            onValueChange = { customDetails = it },
                            label = { Text("Payment Details", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(120.dp),
                            placeholder = { Text("Enter your account numbers, crypto addresses, or emails here.", fontSize = 12.sp) },
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                    }
                }

                Button(
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
                        viewModel.updatePayment(selectedMethod, updatedDetails)
                    },
                    modifier = Modifier.padding(top = 8.dp),
                    enabled = !isSubmitting
                ) {
                    Text("Save Payment Details")
                }
            }
        }
    }
}

@Composable
fun GoogleAuthenticatorTabContent(
    data: ManagerProfileResponse,
    viewModel: ManagerProfileViewModel,
    isSubmitting: Boolean
) {
    val qrUrl by viewModel.qrUrl.collectAsState()
    val twoFaSecret by viewModel.twoFaSecret.collectAsState()
    var code by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(8.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Card(
            modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp, vertical = 4.dp),
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha=0.4f)),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text("Google Authenticator (2FA)", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))

                if (data.twoFactorEnabled) {
                    Text("Two-Factor Authentication is currently ENABLED.", color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                    Spacer(modifier = Modifier.height(8.dp))
                    Text("To disable, enter your current password and a valid 2FA code:", fontSize = 13.sp)
                    OutlinedTextField(
                        value = password,
                        onValueChange = { password = it },
                        label = { Text("Current Password", fontSize = 12.sp) },
                        visualTransformation = PasswordVisualTransformation(),
                        modifier = Modifier.fillMaxWidth().height(56.dp),
                        singleLine = true,
                        textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                    )
                    OutlinedTextField(
                        value = code,
                        onValueChange = { code = it },
                        label = { Text("Authenticator Code", fontSize = 12.sp) },
                        modifier = Modifier.fillMaxWidth().height(56.dp),
                        singleLine = true,
                        textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                    )
                    Button(
                        onClick = { viewModel.disable2fa(password, code) },
                        colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                    ) {
                        Text("Disable 2FA")
                    }
                } else {
                    if (qrUrl == null) {
                        Text("Two-Factor Authentication is currently DISABLED.")
                        Button(onClick = { viewModel.start2fa() }) {
                            Text("Enable 2FA")
                        }
                    } else {
                        Text("1. Scan this QR Code with Google Authenticator app:", fontSize = 13.sp)
                        QrCodeImage(
                            data = "otpauth://totp/Affscash?secret=$twoFaSecret&issuer=Affscash",
                            modifier = Modifier.size(160.dp).align(Alignment.CenterHorizontally)
                        )
                        Text("Or enter this secret manually: $twoFaSecret", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                        
                        Spacer(modifier = Modifier.height(8.dp))
                        Text("2. Enter the 6-digit code generated by the app:", fontSize = 13.sp)
                        OutlinedTextField(
                            value = code,
                            onValueChange = { code = it },
                            label = { Text("6-digit Code", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth().height(56.dp),
                            singleLine = true,
                            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp)
                        )
                        Button(
                            onClick = { viewModel.verify2fa(code) },
                            enabled = code.length >= 6
                        ) {
                            Text("Verify & Enable")
                        }
                    }
                }
            }
        }
    }
}
