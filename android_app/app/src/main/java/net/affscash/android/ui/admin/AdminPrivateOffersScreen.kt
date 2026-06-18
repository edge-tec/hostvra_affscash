package net.affscash.android.ui.admin

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminPrivateOffersScreen(
    onNavigateToDetail: (Int) -> Unit,
    viewModel: AdminPrivateOffersViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val actionMessage by viewModel.actionMessage.collectAsState()
    val context = LocalContext.current

    LaunchedEffect(Unit) {
        viewModel.loadDashboard()
    }

    LaunchedEffect(actionMessage) {
        actionMessage?.let {
            Toast.makeText(context, it, Toast.LENGTH_SHORT).show()
            viewModel.clearActionMessage()
        }
    }

    Column(modifier = Modifier.fillMaxSize()) {
        when (val state = uiState) {
            is AdminPrivateOffersUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            }
            is AdminPrivateOffersUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(state.message, color = MaterialTheme.colorScheme.error)
                }
            }
            is AdminPrivateOffersUiState.Success -> {
                val data = state.data
                LazyColumn(
                    modifier = Modifier.fillMaxSize().padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    item {
                        Text("Private Offers", style = MaterialTheme.typography.headlineMedium)
                        Text(
                            "Restricted-access offers — only visible to admin-granted affiliates.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }

                    // Convert Section
                    item {
                        Card(modifier = Modifier.fillMaxWidth()) {
                            Column(modifier = Modifier.padding(16.dp)) {
                                Text("Convert an Offer to Private", style = MaterialTheme.typography.titleMedium)
                                Spacer(modifier = Modifier.height(8.dp))
                                
                                var expanded by remember { mutableStateOf(false) }
                                var selectedOfferId by remember { mutableStateOf<Int?>(null) }
                                
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    ExposedDropdownMenuBox(
                                        expanded = expanded,
                                        onExpandedChange = { expanded = !expanded },
                                        modifier = Modifier.weight(1f)
                                    ) {
                                        val selectedOffer = data.convertableOffers.find { it.id == selectedOfferId }
                                        OutlinedTextField(
                                            value = selectedOffer?.name ?: "— Select offer —",
                                            onValueChange = {},
                                            readOnly = true,
                                            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                                            modifier = Modifier.menuAnchor().fillMaxWidth()
                                        )
                                        ExposedDropdownMenu(
                                            expanded = expanded,
                                            onDismissRequest = { expanded = false }
                                        ) {
                                            data.convertableOffers.forEach { offer ->
                                                DropdownMenuItem(
                                                    text = { Text("OFF-${offer.id} - ${offer.name}") },
                                                    onClick = {
                                                        selectedOfferId = offer.id
                                                        expanded = false
                                                    }
                                                )
                                            }
                                        }
                                    }
                                    
                                    Button(
                                        onClick = {
                                            selectedOfferId?.let { viewModel.markOfferPrivate(it) }
                                            selectedOfferId = null
                                        },
                                        enabled = selectedOfferId != null
                                    ) {
                                        Text("Mark as Private")
                                    }
                                }
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(
                                    "Once marked private, the offer disappears from every affiliate's dashboard. Grant access to specific affiliates on the offer's detail page.",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                        }
                    }

                    // Active Private Offers
                    item {
                        Card(modifier = Modifier.fillMaxWidth()) {
                            Column(modifier = Modifier.padding(16.dp)) {
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Text("Active Private Offers", style = MaterialTheme.typography.titleMedium)
                                    Badge { Text("${data.privateOffers.size} TOTAL") }
                                }
                                Spacer(modifier = Modifier.height(8.dp))

                                if (data.privateOffers.isEmpty()) {
                                    Text("No private offers currently.", modifier = Modifier.padding(8.dp))
                                } else {
                                    data.privateOffers.forEach { offer ->
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(vertical = 8.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Column(modifier = Modifier.weight(1f)) {
                                                Text("OFF-${offer.id} · ${offer.name}", fontWeight = FontWeight.Bold)
                                                Text("${offer.payout_type} ${offer.payout ?: "0"}", style = MaterialTheme.typography.bodySmall)
                                            }
                                            
                                            Badge(containerColor = MaterialTheme.colorScheme.tertiary) { 
                                                Text("${offer.access_count ?: 0} GRANTED") 
                                            }
                                            
                                            Spacer(modifier = Modifier.width(8.dp))
                                            
                                            Column(horizontalAlignment = Alignment.End) {
                                                Button(
                                                    onClick = { onNavigateToDetail(offer.id) },
                                                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 4.dp)
                                                ) {
                                                    Text("Manage Access")
                                                }
                                                TextButton(
                                                    onClick = { viewModel.makeOfferPublic(offer.id) },
                                                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 0.dp)
                                                ) {
                                                    Text("Make Public")
                                                }
                                            }
                                        }
                                        Divider()
                                    }
                                }
                            }
                        }
                    }

                    // Recent Activity
                    item {
                        Card(modifier = Modifier.fillMaxWidth()) {
                            Column(modifier = Modifier.padding(16.dp)) {
                                Text("Recent Activity", style = MaterialTheme.typography.titleMedium)
                                Spacer(modifier = Modifier.height(8.dp))
                                
                                if (data.recentLog.isEmpty()) {
                                    Text("No recent activity.", modifier = Modifier.padding(8.dp))
                                } else {
                                    data.recentLog.forEach { log ->
                                        Column(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
                                            Row(horizontalArrangement = Arrangement.SpaceBetween, modifier = Modifier.fillMaxWidth()) {
                                                Text(log.created_at, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                                Badge(
                                                    containerColor = if (log.action == "deny") MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.secondary
                                                ) {
                                                    Text(log.action.uppercase())
                                                }
                                            }
                                            Text("Offer: ${log.offer_name ?: "Unknown"} (Source: ${log.source})")
                                            if (log.aff_name != null) {
                                                Text("Affiliate: ${log.aff_name} (${log.affiliate_code})")
                                            }
                                            if (log.details != null) {
                                                Text("Details: ${log.details}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                            }
                                        }
                                        Divider()
                                    }
                                }
                            }
                        }
                    }
                }
            }
            else -> {}
        }
    }
}
