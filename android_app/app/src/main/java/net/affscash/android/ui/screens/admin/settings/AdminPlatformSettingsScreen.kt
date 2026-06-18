package net.affscash.android.ui.screens.admin.settings

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Save
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.NavController
import net.affscash.android.data.model.*
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
        topBar = {
            TopAppBar(
                title = { Text("Platform Settings") },
                navigationIcon = {
                    IconButton(onClick = { navController.navigateUp() }) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    navigationIconContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        },
        snackbarHost = { SnackbarHost(hostState = snackbarHostState) },
        floatingActionButton = {
            if (uiState is AdminPlatformSettingsUiState.Success) {
                FloatingActionButton(
                    onClick = {
                        val currentState = (uiState as AdminPlatformSettingsUiState.Success).config
                        viewModel.saveSettings(currentState)
                    },
                    containerColor = MaterialTheme.colorScheme.primary
                ) {
                    if (isSaving) {
                        CircularProgressIndicator(color = MaterialTheme.colorScheme.onPrimary, modifier = Modifier.size(24.dp))
                    } else {
                        Icon(Icons.Default.Save, contentDescription = "Save")
                    }
                }
            }
        }
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            when (uiState) {
                is AdminPlatformSettingsUiState.Loading -> {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
                is AdminPlatformSettingsUiState.Error -> {
                    Text(
                        text = (uiState as AdminPlatformSettingsUiState.Error).message,
                        color = MaterialTheme.colorScheme.error,
                        modifier = Modifier.align(Alignment.Center)
                    )
                }
                is AdminPlatformSettingsUiState.Success -> {
                    val config = (uiState as AdminPlatformSettingsUiState.Success).config
                    SettingsContent(config = config)
                }
            }
        }
    }
}

@Composable
fun SettingsContent(config: AdminPlatformConfig) {
    val tabs = listOf("General", "Security", "SMTP", "Conversion", "Fraud", "Turnstile", "Shortener", "VPN")
    var selectedTabIndex by remember { mutableIntStateOf(0) }

    Column(modifier = Modifier.fillMaxSize()) {
        ScrollableTabRow(
            selectedTabIndex = selectedTabIndex,
            edgePadding = 8.dp
        ) {
            tabs.forEachIndexed { index, title ->
                Tab(
                    selected = selectedTabIndex == index,
                    onClick = { selectedTabIndex = index },
                    text = { Text(title) }
                )
            }
        }

        Column(
            modifier = Modifier
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .padding(16.dp)
        ) {
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
            Spacer(modifier = Modifier.height(80.dp)) // Space for FAB
        }
    }
}

@Composable
fun GeneralSettingsTab(config: AdminPlatformConfig) {
    var app by remember { mutableStateOf(config.app ?: AppSettings()) }

    LaunchedEffect(app) {
        config.app = app
    }

    Text("General Configuration", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
    Spacer(modifier = Modifier.height(16.dp))

    OutlinedTextField(
        value = app.name ?: "",
        onValueChange = { app = app.copy(name = it) },
        label = { Text("Site Name") },
        modifier = Modifier.fillMaxWidth()
    )
    Spacer(modifier = Modifier.height(8.dp))
    OutlinedTextField(
        value = app.url ?: "",
        onValueChange = { app = app.copy(url = it) },
        label = { Text("Site URL") },
        modifier = Modifier.fillMaxWidth()
    )
    Spacer(modifier = Modifier.height(8.dp))
    OutlinedTextField(
        value = app.timezone ?: "",
        onValueChange = { app = app.copy(timezone = it) },
        label = { Text("Timezone") },
        modifier = Modifier.fillMaxWidth()
    )
    Spacer(modifier = Modifier.height(8.dp))
    OutlinedTextField(
        value = app.contactEmail ?: "",
        onValueChange = { app = app.copy(contactEmail = it) },
        label = { Text("Contact Email") },
        modifier = Modifier.fillMaxWidth()
    )
}

@Composable
fun SecuritySettingsTab(config: AdminPlatformConfig) {
    var app by remember { mutableStateOf(config.app ?: AppSettings()) }

    LaunchedEffect(app) {
        config.app = app
    }

    Text("Security & Registration", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
    Spacer(modifier = Modifier.height(16.dp))

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

    Text("SMTP Configuration", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
    Spacer(modifier = Modifier.height(16.dp))

    OutlinedTextField(
        value = smtp.host ?: "",
        onValueChange = { smtp = smtp.copy(host = it) },
        label = { Text("Host") },
        modifier = Modifier.fillMaxWidth()
    )
    Spacer(modifier = Modifier.height(8.dp))
    OutlinedTextField(
        value = smtp.port ?: "",
        onValueChange = { smtp = smtp.copy(port = it) },
        label = { Text("Port") },
        modifier = Modifier.fillMaxWidth()
    )
    Spacer(modifier = Modifier.height(8.dp))
    OutlinedTextField(
        value = smtp.username ?: "",
        onValueChange = { smtp = smtp.copy(username = it) },
        label = { Text("Username") },
        modifier = Modifier.fillMaxWidth()
    )
    Spacer(modifier = Modifier.height(8.dp))
    OutlinedTextField(
        value = smtp.password ?: "",
        onValueChange = { smtp = smtp.copy(password = it) },
        label = { Text("Password") },
        modifier = Modifier.fillMaxWidth()
    )
}

@Composable
fun ConversionSettingsTab(config: AdminPlatformConfig) {
    var conv by remember { mutableStateOf(config.conversion ?: ConversionSettings()) }

    LaunchedEffect(conv) {
        config.conversion = conv
    }

    Text("Conversion Settings", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
    Spacer(modifier = Modifier.height(16.dp))

    OutlinedTextField(
        value = conv.approvalMode ?: "",
        onValueChange = { conv = conv.copy(approvalMode = it) },
        label = { Text("Approval Mode (auto/manual)") },
        modifier = Modifier.fillMaxWidth()
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

    Text("Fraud Reports", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
    Spacer(modifier = Modifier.height(16.dp))

    SettingSwitch("Enable Fraud Reports", fraud.enabled) { fraud = fraud.copy(enabled = it) }
    SettingSwitch("Send Email Alerts", fraud.sendEmail) { fraud = fraud.copy(sendEmail = it) }
    Spacer(modifier = Modifier.height(8.dp))
    OutlinedTextField(
        value = fraud.intervalHours ?: "",
        onValueChange = { fraud = fraud.copy(intervalHours = it) },
        label = { Text("Interval Hours") },
        modifier = Modifier.fillMaxWidth()
    )
}

@Composable
fun TurnstileSettingsTab(config: AdminPlatformConfig) {
    var turnstile by remember { mutableStateOf(config.turnstile ?: TurnstileSettings()) }

    LaunchedEffect(turnstile) {
        config.turnstile = turnstile
    }

    Text("Cloudflare Turnstile", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
    Spacer(modifier = Modifier.height(16.dp))

    SettingSwitch("Enable Turnstile", turnstile.enabled) { turnstile = turnstile.copy(enabled = it) }
    Spacer(modifier = Modifier.height(8.dp))
    OutlinedTextField(
        value = turnstile.siteKey ?: "",
        onValueChange = { turnstile = turnstile.copy(siteKey = it) },
        label = { Text("Site Key") },
        modifier = Modifier.fillMaxWidth()
    )
    Spacer(modifier = Modifier.height(8.dp))
    OutlinedTextField(
        value = turnstile.secretKey ?: "",
        onValueChange = { turnstile = turnstile.copy(secretKey = it) },
        label = { Text("Secret Key") },
        modifier = Modifier.fillMaxWidth()
    )
}

@Composable
fun ShortenerSettingsTab(config: AdminPlatformConfig) {
    var shortener by remember { mutableStateOf(config.shortener ?: ShortenerSettings()) }

    LaunchedEffect(shortener) {
        config.shortener = shortener
    }

    Text("URL Shortener", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
    Spacer(modifier = Modifier.height(16.dp))

    SettingSwitch("Enable Shortener", shortener.enabled) { shortener = shortener.copy(enabled = it) }
    Spacer(modifier = Modifier.height(8.dp))
    OutlinedTextField(
        value = shortener.apiKey ?: "",
        onValueChange = { shortener = shortener.copy(apiKey = it) },
        label = { Text("API Key") },
        modifier = Modifier.fillMaxWidth()
    )
}

@Composable
fun VpnSettingsTab(config: AdminPlatformConfig) {
    var vpn by remember { mutableStateOf(config.vpnDetection ?: VpnDetectionSettings()) }

    LaunchedEffect(vpn) {
        config.vpnDetection = vpn
    }

    Text("VPN Detection", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
    Spacer(modifier = Modifier.height(16.dp))

    SettingSwitch("Enable VPN Detection", vpn.enabled) { vpn = vpn.copy(enabled = it) }
}

@Composable
fun SettingSwitch(label: String, value: String?, onValueChange: (String) -> Unit) {
    val isChecked = value == "true" || value == "1"
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 8.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(text = label, style = MaterialTheme.typography.bodyLarge)
        Switch(
            checked = isChecked,
            onCheckedChange = { onValueChange(if (it) "true" else "false") }
        )
    }
}
