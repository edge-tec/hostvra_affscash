package net.affscash.android.ui.offers

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Devices
import androidx.compose.material.icons.filled.Public
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import net.affscash.android.data.model.Offer
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.jsonArray
import kotlinx.serialization.json.jsonPrimitive

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FilterDropdown(
    label: String,
    options: List<String>,
    selected: String,
    onSelected: (String) -> Unit,
    modifier: Modifier = Modifier
) {
    var expanded by remember { mutableStateOf(false) }

    ExposedDropdownMenuBox(
        expanded = expanded,
        onExpandedChange = { expanded = !expanded },
        modifier = modifier
    ) {
        OutlinedTextField(
            value = selected,
            onValueChange = {},
            readOnly = true,
            label = { Text(label, maxLines = 1, overflow = TextOverflow.Ellipsis, fontSize = 11.sp) },
            textStyle = androidx.compose.ui.text.TextStyle(fontSize = 12.sp),
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            modifier = Modifier.menuAnchor().fillMaxWidth().height(52.dp),
            colors = ExposedDropdownMenuDefaults.outlinedTextFieldColors(
                unfocusedContainerColor = MaterialTheme.colorScheme.surface,
                focusedContainerColor = MaterialTheme.colorScheme.surface
            )
        )
        ExposedDropdownMenu(
            expanded = expanded,
            onDismissRequest = { expanded = false }
        ) {
            options.forEach { option ->
                DropdownMenuItem(
                    text = { Text(option) },
                    onClick = {
                        onSelected(option)
                        expanded = false
                    }
                )
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun OfferListItem(offer: Offer, isLoadingLink: Boolean, onClick: () -> Unit, onApplyClick: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(10.dp)) {
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.Top) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = "OFF-${offer.id.toString().padStart(4, '0')}",
                        color = MaterialTheme.colorScheme.primary,
                        fontWeight = FontWeight.Bold,
                        fontSize = 10.sp
                    )
                    Spacer(modifier = Modifier.height(2.dp))
                    Text(offer.name, fontSize = 14.sp, fontWeight = FontWeight.Bold, lineHeight = 16.sp)
                    
                    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(top = 4.dp)) {
                        offer.category?.let {
                            Text(it, fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            Text(" • ", fontSize = 10.sp)
                        }
                        
                        Badge(containerColor = MaterialTheme.colorScheme.secondaryContainer) {
                            Text(offer.payoutType.uppercase(), color = MaterialTheme.colorScheme.onSecondaryContainer, fontSize = 9.sp, modifier = Modifier.padding(horizontal = 2.dp, vertical = 0.dp))
                        }
                        
                        if (offer.offerType != null) {
                            Spacer(modifier = Modifier.width(4.dp))
                            Badge(containerColor = MaterialTheme.colorScheme.tertiaryContainer) {
                                Text(offer.offerType.uppercase(), color = MaterialTheme.colorScheme.onTertiaryContainer, fontSize = 9.sp, modifier = Modifier.padding(horizontal = 2.dp, vertical = 0.dp))
                            }
                        }
                    }
                }
                
                Column(horizontalAlignment = Alignment.End) {
                    Text(
                        text = "$${String.format("%.2f", offer.payout)}",
                        fontSize = 16.sp,
                        color = Color(0xFF10B981), // Green color matching web
                        fontWeight = FontWeight.Bold
                    )
                }
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            
            offer.description?.let {
                Text(it, fontSize = 11.sp, maxLines = 2, overflow = TextOverflow.Ellipsis, color = MaterialTheme.colorScheme.onSurfaceVariant, lineHeight = 14.sp)
                Spacer(modifier = Modifier.height(8.dp))
            }
            
            Divider(color = MaterialTheme.colorScheme.surfaceVariant)
            Spacer(modifier = Modifier.height(8.dp))
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Geo and Devices
                Column {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Public, contentDescription = "GEO", modifier = Modifier.size(12.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(modifier = Modifier.width(4.dp))
                        val geos = parseJsonArray(offer.countries)
                        if (geos.isEmpty()) Text("Global", fontSize = 10.sp)
                        else Text(geos.take(3).joinToString(", ") + if (geos.size > 3) " +${geos.size-3}" else "", fontSize = 10.sp)
                    }
                    Spacer(modifier = Modifier.height(4.dp))
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Devices, contentDescription = "Devices", modifier = Modifier.size(12.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(modifier = Modifier.width(4.dp))
                        val devs = parseJsonArray(offer.devices)
                        if (devs.isEmpty()) Text("All Devices", fontSize = 10.sp)
                        else Text(devs.joinToString(", "), fontSize = 10.sp)
                    }
                }
                
                // Status and Action
                Column(horizontalAlignment = Alignment.End) {
                    val statusText = when(offer.accessStatus) {
                        "approved" -> "APPROVED"
                        "pending" -> "PENDING"
                        "rejected" -> "REJECTED"
                        else -> "NOT APPLIED"
                    }
                    val statusColor = when(offer.accessStatus) {
                        "approved" -> Color(0xFF10B981)
                        "pending" -> Color(0xFFF59E0B)
                        "rejected" -> Color(0xFFEF4444)
                        else -> MaterialTheme.colorScheme.onSurfaceVariant
                    }
                    
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Box(modifier = Modifier.size(6.dp).background(statusColor, RoundedCornerShape(50)))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(statusText, fontSize = 9.sp, fontWeight = FontWeight.Bold, color = statusColor)
                    }
                    
                    Spacer(modifier = Modifier.height(6.dp))
                    
                    if (offer.accessStatus == "approved") {
                        Button(
                            onClick = onClick,
                            enabled = !isLoadingLink,
                            colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary),
                            contentPadding = PaddingValues(horizontal = 10.dp, vertical = 0.dp),
                            modifier = Modifier.height(28.dp)
                        ) {
                            if (isLoadingLink) {
                                CircularProgressIndicator(modifier = Modifier.size(14.dp), color = MaterialTheme.colorScheme.onPrimary, strokeWidth = 2.dp)
                            } else {
                                Text("Get Link", fontSize = 10.sp)
                            }
                        }
                    } else if (offer.accessStatus == null || offer.accessStatus == "removed") {
                        Button(
                            onClick = onApplyClick,
                            colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.secondary),
                            contentPadding = PaddingValues(horizontal = 10.dp, vertical = 0.dp),
                            modifier = Modifier.height(28.dp)
                        ) {
                            Text("Request Access", fontSize = 10.sp)
                        }
                    }
                }
            }
        }
    }
}

fun parseJsonArray(jsonStr: String?): List<String> {
    if (jsonStr.isNullOrBlank()) return emptyList()
    return try {
        val jsonArray = Json.parseToJsonElement(jsonStr).jsonArray
        jsonArray.map { it.jsonPrimitive.content }
    } catch (e: Exception) {
        emptyList()
    }
}
