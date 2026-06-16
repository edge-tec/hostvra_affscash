package com.example.affscash.ui.settings

import android.widget.Toast
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
import androidx.hilt.navigation.compose.hiltViewModel
import coil.compose.AsyncImage
import com.example.affscash.data.model.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SettingsScreen(
    role: String,
    onLogout: () -> Unit,
    onNavigateToInvoices: () -> Unit = {},
    viewModel: SettingsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    var selectedTabIndex by remember { mutableIntStateOf(0) }
    val tabs = listOf("Profile", "Security", "Payment", "My Manager", "2FA")

    Scaffold(
        topBar = {
            TopAppBar(
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
                        text = { Text(title) }
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
                            Spacer(modifier = Modifier.height(8.dp))
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
                                .padding(16.dp)
                        ) {
                            when (selectedTabIndex) {
                                0 -> ProfileTab(state.profile, viewModel)
                                1 -> SecurityTab(viewModel)
                                2 -> PaymentTab(state.payment, state.paymentMethods, viewModel)
                                3 -> ManagerTab(state.manager)
                                4 -> TwoFactorTab(state.twoFactorEnabled, viewModel)
                            }

                            Spacer(modifier = Modifier.height(32.dp))

                            // General Actions (Invoices, Impersonate, Logout)
                            if (selectedTabIndex == 0) { // Show general actions at bottom of Profile tab
                                Divider(modifier = Modifier.padding(vertical = 16.dp))
                                
                                if (role == "affiliate") {
                                    Button(
                                        onClick = onNavigateToInvoices,
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.secondary)
                                    ) {
                                        Icon(Icons.Default.PictureAsPdf, contentDescription = "Invoices")
                                        Spacer(modifier = Modifier.width(8.dp))
                                        Text("My Invoices")
                                    }
                                    Spacer(modifier = Modifier.height(16.dp))
                                }

                                if (role == "admin" || role == "manager") {
                                    Button(
                                        onClick = {
                                            viewModel.stopImpersonating(
                                                onSuccess = { onLogout() },
                                                onError = { Toast.makeText(context, it, Toast.LENGTH_SHORT).show() }
                                            )
                                        },
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.secondary)
                                    ) {
                                        Icon(Icons.Default.ExitToApp, contentDescription = "Return")
                                        Spacer(modifier = Modifier.width(8.dp))
                                        Text("Return to $role Dashboard")
                                    }
                                    Spacer(modifier = Modifier.height(16.dp))
                                }

                                OutlinedButton(
                                    onClick = onLogout,
                                    modifier = Modifier.fillMaxWidth(),
                                    colors = ButtonDefaults.outlinedButtonColors(contentColor = MaterialTheme.colorScheme.error)
                                ) {
                                    Icon(Icons.Default.Logout, contentDescription = "Logout")
                                    Spacer(modifier = Modifier.width(8.dp))
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

    Column {
        Text("Profile Information", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(16.dp))

        OutlinedTextField(
            value = firstName,
            onValueChange = { firstName = it },
            label = { Text("First Name *") },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(modifier = Modifier.height(8.dp))
        
        OutlinedTextField(
            value = lastName,
            onValueChange = { lastName = it },
            label = { Text("Last Name *") },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(modifier = Modifier.height(8.dp))

        OutlinedTextField(
            value = profile?.email ?: "",
            onValueChange = { },
            label = { Text("Email Address") },
            modifier = Modifier.fillMaxWidth(),
            readOnly = true,
            enabled = false
        )
        Text("Contact your manager to change your email address", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Spacer(modifier = Modifier.height(8.dp))

        OutlinedTextField(
            value = company,
            onValueChange = { company = it },
            label = { Text("Company") },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(modifier = Modifier.height(8.dp))

        OutlinedTextField(
            value = phone,
            onValueChange = { phone = it },
            label = { Text("Phone") },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(modifier = Modifier.height(16.dp))

        Button(
            onClick = {
                if (firstName.isBlank() || lastName.isBlank()) {
                    Toast.makeText(context, "First and last name are required", Toast.LENGTH_SHORT).show()
                    return@Button
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
            },
            enabled = !isUpdating,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(if (isUpdating) "Saving..." else "Save Profile")
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

    Column {
        Text("Change Password", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(16.dp))

        OutlinedTextField(
            value = currentPass,
            onValueChange = { currentPass = it },
            label = { Text("Current Password *") },
            modifier = Modifier.fillMaxWidth(),
            visualTransformation = PasswordVisualTransformation()
        )
        Spacer(modifier = Modifier.height(8.dp))

        OutlinedTextField(
            value = newPass,
            onValueChange = { newPass = it },
            label = { Text("New Password *") },
            modifier = Modifier.fillMaxWidth(),
            visualTransformation = PasswordVisualTransformation()
        )
        Spacer(modifier = Modifier.height(8.dp))

        OutlinedTextField(
            value = confirmPass,
            onValueChange = { confirmPass = it },
            label = { Text("Confirm New Password *") },
            modifier = Modifier.fillMaxWidth(),
            visualTransformation = PasswordVisualTransformation()
        )
        Spacer(modifier = Modifier.height(16.dp))

        Button(
            onClick = {
                if (currentPass.isBlank() || newPass.isBlank() || confirmPass.isBlank()) {
                    Toast.makeText(context, "All fields are required", Toast.LENGTH_SHORT).show()
                    return@Button
                }
                if (newPass != confirmPass) {
                    Toast.makeText(context, "Passwords do not match", Toast.LENGTH_SHORT).show()
                    return@Button
                }
                if (newPass.length < 8) {
                    Toast.makeText(context, "Password must be at least 8 characters", Toast.LENGTH_SHORT).show()
                    return@Button
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
            },
            enabled = !isUpdating,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(if (isUpdating) "Updating..." else "Change Password")
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PaymentTab(payment: PaymentInfo?, methods: List<String>, viewModel: SettingsViewModel) {
    val context = LocalContext.current
    var selectedMethod by remember { mutableStateOf(payment?.method ?: "") }
    var details by remember { mutableStateOf(payment?.details ?: "") }
    var expanded by remember { mutableStateOf(false) }
    var isUpdating by remember { mutableStateOf(false) }

    Column {
        Text("Payment Settings", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(16.dp))

        ExposedDropdownMenuBox(
            expanded = expanded,
            onExpandedChange = { expanded = !expanded }
        ) {
            OutlinedTextField(
                value = selectedMethod,
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
        Spacer(modifier = Modifier.height(16.dp))

        OutlinedTextField(
            value = details,
            onValueChange = { details = it },
            label = { Text("Payment Details") },
            modifier = Modifier.fillMaxWidth().height(150.dp),
            placeholder = { Text("Enter your account numbers, crypto addresses, or emails here.") }
        )
        Spacer(modifier = Modifier.height(16.dp))

        Button(
            onClick = {
                isUpdating = true
                viewModel.updatePayment(
                    UpdatePaymentRequest(selectedMethod, details),
                    onSuccess = { 
                        isUpdating = false
                        Toast.makeText(context, it, Toast.LENGTH_SHORT).show() 
                    },
                    onError = { 
                        isUpdating = false
                        Toast.makeText(context, it, Toast.LENGTH_SHORT).show() 
                    }
                )
            },
            enabled = !isUpdating,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(if (isUpdating) "Saving..." else "Save Payment Details")
        }
    }
}

@Composable
fun ManagerTab(manager: ManagerInfo?) {
    Column {
        Text("My Manager", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(16.dp))

        if (manager == null) {
            Text("No manager assigned to your account.")
        } else {
            Card(
                modifier = Modifier.fillMaxWidth(),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text(
                        text = "${manager.firstName} ${manager.lastName}",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    Text("Email: ${manager.email}")
                    if (!manager.company.isNullOrBlank()) Text("Company: ${manager.company}")
                    if (!manager.phone.isNullOrBlank()) Text("Phone: ${manager.phone}")
                    if (!manager.skype.isNullOrBlank()) Text("Skype: ${manager.skype}")
                    if (!manager.telegram.isNullOrBlank()) Text("Telegram: ${manager.telegram}")
                }
            }
        }
    }
}

@Composable
fun TwoFactorTab(isEnabled: Boolean, viewModel: SettingsViewModel) {
    val context = LocalContext.current
    var qrUrl by remember { mutableStateOf<String?>(null) }
    var pendingSecret by remember { mutableStateOf<String?>(null) }
    var otpCode by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var isLoading by remember { mutableStateOf(false) }

    Column {
        Text("Google Authenticator", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(16.dp))

        if (!isEnabled && pendingSecret == null) {
            Text("Protect your account with two-factor authentication.")
            Spacer(modifier = Modifier.height(16.dp))
            Button(
                onClick = {
                    isLoading = true
                    viewModel.start2fa(
                        onSuccess = { secret, url ->
                            pendingSecret = secret
                            qrUrl = url
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
            Text("1. Scan this QR code with Google Authenticator or Authy.")
            Spacer(modifier = Modifier.height(8.dp))
            AsyncImage(
                model = qrUrl,
                contentDescription = "QR Code",
                modifier = Modifier.size(200.dp).align(Alignment.CenterHorizontally)
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text("Secret Key: $pendingSecret", style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Bold)
            Spacer(modifier = Modifier.height(16.dp))
            Text("2. Enter the 6-digit code from the app to verify.")
            OutlinedTextField(
                value = otpCode,
                onValueChange = { otpCode = it },
                label = { Text("6-digit Code") },
                modifier = Modifier.fillMaxWidth()
            )
            Spacer(modifier = Modifier.height(16.dp))
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
                modifier = Modifier.fillMaxWidth()
            ) {
                Row(
                    modifier = Modifier.padding(16.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Icon(Icons.Default.CheckCircle, contentDescription = "Enabled", tint = Color(0xFF10B981))
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Two-Factor Authentication is ENABLED", color = Color(0xFF10B981), fontWeight = FontWeight.Bold)
                }
            }
            Spacer(modifier = Modifier.height(24.dp))
            Text("Disable 2FA", style = MaterialTheme.typography.titleMedium)
            Spacer(modifier = Modifier.height(8.dp))
            Text("To disable, enter your account password OR a current 6-digit OTP code.")
            Spacer(modifier = Modifier.height(8.dp))
            OutlinedTextField(
                value = password,
                onValueChange = { password = it },
                label = { Text("Account Password") },
                modifier = Modifier.fillMaxWidth(),
                visualTransformation = PasswordVisualTransformation()
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text("OR", modifier = Modifier.align(Alignment.CenterHorizontally))
            Spacer(modifier = Modifier.height(8.dp))
            OutlinedTextField(
                value = otpCode,
                onValueChange = { otpCode = it },
                label = { Text("6-digit OTP Code") },
                modifier = Modifier.fillMaxWidth()
            )
            Spacer(modifier = Modifier.height(16.dp))
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
