package net.affscash.android.ui.admin

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Business
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Phone
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.AdminAdvertiserListModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminAdvertiserDetailsScreen(
    advId: Int,
    onNavigateBack: () -> Unit,
    viewModel: AdminAdvertisersViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    LaunchedEffect(advId) {
        viewModel.loadAdvertiserDetails(advId)
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Advertiser Details") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    navigationIconContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
    ) { padding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
        ) {
            if (uiState.isDetailsLoading) {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            } else if (uiState.error != null && uiState.selectedAdvertiserDetails == null) {
                Text(
                    text = uiState.error ?: "Failed to load details",
                    color = MaterialTheme.colorScheme.error,
                    modifier = Modifier.align(Alignment.Center).padding(16.dp)
                )
            } else {
                uiState.selectedAdvertiserDetails?.advertiser?.let { advertiser ->
                    Column(
                        modifier = Modifier
                            .fillMaxSize()
                            .verticalScroll(rememberScrollState())
                            .padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(16.dp)
                    ) {
                        AdvertiserProfileHeader(advertiser)
                        AdvertiserDetailsSection(advertiser)
                    }
                }
            }
        }
    }
}

@Composable
fun AdvertiserProfileHeader(advertiser: AdminAdvertiserListModel) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(
                text = "${advertiser.firstName} ${advertiser.lastName}",
                style = MaterialTheme.typography.headlineSmall,
                fontWeight = FontWeight.Bold
            )
            
            Spacer(modifier = Modifier.height(8.dp))
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceEvenly
            ) {
                StatusBadgeAdv(status = advertiser.status)
                BudgetBadge(isExempt = advertiser.budgetExempt == 1)
            }
        }
    }
}

@Composable
fun AdvertiserDetailsSection(advertiser: AdminAdvertiserListModel) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(
                text = "Contact Information",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )
            Divider()

            DetailItem(Icons.Default.Email, "Email", advertiser.email)
            if (!advertiser.phone.isNullOrBlank()) {
                DetailItem(Icons.Default.Phone, "Phone", advertiser.phone)
            }
            if (!advertiser.company.isNullOrBlank()) {
                DetailItem(Icons.Default.Business, "Company", advertiser.company)
            }
            if (!advertiser.country.isNullOrBlank()) {
                DetailItem(Icons.Default.LocationOn, "Country", advertiser.country)
            }

            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = "Account Information",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )
            Divider()

            DetailRow("Advertiser ID", advertiser.advId.toString())
            DetailRow("User ID", advertiser.userId.toString())
            DetailRow("Advertiser Code", advertiser.advertiserCode)
            DetailRow("Balance", String.format("$%.2f", advertiser.balance))
            DetailRow("Created At", advertiser.createdAt)
        }
    }
}

@Composable
private fun DetailItem(icon: ImageVector, label: String, value: String) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Icon(
            imageVector = icon,
            contentDescription = null,
            modifier = Modifier.size(20.dp),
            tint = MaterialTheme.colorScheme.primary
        )
        Spacer(modifier = Modifier.width(12.dp))
        Column {
            Text(text = label, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Text(text = value, style = MaterialTheme.typography.bodyLarge)
        }
    }
}

@Composable
private fun DetailRow(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(text = label, style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Text(text = value, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium)
    }
}
