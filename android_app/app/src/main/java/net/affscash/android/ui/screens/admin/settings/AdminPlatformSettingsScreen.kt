package net.affscash.android.ui.screens.admin.settings

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Save
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.NavController
import net.affscash.android.data.model.*
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminPlatformSettingsScreen(
    navController: NavController,
    viewModel: AdminPlatformSettingsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val isSaving by viewModel.isSaving.collectAsState()
    val saveMessage by viewModel.saveMessage.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }
    val coroutineScope = rememberCoroutineScope()

    LaunchedEffect(saveMessage) {
        saveMessage?.let {
            coroutineScope.launch {
                snackbarHostState.showSnackbar(it)
                viewModel.clearSaveMessage()
            }
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(hostState = snackbarHostState) },
        floatingActionButton = {
            if (uiState is AdminPlatformSettingsUiState.Success) {
                Surface(
                    onClick = {
                        val currentState = (uiState as AdminPlatformSettingsUiState.Success).config
                        viewModel.saveSettings(currentState)
                    },
                    shape = RoundedCornerShape(16.dp),
                    color = Color.Transparent,
                    shadowElevation = 6.dp
                ) {
                    Box(
                        modifier = Modifier
                            .size(56.dp)
                            .background(PremiumUI.PrimaryGradient),
                        contentAlignment = Alignment.Center
                    ) {
                        if (isSaving) {
                            CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                        } else {
                            net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.Save, contentDescription = "Save", tint = Color.White, modifier = Modifier.size(26.dp))
                        }
                    }
                }
            }
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .background(PremiumUI.PageBackground)
                .padding(paddingValues)
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
                        IconButton(onClick = { navController.navigateUp() }) {
                            net.affscash.android.ui.dashboard.GradientIcon(Icons.Default.ArrowBack, contentDescription = "Back", tint = Color(0xFF0F172A))
                        }
                        Box(
                            modifier = Modifier
                                .size(38.dp)
                                .clip(RoundedCornerShape(10.dp))
                                .background(PremiumUI.HeaderGradient),
                            contentAlignment = Alignment.Center
                        ) {
                            net.affscash.android.ui.dashboard.GradientIcon(
                                Icons.Outlined.Tune,
                                contentDescription = null,
                                tint = Color.White,
                                modifier = Modifier.size(20.dp)
                            )
                        }
                        Spacer(modifier = Modifier.width(10.dp))
                        Column {
                            Text(
                                text = "Platform Settings",
                                fontSize = 17.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF0F172A)
                            )
                            Text(
                                text = "Global configuration & rules",
                                fontSize = 11.sp,
                                color = Color(0xFF64748B)
                            )
                        }
                    }
                }
            }

            when (uiState) {
                is AdminPlatformSettingsUiState.Loading -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = Color(0xFF4F46E5))
                    }
                }
                is AdminPlatformSettingsUiState.Error -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Text(
                            text = (uiState as AdminPlatformSettingsUiState.Error).message,
                            color = Color(0xFFEF4444)
                        )
                    }
                }
                is AdminPlatformSettingsUiState.Success -> {
                    val config = (uiState as AdminPlatformSettingsUiState.Success).config
                    SettingsContent3D(config = config)
                }
            }
        }
    }
}

@Composable
fun SettingsContent3D(config: AdminPlatformConfig) {
    val tabs = listOf("General", "Security", "SMTP", "Conversion", "Fraud", "Turnstile", "Shortener", "VPN")
    var selectedTabIndex by remember { mutableIntStateOf(0) }

    Column(modifier = Modifier.fillMaxSize()) {
        // 3D Segmented Scrollable Tab Row
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .horizontalScroll(rememberScrollState())
                .padding(horizontal = 12.dp, vertical = 4.dp),
            horizontalArrangement = Arrangement.spacedBy(6.dp)
        ) {
            tabs.forEachIndexed { index, title ->
                val isSelected = selectedTabIndex == index
                Surface(
                    onClick = { selectedTabIndex = index },
                    shape = RoundedCornerShape(12.dp),
                    color = if (isSelected) Color(0xFF4F46E5) else Color.White,
                    border = BorderStroke(1.dp, if (isSelected) Color(0xFF4F46E5) else Color(0xFFE2E8F0)),
                    shadowElevation = if (isSelected) 3.dp else 1.dp
                ) {
                    Text(
                        text = title,
                        modifier = Modifier.padding(horizontal = 14.dp, vertical = 8.dp),
                        fontSize = 12.sp,
                        fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
                        color = if (isSelected) Color.White else Color(0xFF475569)
                    )
                }
            }
        }

        Column(
            modifier = Modifier
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 12.dp, vertical = 6.dp)
        ) {
            GlassCard(elevation = 2.dp) {
                Column(modifier = Modifier.fillMaxWidth().padding(4.dp)) {
                    when (selectedTabIndex) {
                        0 -> GeneralSettingsTab(config)
                        1 -> SecuritySettingsTab(config)
                        2 -> SmtpSettingsTab(config)
                        3 -> ConversionSettingsTab(config)
                        4 -> FraudSettingsTab(config)
                        5 -> TurnstileSettingsTab(config)
                        6 -> ShortenerSettingsTab(config)
                        7 -> VpnSettingsTab(config)
                    }
                }
            }
            Spacer(modifier = Modifier.height(90.dp))
        }
    }
}

@Composable
fun GeneralSettingsTab(config: AdminPlatformConfig) {
    var app by remember { mutableStateOf(config.app ?: AppSettings()) }

    LaunchedEffect(app) {
        config.app = app
    }

    Text("General Configuration", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
    Spacer(modifier = Modifier.height(8.dp))

    AdminStyledTextField(
        value = app.name ?: "",
        onValueChange = { app = app.copy(name = it) },
        label = { Text("Site Name") }
    )
    Spacer(modifier = Modifier.height(8.dp))
    AdminStyledTextField(
        value = app.url ?: "",
        onValueChange = { app = app.copy(url = it) },
        label = { Text("Site URL") }
    )
    Spacer(modifier = Modifier.height(8.dp))
    AdminStyledTextField(
        value = app.timezone ?: "",
        onValueChange = { app = app.copy(timezone = it) },
        label = { Text("Timezone") }
    )
    Spacer(modifier = Modifier.height(8.dp))
    AdminStyledTextField(
        value = app.contactEmail ?: "",
        onValueChange = { app = app.copy(contactEmail = it) },
        label = { Text("Contact Email") }
    )
}

@Composable
fun SecuritySettingsTab(config: AdminPlatformConfig) {
    var app by remember { mutableStateOf(config.app ?: AppSettings()) }

    LaunchedEffect(app) {
        config.app = app
    }

    Text("Security & Registration", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
    Spacer(modifier = Modifier.height(8.dp))

    SettingSwitch("Enable 2FA", app.twoFaEnabled) { app = app.copy(twoFaEnabled = it) }
    SettingSwitch("Email Verification", app.emailVerification) { app = app.copy(emailVerification = it) }
    SettingSwitch("Advertiser Registration", app.advRegEnabled) { app = app.copy(advRegEnabled = it) }
    SettingSwitch("Inactivity Detection", app.inactivityEnabled) { app = app.copy(inactivityEnabled = it) }
}

@Composable
fun SmtpSettingsTab(config: AdminPlatformConfig) {
    var smtp by remember { mutableStateOf(config.smtp ?: SmtpSettings()) }

    LaunchedEffect(smtp) {
        config.smtp = smtp
    }

    Text("SMTP Configuration", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
    Spacer(modifier = Modifier.height(8.dp))

    AdminStyledTextField(
        value = smtp.host ?: "",
        onValueChange = { smtp = smtp.copy(host = it) },
        label = { Text("Host") }
    )
    Spacer(modifier = Modifier.height(8.dp))
    AdminStyledTextField(
        value = smtp.port ?: "",
        onValueChange = { smtp = smtp.copy(port = it) },
        label = { Text("Port") }
    )
    Spacer(modifier = Modifier.height(8.dp))
    AdminStyledTextField(
        value = smtp.username ?: "",
        onValueChange = { smtp = smtp.copy(username = it) },
        label = { Text("Username") }
    )
    Spacer(modifier = Modifier.height(8.dp))
    AdminStyledTextField(
        value = smtp.password ?: "",
        onValueChange = { smtp = smtp.copy(password = it) },
        label = { Text("Password") }
    )
}

@Composable
fun ConversionSettingsTab(config: AdminPlatformConfig) {
    var conv by remember { mutableStateOf(config.conversion ?: ConversionSettings()) }

    LaunchedEffect(conv) {
        config.conversion = conv
    }

    Text("Conversion Settings", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
    Spacer(modifier = Modifier.height(8.dp))

    AdminStyledTextField(
        value = conv.approvalMode ?: "",
        onValueChange = { conv = conv.copy(approvalMode = it) },
        label = { Text("Approval Mode (auto/manual)") }
    )
    Spacer(modifier = Modifier.height(8.dp))
    SettingSwitch("One Lead Per IP", conv.onePerIpEnabled) { conv = conv.copy(onePerIpEnabled = it) }
    SettingSwitch("Hide Fraud Rejected Reports", conv.hideFraudRejectedReports) { conv = conv.copy(hideFraudRejectedReports = it) }
}

@Composable
fun FraudSettingsTab(config: AdminPlatformConfig) {
    var fraud by remember { mutableStateOf(config.fraudReports ?: FraudReportsSettings()) }

    LaunchedEffect(fraud) {
        config.fraudReports = fraud
    }

    Text("Fraud Reports", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
    Spacer(modifier = Modifier.height(8.dp))

    SettingSwitch("Enable Fraud Reports", fraud.enabled) { fraud = fraud.copy(enabled = it) }
    SettingSwitch("Send Email Alerts", fraud.sendEmail) { fraud = fraud.copy(sendEmail = it) }
    Spacer(modifier = Modifier.height(8.dp))
    AdminStyledTextField(
        value = fraud.intervalHours ?: "",
        onValueChange = { fraud = fraud.copy(intervalHours = it) },
        label = { Text("Interval Hours") }
    )
}

@Composable
fun TurnstileSettingsTab(config: AdminPlatformConfig) {
    var turnstile by remember { mutableStateOf(config.turnstile ?: TurnstileSettings()) }

    LaunchedEffect(turnstile) {
        config.turnstile = turnstile
    }

    Text("Cloudflare Turnstile", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
    Spacer(modifier = Modifier.height(8.dp))

    SettingSwitch("Enable Turnstile", turnstile.enabled) { turnstile = turnstile.copy(enabled = it) }
    Spacer(modifier = Modifier.height(8.dp))
    AdminStyledTextField(
        value = turnstile.siteKey ?: "",
        onValueChange = { turnstile = turnstile.copy(siteKey = it) },
        label = { Text("Site Key") }
    )
    Spacer(modifier = Modifier.height(8.dp))
    AdminStyledTextField(
        value = turnstile.secretKey ?: "",
        onValueChange = { turnstile = turnstile.copy(secretKey = it) },
        label = { Text("Secret Key") }
    )
}

@Composable
fun ShortenerSettingsTab(config: AdminPlatformConfig) {
    var shortener by remember { mutableStateOf(config.shortener ?: ShortenerSettings()) }

    LaunchedEffect(shortener) {
        config.shortener = shortener
    }

    Text("URL Shortener", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
    Spacer(modifier = Modifier.height(8.dp))

    SettingSwitch("Enable Shortener", shortener.enabled) { shortener = shortener.copy(enabled = it) }
    Spacer(modifier = Modifier.height(8.dp))
    AdminStyledTextField(
        value = shortener.apiKey ?: "",
        onValueChange = { shortener = shortener.copy(apiKey = it) },
        label = { Text("API Key") }
    )
}

@Composable
fun VpnSettingsTab(config: AdminPlatformConfig) {
    var vpn by remember { mutableStateOf(config.vpnDetection ?: VpnDetectionSettings()) }

    LaunchedEffect(vpn) {
        config.vpnDetection = vpn
    }

    Text("VPN Detection", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
    Spacer(modifier = Modifier.height(8.dp))

    SettingSwitch("Enable VPN Detection", vpn.enabled) { vpn = vpn.copy(enabled = it) }
}

@Composable
fun SettingSwitch(label: String, value: String?, onValueChange: (String) -> Unit) {
    val isChecked = value == "true" || value == "1"
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 6.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(text = label, fontSize = 13.sp, fontWeight = FontWeight.Medium, color = Color(0xFF334155))
        Switch(
            checked = isChecked,
            onCheckedChange = { onValueChange(if (it) "true" else "false") },
            colors = SwitchDefaults.colors(checkedThumbColor = Color(0xFF4F46E5), checkedTrackColor = Color(0xFFEEF2FF))
        )
    }
}

@Composable
fun AdminStyledTextField(
    value: String,
    onValueChange: (String) -> Unit,
    label: @Composable (() -> Unit)? = null,
    modifier: Modifier = Modifier
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = label,
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        singleLine = true,
        textStyle = androidx.compose.ui.text.TextStyle(fontSize = 13.sp),
        colors = OutlinedTextFieldDefaults.colors(
            focusedBorderColor = Color(0xFF4F46E5),
            unfocusedBorderColor = Color(0xFFE2E8F0),
            focusedContainerColor = Color(0xFFF8FAFC),
            unfocusedContainerColor = Color(0xFFF8FAFC)
        )
    )
}
