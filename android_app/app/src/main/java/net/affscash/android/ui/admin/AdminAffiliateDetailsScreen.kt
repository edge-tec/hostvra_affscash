package net.affscash.android.ui.admin

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.ManagerAffiliateDetailsData
import net.affscash.android.ui.affiliates.AdminAffiliatesViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminAffiliateDetailsScreen(
    affId: Int,
    onNavigateBack: () -> Unit,
    viewModel: AdminAffiliatesViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    LaunchedEffect(affId) {
        viewModel.loadAffiliateDetails(affId)
    }

    Scaffold(
        topBar = {
            net.affscash.android.ui.components.CompactTopBar(
                title = { Text("Affiliate Details") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { padding ->
        if (uiState.isDetailsLoading) {
            Box(modifier = Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
        } else if (uiState.selectedAffiliateDetails != null) {
            val details = uiState.selectedAffiliateDetails!!
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(padding)
                    .padding(8.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                Card(modifier = Modifier.fillMaxWidth()) {
                    Column(modifier = Modifier.padding(8.dp)) {
                        Text("Profile Information", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                        Divider(modifier = Modifier.padding(vertical = 8.dp))
                        Text("Name: ${details.affiliate.firstName} ${details.affiliate.lastName}")
                        Text("Email: ${details.affiliate.email}")
                        Text("Company: ${details.affiliate.company ?: "N/A"}")
                        Text("Code: ${details.affiliate.affiliateCode}")
                        Text("Balance: ${details.affiliate.balance ?: "$0.00"}")
                        Text("Status: ${details.affiliate.status}")
                        Text("Fraud Score: ${details.affiliate.fraudScore.toInt()}")
                    }
                }

                if (details.stats != null) {
                    Card(modifier = Modifier.fillMaxWidth()) {
                        Column(modifier = Modifier.padding(8.dp)) {
                            Text("All-Time Stats", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                            Divider(modifier = Modifier.padding(vertical = 8.dp))
                            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                Column {
                                    Text("Clicks", fontWeight = FontWeight.Bold)
                                    Text("${details.stats.clicks}")
                                }
                                Column {
                                    Text("Conversions", fontWeight = FontWeight.Bold)
                                    Text("${details.stats.conv}")
                                }
                                Column {
                                    Text("Approved", fontWeight = FontWeight.Bold)
                                    Text("${details.stats.approved}")
                                }
                                Column(horizontalAlignment = Alignment.End) {
                                    Text("Payout", fontWeight = FontWeight.Bold)
                                    Text("$${String.format("%.2f", details.stats.payout)}", color = MaterialTheme.colorScheme.primary)
                                }
                            }
                        }
                    }
                }
            }
        } else {
            Box(modifier = Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) {
                Text("Failed to load details.")
            }
        }
    }
}
