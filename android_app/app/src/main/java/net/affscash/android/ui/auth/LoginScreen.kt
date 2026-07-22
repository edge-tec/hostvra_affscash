package net.affscash.android.ui.auth

import androidx.compose.animation.*
import androidx.compose.foundation.Image
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.Face
import androidx.compose.material.icons.filled.Fingerprint
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Security
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import androidx.compose.material.icons.outlined.ErrorOutline
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalUriHandler
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.fragment.app.FragmentActivity
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.Config
import net.affscash.android.R
import net.affscash.android.ui.dashboard.PremiumUI

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun LoginScreen(
    onLoginSuccess: (String) -> Unit,
    onForgotPasswordClick: () -> Unit = {},
    viewModel: AuthViewModel = hiltViewModel()
) {
    val authState by viewModel.authState.collectAsState()
    val rememberMe by viewModel.rememberMe.collectAsState()
    val biometricCap by viewModel.biometricCapability.collectAsState()
    val isBiometricEnabled by viewModel.isBiometricEnabled.collectAsState()
    val showBiometricSetupDialog by viewModel.showBiometricPromptDialog.collectAsState()

    val context = LocalContext.current
    val activity = context as? FragmentActivity

    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var passwordVisible by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        viewModel.refreshBiometricState()
    }

    LaunchedEffect(authState) {
        if (authState is AuthState.Success) {
            val user = (authState as AuthState.Success).user
            onLoginSuccess(user.role)
        }
    }

    Scaffold { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .verticalScroll(rememberScrollState())
                .padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Image(
                painter = painterResource(id = R.drawable.logo),
                contentDescription = "AffsCash Logo",
                modifier = Modifier
                    .height(100.dp)
                    .padding(bottom = 16.dp)
            )

            Text(
                text = "Welcome Back",
                style = MaterialTheme.typography.headlineMedium.copy(fontWeight = FontWeight.Bold),
                color = MaterialTheme.colorScheme.primary,
                modifier = Modifier.padding(bottom = 4.dp)
            )

            Text(
                text = "Sign in to continue to AffsCash",
                style = MaterialTheme.typography.bodyMedium,
                color = Color.Gray,
                modifier = Modifier.padding(bottom = 24.dp)
            )

            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 16.dp),
                horizontalArrangement = Arrangement.Center,
                verticalAlignment = Alignment.CenterVertically
            ) {
                if (biometricCap.isFingerprintSupported) {
                    AssistChip(
                        onClick = { },
                        label = { Text("✓ Fingerprint", style = MaterialTheme.typography.labelSmall) },
                        leadingIcon = { Icon(Icons.Default.Fingerprint, contentDescription = null, modifier = Modifier.size(16.dp)) },
                        colors = AssistChipDefaults.assistChipColors(containerColor = MaterialTheme.colorScheme.secondaryContainer.copy(alpha = 0.5f))
                    )
                    Spacer(Modifier.width(8.dp))
                }
                if (biometricCap.isFaceSupported) {
                    AssistChip(
                        onClick = { },
                        label = { Text("✓ Face ID", style = MaterialTheme.typography.labelSmall) },
                        leadingIcon = { Icon(Icons.Default.Face, contentDescription = null, modifier = Modifier.size(16.dp)) },
                        colors = AssistChipDefaults.assistChipColors(containerColor = MaterialTheme.colorScheme.tertiaryContainer.copy(alpha = 0.5f))
                    )
                    Spacer(Modifier.width(8.dp))
                }
                if (rememberMe) {
                    AssistChip(
                        onClick = { },
                        label = { Text("✓ Remembered", style = MaterialTheme.typography.labelSmall) },
                        leadingIcon = { Icon(Icons.Default.CheckCircle, contentDescription = null, modifier = Modifier.size(16.dp)) },
                        colors = AssistChipDefaults.assistChipColors(containerColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f))
                    )
                }
            }

            OutlinedTextField(
                value = email,
                onValueChange = { email = it },
                label = { Text("Email Address") },
                leadingIcon = { Icon(Icons.Default.Email, contentDescription = "Email", tint = MaterialTheme.colorScheme.primary) },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                shape = PremiumUI.CardShape,
                colors = OutlinedTextFieldDefaults.colors(
                    unfocusedBorderColor = Color.Transparent,
                    focusedBorderColor = MaterialTheme.colorScheme.primary,
                    unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f),
                    focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f)
                )
            )

            Spacer(modifier = Modifier.height(12.dp))

            OutlinedTextField(
                value = password,
                onValueChange = { password = it },
                label = { Text("Password") },
                leadingIcon = { Icon(Icons.Default.Lock, contentDescription = "Password", tint = MaterialTheme.colorScheme.primary) },
                trailingIcon = {
                    val image = if (passwordVisible) Icons.Default.Visibility else Icons.Default.VisibilityOff
                    IconButton(onClick = { passwordVisible = !passwordVisible }) {
                        Icon(imageVector = image, contentDescription = "Toggle password visibility")
                    }
                },
                visualTransformation = if (passwordVisible) VisualTransformation.None else PasswordVisualTransformation(),
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                shape = PremiumUI.CardShape,
                colors = OutlinedTextFieldDefaults.colors(
                    unfocusedBorderColor = Color.Transparent,
                    focusedBorderColor = MaterialTheme.colorScheme.primary,
                    unfocusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f),
                    focusedContainerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f)
                )
            )

            Spacer(modifier = Modifier.height(8.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.clickable { viewModel.setRememberMe(!rememberMe) }
                ) {
                    Checkbox(
                        checked = rememberMe,
                        onCheckedChange = { viewModel.setRememberMe(it) }
                    )
                    Text("Remember Me", style = MaterialTheme.typography.bodyMedium)
                }

                TextButton(onClick = onForgotPasswordClick) {
                    Text("Forgot Password?", style = MaterialTheme.typography.bodyMedium)
                }
            }

            Spacer(modifier = Modifier.height(20.dp))

            if (authState is AuthState.Error) {
                AlertDialog(
                    onDismissRequest = { viewModel.clearError() },
                    title = {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Outlined.ErrorOutline, contentDescription = "Error", tint = MaterialTheme.colorScheme.error)
                            Spacer(Modifier.width(8.dp))
                            Text("Login Failed")
                        }
                    },
                    text = {
                        Text((authState as AuthState.Error).message)
                    },
                    confirmButton = {
                        TextButton(onClick = { viewModel.clearError() }) {
                            Text("OK")
                        }
                    },
                    shape = PremiumUI.CardShape,
                    containerColor = MaterialTheme.colorScheme.surface,
                    titleContentColor = MaterialTheme.colorScheme.onSurface,
                    textContentColor = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            Button(
                onClick = { viewModel.login(email, password) },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(54.dp),
                shape = PremiumUI.CardShape,
                enabled = authState !is AuthState.Loading
            ) {
                if (authState is AuthState.Loading) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(24.dp),
                        color = MaterialTheme.colorScheme.onPrimary,
                        strokeWidth = 3.dp
                    )
                } else {
                    Text("Log In", fontSize = 16.sp, fontWeight = FontWeight.Bold)
                }
            }

            if (biometricCap.isAvailable && (isBiometricEnabled || rememberMe)) {
                Spacer(modifier = Modifier.height(16.dp))

                OutlinedButton(
                    onClick = {
                        activity?.let { viewModel.loginWithBiometrics(it) }
                    },
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(54.dp),
                    shape = PremiumUI.CardShape,
                    colors = ButtonDefaults.outlinedButtonColors(
                        contentColor = MaterialTheme.colorScheme.primary
                    )
                ) {
                    Icon(
                        imageVector = if (biometricCap.isFaceSupported) Icons.Default.Face else Icons.Default.Fingerprint,
                        contentDescription = "Biometric Login",
                        modifier = Modifier.size(24.dp)
                    )
                    Spacer(modifier = Modifier.width(12.dp))
                    Text(
                        text = if (biometricCap.isFaceSupported) "Login using Face ID / Biometrics" else "Login using Fingerprint",
                        fontSize = 15.sp,
                        fontWeight = FontWeight.SemiBold
                    )
                }
            }

            Spacer(modifier = Modifier.height(24.dp))

            val uriHandler = LocalUriHandler.current
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.Center,
                verticalAlignment = Alignment.CenterVertically
            ) {
                TextButton(onClick = { uriHandler.openUri(Config.BASE_URL + "privacy-policy") }) {
                    Text("Privacy Policy", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.secondary)
                }
                Text("•", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.secondary)
                TextButton(onClick = { uriHandler.openUri(Config.BASE_URL + "terms-of-service") }) {
                    Text("Terms & Conditions", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.secondary)
                }
            }
        }
    }

    if (showBiometricSetupDialog && activity != null) {
        AlertDialog(
            onDismissRequest = { viewModel.dismissBiometricSetupDialog() },
            icon = {
                Icon(
                    imageVector = Icons.Default.Security,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(36.dp)
                )
            },
            title = {
                Text("Enable Biometric Login", fontWeight = FontWeight.Bold)
            },
            text = {
                Text("Would you like to enable Fingerprint or Face ID login for future sign-ins?")
            },
            confirmButton = {
                Button(
                    onClick = { viewModel.enableBiometricAuth(activity) },
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Text("Enable")
                }
            },
            dismissButton = {
                TextButton(
                    onClick = { viewModel.dismissBiometricSetupDialog() }
                ) {
                    Text("Not Now")
                }
            },
            shape = PremiumUI.CardShape
        )
    }
}
