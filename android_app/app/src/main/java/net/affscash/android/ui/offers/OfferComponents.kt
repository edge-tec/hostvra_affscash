package net.affscash.android.ui.offers

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.clickable
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
import net.affscash.android.ui.dashboard.PremiumUI
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
            modifier = Modifier.menuAnchor().fillMaxWidth(),
            shape = RoundedCornerShape(10.dp),
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
                    text = { Text(option, fontSize = 13.sp) },
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
        shape = PremiumUI.CardShape,
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Box(modifier = Modifier.background(PremiumUI.CardGradient).fillMaxWidth()) {
            Column(modifier = Modifier.padding(12.dp)) {
                // Header row: ID + Payout
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.Top) {
                    Column(modifier = Modifier.weight(1f)) {
                        Text(
                            text = "OFF-${offer.id.toString().padStart(4, '0')}",
                            style = PremiumUI.LabelSmall,
                            color = MaterialTheme.colorScheme.primary,
                            fontWeight = FontWeight.Bold
                        )
                        Spacer(modifier = Modifier.height(2.dp))
                        Text(
                            text = offer.name,
                            style = PremiumUI.DataBold,
                            fontSize = 14.sp,
                            maxLines = 2,
                            overflow = TextOverflow.Ellipsis
                        )

                        // Tags
                        Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(top = 4.dp), horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                            offer.category?.let {
                                Surface(color = MaterialTheme.colorScheme.surfaceVariant, shape = RoundedCornerShape(4.dp)) {
                                    Text(it, fontSize = 10.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.padding(horizontal = 5.dp, vertical = 1.dp))
                                }
                            }

                            Surface(color = MaterialTheme.colorScheme.secondaryContainer, shape = RoundedCornerShape(4.dp)) {
                                Text(offer.payoutType.uppercase(), color = MaterialTheme.colorScheme.onSecondaryContainer, fontSize = 10.sp, fontWeight = FontWeight.Medium, modifier = Modifier.padding(horizontal = 5.dp, vertical = 1.dp))
                            }

                            if (offer.offerType != null) {
                                Surface(color = MaterialTheme.colorScheme.tertiaryContainer, shape = RoundedCornerShape(4.dp)) {
                                    Text(offer.offerType.uppercase(), color = MaterialTheme.colorScheme.onTertiaryContainer, fontSize = 10.sp, fontWeight = FontWeight.Medium, modifier = Modifier.padding(horizontal = 5.dp, vertical = 1.dp))
                                }
                            }
                        }
                    }

                    Column(horizontalAlignment = Alignment.End) {
                        Text(
                            text = "$${String.format("%.2f", offer.payout)}",
                            fontSize = 16.sp,
                            color = PremiumUI.StatusApproved,
                            fontWeight = FontWeight.Bold
                        )
                    }
                }

                // Description
                offer.description?.let { desc ->
                    Spacer(modifier = Modifier.height(6.dp))
                    var isDescExpanded by remember { mutableStateOf(false) }
                    var showSeeMore by remember { mutableStateOf(false) }

                    Column(modifier = Modifier.fillMaxWidth()) {
                        Text(
                            text = desc,
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            lineHeight = 16.sp,
                            maxLines = if (isDescExpanded) Int.MAX_VALUE else 2,
                            overflow = TextOverflow.Ellipsis,
                            onTextLayout = { textLayoutResult ->
                                if (textLayoutResult.hasVisualOverflow) {
                                    showSeeMore = true
                                }
                            }
                        )
                        if (showSeeMore || isDescExpanded) {
                            Text(
                                text = if (isDescExpanded) "See less" else "See more",
                                color = MaterialTheme.colorScheme.primary,
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier
                                    .padding(top = 2.dp)
                                    .clickable { isDescExpanded = !isDescExpanded }
                            )
                        }
                    }
                }

                Spacer(modifier = Modifier.height(8.dp))
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
                Spacer(modifier = Modifier.height(8.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    // Geo and Devices
                    Column {
                        var isGeoExpanded by remember { mutableStateOf(false) }
                        Row(
                            verticalAlignment = if (isGeoExpanded) Alignment.Top else Alignment.CenterVertically,
                            modifier = Modifier.clickable { isGeoExpanded = !isGeoExpanded }
                        ) {
                            Icon(Icons.Default.Public, contentDescription = "GEO", modifier = Modifier.size(13.dp).padding(top = if (isGeoExpanded) 2.dp else 0.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                            Spacer(modifier = Modifier.width(4.dp))
                            val geos = parseJsonArray(offer.countries)
                            if (geos.isEmpty()) {
                                Text("Global", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            } else {
                                val displayText = if (isGeoExpanded) geos.joinToString(", ") else (geos.take(3).joinToString(", ") + if (geos.size > 3) " +${geos.size-3}" else "")
                                Text(displayText, fontSize = 11.sp, modifier = Modifier.weight(1f, fill = false), color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                        }
                        Spacer(modifier = Modifier.height(4.dp))
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.Devices, contentDescription = "Devices", modifier = Modifier.size(13.dp), tint = MaterialTheme.colorScheme.onSurfaceVariant)
                            Spacer(modifier = Modifier.width(4.dp))
                            val devs = parseJsonArray(offer.devices)
                            if (devs.isEmpty()) Text("All Devices", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            else Text(devs.joinToString(", "), fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
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
                            "approved" -> PremiumUI.StatusApproved
                            "pending" -> PremiumUI.StatusPending
                            "rejected" -> PremiumUI.StatusRejected
                            else -> MaterialTheme.colorScheme.onSurfaceVariant
                        }
                        val statusBgColor = when(offer.accessStatus) {
                            "approved" -> PremiumUI.StatusApprovedBg
                            "pending" -> PremiumUI.StatusPendingBg
                            "rejected" -> PremiumUI.StatusRejectedBg
                            else -> Color(0xFFF3F4F6)
                        }

                        Surface(
                            color = statusBgColor,
                            shape = RoundedCornerShape(6.dp)
                        ) {
                            Text(statusText, fontSize = 10.sp, fontWeight = FontWeight.Bold, color = statusColor, modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp))
                        }

                        Spacer(modifier = Modifier.height(6.dp))

                        if (offer.accessStatus == "approved") {
                            Button(
                                onClick = onClick,
                                enabled = !isLoadingLink,
                                colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary),
                                contentPadding = PaddingValues(horizontal = 14.dp, vertical = 6.dp),
                                shape = RoundedCornerShape(8.dp)
                            ) {
                                if (isLoadingLink) {
                                    CircularProgressIndicator(modifier = Modifier.size(14.dp), color = MaterialTheme.colorScheme.onPrimary, strokeWidth = 2.dp)
                                } else {
                                    Text("Get Link", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                                }
                            }
                        } else if (offer.accessStatus == null || offer.accessStatus == "removed") {
                            OutlinedButton(
                                onClick = onApplyClick,
                                contentPadding = PaddingValues(horizontal = 14.dp, vertical = 6.dp),
                                shape = RoundedCornerShape(8.dp)
                            ) {
                                Text("Request Access", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                            }
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
