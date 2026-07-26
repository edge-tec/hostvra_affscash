@file:OptIn(androidx.compose.material3.ExperimentalMaterial3Api::class)
package net.affscash.android.ui.manager

import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.WindowInsetsSides
import androidx.compose.foundation.layout.only
import androidx.compose.foundation.layout.safeDrawing
import androidx.compose.foundation.layout.windowInsetsPadding


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
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import net.affscash.android.ui.dashboard.PremiumUI
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.settings.StyledTextField
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

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(PremiumUI.PageBackground)
            .windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))
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
                    Box(
                        modifier = Modifier
                            .size(42.dp)
                            .clip(RoundedCornerShape(12.dp))
                            .background(PremiumUI.HeaderGradient),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            Icons.Default.Settings,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(22.dp)
                        )
                    }
                    Spacer(modifier = Modifier.width(10.dp))
                    Column {
                        Text(
                            text = "My Settings",
                            fontSize = 18.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF0F172A)
                        )
                        Text(
                            text = "Profile, security, payments & preferences",
                            fontSize = 12.sp,
                            color = Color(0xFF64748B)
                        )
                    }
                }
            }
        }

        val tabs = listOf("Profile", "Security", "Payment", "Authenticator")
        // 3D Scrollable Segmented Tab Row
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .horizontalScroll(rememberScrollState())
                .padding(horizontal = 12.dp, vertical = 4.dp),
            horizontalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            tabs.forEachIndexed { index, title ->
                val isSelected = selectedTab == index

                Surface(
                    onClick = { viewModel.setTab(index) },
                    shape = RoundedCornerShape(12.dp),
                    color = when {
                        isSelected -> Color(0xFF4F46E5)
                        else -> Color.White
                    },
                    border = androidx.compose.foundation.BorderStroke(
                        1.dp,
                        when {
                            isSelected -> Color(0xFF4F46E5)
                            else -> Color(0xFFE2E8F0)
                        }
                    ),
                    shadowElevation = if (isSelected) 3.dp else 1.dp
                ) {
                    Text(
                        text = title,
                        modifier = Modifier.padding(horizontal = 14.dp, vertical = 8.dp),
                        fontSize = 12.sp,
                        fontWeight = if (isSelected) FontWeight.ExtraBold else FontWeight.Medium,
                        color = when {
                            isSelected -> Color.White
                            else -> Color(0xFF475569)
                        }
                    )
                }
            }
        }

        Box(modifier = Modifier.weight(1f)) {
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
                            Spacer(modifier = Modifier.height(4.dp))
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
            .padding(12.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        GlassCard(
            modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Text(
                    text = "Profile Information",
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color(0xFF0F172A)
                )

                HorizontalDivider(color = Color(0xFFE2E8F0), thickness = 1.dp)
                
                Text("Profile Picture", fontSize = 13.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFF334155))
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    val initialLetter = (firstName.firstOrNull() ?: lastName.firstOrNull() ?: 'M').uppercaseChar().toString()
                    Box(
                        modifier = Modifier
                            .size(64.dp)
                            .clip(CircleShape)
                            .background(PremiumUI.HeaderGradient)
                            .border(2.dp, Color.White, CircleShape),
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
                            Text(
                                text = initialLetter,
                                fontSize = 24.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color.White
                            )
                        }
                    }

                    OutlinedButton(
                        onClick = { launcher.launch("image/*") },
                        shape = RoundedCornerShape(20.dp),
                        border = androidx.compose.foundation.BorderStroke(1.2.dp, Color(0xFF4F46E5)),
                        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.CloudUpload,
                            contentDescription = "Upload",
                            tint = Color(0xFF4F46E5),
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Change Photo",
                            fontSize = 12.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF4F46E5)
                        )
                    }
                }
                
                Spacer(modifier = Modifier.height(2.dp))
                
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
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

                StyledTextField(
                    value = email,
                    onValueChange = { email = it },
                    label = "Email Address *",
                    modifier = Modifier.fillMaxWidth()
                )

                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
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
                
                Spacer(modifier = Modifier.height(8.dp))
                Text("Contact Handles", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                HorizontalDivider(color = Color(0xFFE2E8F0), thickness = 1.dp)
                
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    StyledTextField(
                        value = skype,
                        onValueChange = { skype = it },
                        label = "Skype",
                        modifier = Modifier.weight(1f)
                    )
                    StyledTextField(
                        value = telegram,
                        onValueChange = { telegram = it },
                        label = "Telegram",
                        modifier = Modifier.weight(1f)
                    )
                }
                StyledTextField(
                    value = discord,
                    onValueChange = { discord = it },
                    label = "Discord",
                    modifier = Modifier.fillMaxWidth()
                )

                Button(
                    onClick = { 
                        viewModel.updateProfile(context, firstName, lastName, email, company, phone, skype, telegram, discord, selectedImageUri)
                    },
                    modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = Color(0xFF4F46E5),
                        contentColor = Color.White
                    ),
                    shape = RoundedCornerShape(12.dp),
                    enabled = !isSubmitting
                ) {
                    Text("Save Changes", modifier = Modifier.padding(vertical = 4.dp), fontWeight = FontWeight.Bold)
                }
            }
        }
        
        // Logout Section
        GlassCard(
            modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Text(
                    text = "Account Actions",
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color(0xFF0F172A)
                )

                HorizontalDivider(color = Color(0xFFE2E8F0), thickness = 1.dp)

                Button(
                    onClick = onLogout,
                    modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626)),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Icon(Icons.AutoMirrored.Filled.ExitToApp, contentDescription = "Logout")
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Log Out", modifier = Modifier.padding(vertical = 4.dp), fontWeight = FontWeight.Bold)
                }
            }
        }
        Spacer(modifier = Modifier.height(16.dp))
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
            .padding(12.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        GlassCard(
            modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Text(
                    text = "Change Password",
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color(0xFF0F172A)
                )

                HorizontalDivider(color = Color(0xFFE2E8F0), thickness = 1.dp)

                StyledTextField(
                    value = currentPass,
                    onValueChange = { currentPass = it },
                    label = "Current Password",
                    modifier = Modifier.fillMaxWidth(),
                    visualTransformation = PasswordVisualTransformation()
                )

                StyledTextField(
                    value = newPass,
                    onValueChange = { newPass = it },
                    label = "New Password",
                    modifier = Modifier.fillMaxWidth(),
                    visualTransformation = PasswordVisualTransformation()
                )

                StyledTextField(
                    value = confirmPass,
                    onValueChange = { confirmPass = it },
                    label = "Confirm New Password",
                    modifier = Modifier.fillMaxWidth(),
                    visualTransformation = PasswordVisualTransformation()
                )

                Button(
                    onClick = { viewModel.updateSecurity(currentPass, newPass, confirmPass) },
                    modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = Color(0xFF4F46E5),
                        contentColor = Color.White
                    ),
                    shape = RoundedCornerShape(12.dp),
                    enabled = !isSubmitting && currentPass.isNotEmpty() && newPass.isNotEmpty()
                ) {
                    Text("Update Password", modifier = Modifier.padding(vertical = 4.dp), fontWeight = FontWeight.Bold)
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
            .padding(12.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        GlassCard(
            modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Text(
                    text = "Payment Information",
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color(0xFF0F172A)
                )

                HorizontalDivider(color = Color(0xFFE2E8F0), thickness = 1.dp)

                ExposedDropdownMenuBox(
                    expanded = expanded,
                    onExpandedChange = { expanded = !expanded }
                ) {
                    OutlinedTextField(
                        value = selectedMethod,
                        onValueChange = { selectedMethod = it },
                        label = { Text("Payment Method") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                        modifier = Modifier.menuAnchor().fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = Color(0xFF4F46E5),
                            unfocusedBorderColor = Color(0xFFCBD5E1),
                            focusedContainerColor = Color(0xFFF8FAFC),
                            unfocusedContainerColor = Color(0xFFF8FAFC)
                        )
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
                        StyledTextField(
                            value = accountHolderName,
                            onValueChange = { accountHolderName = it },
                            label = "Account Holder Name *",
                            modifier = Modifier.fillMaxWidth()
                        )
                        StyledTextField(
                            value = emailId,
                            onValueChange = { emailId = it },
                            label = "Email / Account ID *",
                            modifier = Modifier.fillMaxWidth()
                        )
                    }
                    "wire" -> {
                        StyledTextField(
                            value = accountHolderName,
                            onValueChange = { accountHolderName = it },
                            label = "Account Holder Name *",
                            modifier = Modifier.fillMaxWidth()
                        )
                        StyledTextField(
                            value = bankName,
                            onValueChange = { bankName = it },
                            label = "Bank Name *",
                            modifier = Modifier.fillMaxWidth()
                        )
                        StyledTextField(
                            value = accountNumber,
                            onValueChange = { accountNumber = it },
                            label = "Account Number *",
                            modifier = Modifier.fillMaxWidth()
                        )
                        StyledTextField(
                            value = ibanSwift,
                            onValueChange = { ibanSwift = it },
                            label = "IBAN / SWIFT Code",
                            modifier = Modifier.fillMaxWidth()
                        )
                        StyledTextField(
                            value = routingNumber,
                            onValueChange = { routingNumber = it },
                            label = "Routing Number",
                            modifier = Modifier.fillMaxWidth()
                        )
                        StyledTextField(
                            value = branchName,
                            onValueChange = { branchName = it },
                            label = "Branch Name",
                            modifier = Modifier.fillMaxWidth()
                        )
                        StyledTextField(
                            value = bankAddress,
                            onValueChange = { bankAddress = it },
                            label = "Bank Address *",
                            modifier = Modifier.fillMaxWidth()
                        )
                    }
                    "crypto" -> {
                        StyledTextField(
                            value = cryptoType,
                            onValueChange = { cryptoType = it },
                            label = "Cryptocurrency (e.g. USDT) *",
                            modifier = Modifier.fillMaxWidth()
                        )
                        StyledTextField(
                            value = networkType,
                            onValueChange = { networkType = it },
                            label = "Network Type (e.g. TRC20) *",
                            modifier = Modifier.fillMaxWidth()
                        )
                        StyledTextField(
                            value = walletAddress,
                            onValueChange = { walletAddress = it },
                            label = "Wallet Address *",
                            modifier = Modifier.fillMaxWidth()
                        )
                    }
                    else -> {
                        StyledTextField(
                            value = customDetails,
                            onValueChange = { customDetails = it },
                            label = "Payment Details",
                            modifier = Modifier.fillMaxWidth().height(120.dp)
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
                    modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = Color(0xFF4F46E5),
                        contentColor = Color.White
                    ),
                    shape = RoundedCornerShape(12.dp),
                    enabled = !isSubmitting
                ) {
                    Text("Save Payment Details", modifier = Modifier.padding(vertical = 4.dp), fontWeight = FontWeight.Bold)
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
            .padding(12.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        GlassCard(
            modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Text(
                    text = "Google Authenticator (2FA)",
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color(0xFF0F172A)
                )

                HorizontalDivider(color = Color(0xFFE2E8F0), thickness = 1.dp)

                if (data.twoFactorEnabled) {
                    Surface(color = Color(0xFFD1FAE5), shape = RoundedCornerShape(12.dp), modifier = Modifier.fillMaxWidth()) {
                        Text("Two-Factor Authentication is currently ENABLED.", color = Color(0xFF065F46), fontWeight = FontWeight.Bold, modifier = Modifier.padding(12.dp), fontSize = 13.sp)
                    }
                    Spacer(modifier = Modifier.height(4.dp))
                    Text("To disable, enter your current password and a valid 2FA code:", fontSize = 13.sp, color = Color(0xFF64748B))
                    
                    StyledTextField(
                        value = password,
                        onValueChange = { password = it },
                        label = "Current Password",
                        modifier = Modifier.fillMaxWidth(),
                        visualTransformation = PasswordVisualTransformation()
                    )
                    
                    StyledTextField(
                        value = code,
                        onValueChange = { code = it },
                        label = "Authenticator Code",
                        modifier = Modifier.fillMaxWidth()
                    )

                    Button(
                        onClick = { viewModel.disable2fa(password, code) },
                        colors = ButtonDefaults.buttonColors(
                            containerColor = Color(0xFFDC2626),
                            contentColor = Color.White
                        ),
                        modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                        shape = RoundedCornerShape(12.dp),
                        enabled = !isSubmitting
                    ) {
                        Text("Disable 2FA", modifier = Modifier.padding(vertical = 4.dp), fontWeight = FontWeight.Bold)
                    }
                } else {
                    if (qrUrl == null) {
                        Surface(color = Color(0xFFFEF2F2), shape = RoundedCornerShape(12.dp), modifier = Modifier.fillMaxWidth()) {
                            Text("Two-Factor Authentication is currently DISABLED.", color = Color(0xFF991B1B), fontWeight = FontWeight.Bold, modifier = Modifier.padding(12.dp), fontSize = 13.sp)
                        }
                        Button(
                            onClick = { viewModel.start2fa() },
                            modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                            colors = ButtonDefaults.buttonColors(
                                containerColor = Color(0xFF4F46E5),
                                contentColor = Color.White
                            ),
                            shape = RoundedCornerShape(12.dp),
                            enabled = !isSubmitting
                        ) {
                            Text("Enable 2FA", modifier = Modifier.padding(vertical = 4.dp), fontWeight = FontWeight.Bold)
                        }
                    } else {
                        Text("1. Scan this QR Code with Google Authenticator app:", fontSize = 13.sp, color = Color(0xFF64748B))
                        
                        Box(modifier = Modifier.align(Alignment.CenterHorizontally).padding(8.dp).background(Color.White, RoundedCornerShape(12.dp)).padding(8.dp)) {
                            QrCodeImage(
                                data = "otpauth://totp/Affscash?secret=$twoFaSecret&issuer=Affscash",
                                modifier = Modifier.size(180.dp).padding(16.dp)
                            )
                        }
                        Text("Or enter this secret manually: $twoFaSecret", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A), modifier = Modifier.align(Alignment.CenterHorizontally))
                        
                        Spacer(modifier = Modifier.height(8.dp))
                        Text("2. Enter the 6-digit code generated by the app:", fontSize = 13.sp, color = Color(0xFF64748B))
                        
                        StyledTextField(
                            value = code,
                            onValueChange = { code = it },
                            label = "6-digit Code",
                            modifier = Modifier.fillMaxWidth()
                        )
                        
                        Button(
                            onClick = { viewModel.verify2fa(code) },
                            enabled = !isSubmitting && code.length >= 6,
                            colors = ButtonDefaults.buttonColors(
                                containerColor = Color(0xFF4F46E5),
                                contentColor = Color.White
                            ),
                            modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                            shape = RoundedCornerShape(12.dp)
                        ) {
                            Text("Verify & Enable", modifier = Modifier.padding(vertical = 4.dp), fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }
        }
    }
}
