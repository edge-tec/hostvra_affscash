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
import androidx.compose.ui.graphics.Color
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.hilt.navigation.compose.hiltViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminPrivateOffersScreen(
    onNavigateToDetail: (Int) -> Unit,
    viewModel: AdminPrivateOffersViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val actionMessage by viewModel.actionMessage.collectAsState()
    val context = androidx.compose.ui.platform.LocalContext.current

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
                    modifier = Modifier.fillMaxSize().padding(horizontal = 16.dp),
                    contentPadding = PaddingValues(vertical = 16.dp),
                    verticalArrangement = Arrangement.spacedBy(20.dp)
                ) {
                    // Header Section
                    item {
                        Surface(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(16.dp),
                            color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f)
                        ) {
                            Row(
                                modifier = Modifier.padding(20.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Icon(
                                    imageVector = Icons.Default.Lock,
                                    contentDescription = "Private Offers",
                                    tint = MaterialTheme.colorScheme.primary,
                                    modifier = Modifier.size(40.dp)
                                )
                                Spacer(modifier = Modifier.width(16.dp))
                                Column {
                                    Text(
                                        text = "Private Offers",
                                        style = MaterialTheme.typography.titleLarge,
                                        fontWeight = FontWeight.Bold,
                                        color = MaterialTheme.colorScheme.onPrimaryContainer
                                    )
                                    Spacer(modifier = Modifier.height(4.dp))
                                    Text(
                                        text = "Manage restricted-access offers. Only visible to affiliates you manually approve.",
                                        style = MaterialTheme.typography.bodySmall,
                                        color = MaterialTheme.colorScheme.onPrimaryContainer.copy(alpha = 0.8f)
                                    )
                                }
                            }
                        }
                    }

                    // Convert Section
                    item {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(16.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                        ) {
                            Column(modifier = Modifier.padding(20.dp)) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Icon(Icons.Default.Security, contentDescription = null, tint = MaterialTheme.colorScheme.secondary)
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Text("Convert Offer to Private", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                                }
                                Spacer(modifier = Modifier.height(16.dp))
                                
                                var expanded by remember { mutableStateOf(false) }
                                var selectedOfferId by remember { mutableStateOf<Int?>(null) }
                                
                                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                                    ExposedDropdownMenuBox(
                                        expanded = expanded,
                                        onExpandedChange = { expanded = !expanded },
                                        modifier = Modifier.fillMaxWidth()
                                    ) {
                                        val selectedOffer = data.convertableOffers.find { it.id == selectedOfferId }
                                        OutlinedTextField(
                                            value = selectedOffer?.let { "OFF-${it.id} - ${it.name}" } ?: "Select an offer...",
                                            onValueChange = {},
                                            readOnly = true,
                                            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                                            leadingIcon = { Icon(Icons.Default.LocalOffer, contentDescription = null, tint = MaterialTheme.colorScheme.outline) },
                                            modifier = Modifier.menuAnchor().fillMaxWidth(),
                                            shape = RoundedCornerShape(12.dp),
                                            colors = OutlinedTextFieldDefaults.colors(
                                                unfocusedBorderColor = MaterialTheme.colorScheme.outline.copy(alpha = 0.3f)
                                            )
                                        )
                                        ExposedDropdownMenu(
                                            expanded = expanded,
                                            onDismissRequest = { expanded = false }
                                        ) {
                                            data.convertableOffers.forEach { offer ->
                                                DropdownMenuItem(
                                                    text = { Text("OFF-${offer.id} - ${offer.name}", maxLines = 1, overflow = TextOverflow.Ellipsis) },
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
                                        enabled = selectedOfferId != null,
                                        modifier = Modifier.fillMaxWidth().height(50.dp),
                                        shape = RoundedCornerShape(12.dp)
                                    ) {
                                        Icon(Icons.Default.Lock, contentDescription = null, modifier = Modifier.size(18.dp))
                                        Spacer(modifier = Modifier.width(8.dp))
                                        Text("Mark as Private")
                                    }
                                }
                                
                                Spacer(modifier = Modifier.height(16.dp))
                                Row(verticalAlignment = Alignment.Top, modifier = Modifier.background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f), RoundedCornerShape(8.dp)).padding(12.dp)) {
                                    Icon(Icons.Default.Info, contentDescription = null, tint = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.size(16.dp))
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Text(
                                        "Once private, the offer is hidden from all affiliates. You must manually grant access from the offer's manage page.",
                                        style = MaterialTheme.typography.bodySmall,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                }
                            }
                        }
                    }

                    // Active Private Offers
                    item {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(16.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                        ) {
                            Column(modifier = Modifier.padding(vertical = 16.dp)) {
                                Row(
                                    modifier = Modifier.fillMaxWidth().padding(horizontal = 20.dp),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        Icon(Icons.Default.VerifiedUser, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
                                        Spacer(modifier = Modifier.width(8.dp))
                                        Text("Active Private Offers", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                                    }
                                    Surface(
                                        shape = RoundedCornerShape(16.dp),
                                        color = MaterialTheme.colorScheme.primaryContainer
                                    ) {
                                        Text(
                                            "${data.privateOffers.size}",
                                            modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp),
                                            style = MaterialTheme.typography.labelMedium,
                                            fontWeight = FontWeight.Bold,
                                            color = MaterialTheme.colorScheme.onPrimaryContainer
                                        )
                                    }
                                }
                                Spacer(modifier = Modifier.height(12.dp))
                                Divider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))

                                if (data.privateOffers.isEmpty()) {
                                    Box(modifier = Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                            Icon(Icons.Outlined.VisibilityOff, contentDescription = null, tint = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.5f), modifier = Modifier.size(48.dp))
                                            Spacer(modifier = Modifier.height(8.dp))
                                            Text("No private offers currently.", color = MaterialTheme.colorScheme.onSurfaceVariant)
                                        }
                                    }
                                } else {
                                    data.privateOffers.forEachIndexed { index, offer ->
                                        Column(
                                            modifier = Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 16.dp)
                                        ) {
                                            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.Top) {
                                                Column(modifier = Modifier.weight(1f)) {
                                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                                        Surface(
                                                            shape = RoundedCornerShape(6.dp),
                                                            color = MaterialTheme.colorScheme.secondaryContainer
                                                        ) {
                                                            Text("OFF-${offer.id}", modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSecondaryContainer)
                                                        }
                                                        Spacer(modifier = Modifier.width(8.dp))
                                                        Text(offer.name, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.bodyLarge, maxLines = 1, overflow = TextOverflow.Ellipsis)
                                                    }
                                                    Spacer(modifier = Modifier.height(6.dp))
                                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                                        Icon(Icons.Default.MonetizationOn, contentDescription = null, modifier = Modifier.size(14.dp), tint = Color.Gray)
                                                        Spacer(modifier = Modifier.width(4.dp))
                                                        Text("${offer.payout_type} ${offer.payout ?: "0"}", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                                                        Spacer(modifier = Modifier.width(16.dp))
                                                        Icon(Icons.Default.People, contentDescription = null, modifier = Modifier.size(14.dp), tint = MaterialTheme.colorScheme.tertiary)
                                                        Spacer(modifier = Modifier.width(4.dp))
                                                        Text("${offer.access_count ?: 0} Granted", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.tertiary, fontWeight = FontWeight.Medium)
                                                    }
                                                }
                                            }
                                            
                                            Spacer(modifier = Modifier.height(16.dp))
                                            
                                            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End, verticalAlignment = Alignment.CenterVertically) {
                                                OutlinedButton(
                                                    onClick = { viewModel.makeOfferPublic(offer.id) },
                                                    shape = RoundedCornerShape(20.dp),
                                                    modifier = Modifier.height(36.dp),
                                                    contentPadding = PaddingValues(horizontal = 16.dp)
                                                ) {
                                                    Icon(Icons.Default.Public, contentDescription = null, modifier = Modifier.size(16.dp))
                                                    Spacer(modifier = Modifier.width(6.dp))
                                                    Text("Make Public", fontSize = 13.sp)
                                                }
                                                Spacer(modifier = Modifier.width(8.dp))
                                                Button(
                                                    onClick = { onNavigateToDetail(offer.id) },
                                                    shape = RoundedCornerShape(20.dp),
                                                    modifier = Modifier.height(36.dp),
                                                    contentPadding = PaddingValues(horizontal = 16.dp)
                                                ) {
                                                    Icon(Icons.Default.ManageAccounts, contentDescription = null, modifier = Modifier.size(16.dp))
                                                    Spacer(modifier = Modifier.width(6.dp))
                                                    Text("Manage Access", fontSize = 13.sp)
                                                }
                                            }
                                        }
                                        if (index < data.privateOffers.lastIndex) {
                                            Divider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.3f), modifier = Modifier.padding(horizontal = 20.dp))
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Recent Activity
                    item {
                        Card(
                            modifier = Modifier.fillMaxWidth().padding(bottom = 16.dp),
                            shape = RoundedCornerShape(16.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                        ) {
                            Column(modifier = Modifier.padding(vertical = 16.dp)) {
                                Row(
                                    modifier = Modifier.fillMaxWidth().padding(horizontal = 20.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Icon(Icons.Default.History, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Text("Recent Activity Log", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                                }
                                Spacer(modifier = Modifier.height(12.dp))
                                Divider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
                                
                                if (data.recentLog.isEmpty()) {
                                    Box(modifier = Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                                        Text("No recent activity.", color = MaterialTheme.colorScheme.onSurfaceVariant)
                                    }
                                } else {
                                    data.recentLog.forEachIndexed { index, log ->
                                        Row(modifier = Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 16.dp), verticalAlignment = Alignment.Top) {
                                            // Status Icon
                                            val isNegative = log.action == "deny" || log.action == "disable" || log.action == "remove"
                                            val iconColor = if (isNegative) MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.primary
                                            val iconBg = if (isNegative) MaterialTheme.colorScheme.errorContainer else MaterialTheme.colorScheme.primaryContainer
                                            val icon = if (isNegative) Icons.Default.Block else Icons.Default.CheckCircle
                                            
                                            Surface(
                                                shape = RoundedCornerShape(8.dp),
                                                color = iconBg,
                                                modifier = Modifier.size(40.dp)
                                            ) {
                                                Box(contentAlignment = Alignment.Center) {
                                                    Icon(icon, contentDescription = null, tint = iconColor, modifier = Modifier.size(20.dp))
                                                }
                                            }
                                            
                                            Spacer(modifier = Modifier.width(16.dp))
                                            
                                            Column(modifier = Modifier.weight(1f)) {
                                                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                                                    Text(log.action.uppercase(), style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, color = iconColor)
                                                    Text(log.created_at, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                                }
                                                Spacer(modifier = Modifier.height(4.dp))
                                                Text("Offer: ${log.offer_name ?: "Unknown"}", fontWeight = FontWeight.Medium, style = MaterialTheme.typography.bodyMedium)
                                                if (log.aff_name != null) {
                                                    Text("Affiliate: ${log.aff_name} (${log.affiliate_code})", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.8f))
                                                }
                                                if (log.details != null) {
                                                    Spacer(modifier = Modifier.height(2.dp))
                                                    Text(log.details, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                                }
                                                Text("By: ${log.source}", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.outline, modifier = Modifier.padding(top = 4.dp))
                                            }
                                        }
                                        if (index < data.recentLog.lastIndex) {
                                            Divider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.3f), modifier = Modifier.padding(horizontal = 76.dp))
                                        }
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
