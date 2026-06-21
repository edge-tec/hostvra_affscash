package net.affscash.android.ui.screens.admin.smartlinks

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import net.affscash.android.data.model.AdminAvailableOffer
import net.affscash.android.data.model.AdminSmartlinkOffer

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminSmartlinkFormScreen(
    viewModel: AdminSmartlinkFormViewModel,
    navController: NavController,
    smartlinkId: Int?
) {
    val uiState by viewModel.uiState.collectAsState()

    LaunchedEffect(smartlinkId) {
        viewModel.loadForm(smartlinkId)
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (smartlinkId == null || smartlinkId == 0) "Create Smartlink" else "Edit Smartlink") },
                navigationIcon = {
                    IconButton(onClick = { navController.navigateUp() }) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { padding ->
        Box(modifier = Modifier.padding(padding).fillMaxSize()) {
            when (val state = uiState) {
                is AdminSmartlinkFormUiState.Loading -> {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
                is AdminSmartlinkFormUiState.Error -> {
                    Text(
                        text = state.message,
                        color = MaterialTheme.colorScheme.error,
                        modifier = Modifier.align(Alignment.Center)
                    )
                }
                is AdminSmartlinkFormUiState.Success -> {
                    Column(
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(16.dp)
                            .verticalScroll(rememberScrollState()),
                        verticalArrangement = Arrangement.spacedBy(16.dp)
                    ) {
                        OutlinedTextField(
                            value = state.name,
                            onValueChange = { viewModel.updateField(name = it) },
                            label = { Text("Smartlink Name") },
                            modifier = Modifier.fillMaxWidth(),
                            singleLine = true
                        )

                        OutlinedTextField(
                            value = state.slug,
                            onValueChange = { viewModel.updateField(slug = it) },
                            label = { Text("Slug (e.g. generic-dating)") },
                            modifier = Modifier.fillMaxWidth(),
                            singleLine = true
                        )

                        var expandedRotation by remember { mutableStateOf(false) }
                        ExposedDropdownMenuBox(
                            expanded = expandedRotation,
                            onExpandedChange = { expandedRotation = !expandedRotation }
                        ) {
                            OutlinedTextField(
                                value = state.rotationType.uppercase(),
                                onValueChange = {},
                                readOnly = true,
                                label = { Text("Rotation Logic") },
                                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expandedRotation) },
                                modifier = Modifier.menuAnchor().fillMaxWidth()
                            )
                            ExposedDropdownMenu(
                                expanded = expandedRotation,
                                onDismissRequest = { expandedRotation = false }
                            ) {
                                listOf("geo", "weight", "epc", "random").forEach { type ->
                                    DropdownMenuItem(
                                        text = { Text(type.uppercase()) },
                                        onClick = {
                                            viewModel.updateField(rotationType = type)
                                            expandedRotation = false
                                        }
                                    )
                                }
                            }
                        }

                        OutlinedTextField(
                            value = state.description,
                            onValueChange = { viewModel.updateField(description = it) },
                            label = { Text("Internal Description") },
                            modifier = Modifier.fillMaxWidth(),
                            minLines = 3
                        )

                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Checkbox(
                                checked = state.requireApproval,
                                onCheckedChange = { viewModel.updateField(requireApproval = it) }
                            )
                            Text("Require Approval (Affiliates must request access)")
                        }

                        Text("Offers & Custom URLs", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)

                        state.offers.forEachIndexed { index, offer ->
                            AdminSmartlinkOfferRow(
                                offer = offer,
                                availableOffers = state.availableOffers,
                                onUpdate = { viewModel.updateOffer(index, it) },
                                onRemove = { viewModel.removeOfferRow(index) }
                            )
                        }

                        Button(
                            onClick = { viewModel.addOfferRow() },
                            modifier = Modifier.fillMaxWidth(),
                            colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.secondary)
                        ) {
                            Icon(Icons.Default.Add, contentDescription = "Add")
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Add Offer/URL Entry")
                        }

                        if (state.saveError != null) {
                            Text(text = state.saveError, color = MaterialTheme.colorScheme.error)
                        }

                        Button(
                            onClick = { viewModel.save(onSuccess = { navController.navigateUp() }) },
                            modifier = Modifier.fillMaxWidth(),
                            enabled = !state.isSaving
                        ) {
                            if (state.isSaving) {
                                CircularProgressIndicator(modifier = Modifier.size(24.dp), color = Color.White)
                            } else {
                                Text("Save Smartlink")
                            }
                        }
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminSmartlinkOfferRow(
    offer: AdminSmartlinkOffer,
    availableOffers: List<AdminAvailableOffer>,
    onUpdate: (AdminSmartlinkOffer) -> Unit,
    onRemove: () -> Unit
) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text("Offer Entry", fontWeight = FontWeight.Bold)
                IconButton(onClick = onRemove) {
                    Icon(Icons.Default.Delete, contentDescription = "Remove", tint = MaterialTheme.colorScheme.error)
                }
            }

            var expandedOffer by remember { mutableStateOf(false) }
            ExposedDropdownMenuBox(
                expanded = expandedOffer,
                onExpandedChange = { expandedOffer = !expandedOffer }
            ) {
                val selectedName = availableOffers.find { it.id == offer.offerId }?.name ?: "Custom URL / Auto-Select"
                OutlinedTextField(
                    value = selectedName,
                    onValueChange = {},
                    readOnly = true,
                    label = { Text("Select Offer") },
                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expandedOffer) },
                    modifier = Modifier.menuAnchor().fillMaxWidth()
                )
                ExposedDropdownMenu(
                    expanded = expandedOffer,
                    onDismissRequest = { expandedOffer = false }
                ) {
                    DropdownMenuItem(
                        text = { Text("Custom URL (No specific offer)") },
                        onClick = {
                            onUpdate(offer.copy(offerId = null))
                            expandedOffer = false
                        }
                    )
                    availableOffers.forEach { av ->
                        DropdownMenuItem(
                            text = { Text("[#${av.id}] ${av.name}") },
                            onClick = {
                                onUpdate(offer.copy(offerId = av.id))
                                expandedOffer = false
                            }
                        )
                    }
                }
            }

            if (offer.offerId == null) {
                OutlinedTextField(
                    value = offer.directUrl ?: "",
                    onValueChange = { onUpdate(offer.copy(directUrl = it)) },
                    label = { Text("Direct URL (if no offer selected)") },
                    modifier = Modifier.fillMaxWidth()
                )
            }

            OutlinedTextField(
                value = offer.weight.toString(),
                onValueChange = { onUpdate(offer.copy(weight = it.toIntOrNull() ?: 10)) },
                label = { Text("Weight (1-100)") },
                modifier = Modifier.fillMaxWidth()
            )

            // Basic inputs for Geolocation (comma separated strings)
            val geoStr = offer.geoRules.joinToString(",")
            OutlinedTextField(
                value = geoStr,
                onValueChange = { 
                    val newGeos = it.split(",").map { s -> s.trim().uppercase() }.filter { s -> s.length == 2 }
                    onUpdate(offer.copy(geoRules = newGeos))
                },
                label = { Text("Geo Rules (Comma separated, e.g. US,UK)") },
                modifier = Modifier.fillMaxWidth()
            )

            // Payout type
            var expandedPayout by remember { mutableStateOf(false) }
            ExposedDropdownMenuBox(
                expanded = expandedPayout,
                onExpandedChange = { expandedPayout = !expandedPayout }
            ) {
                OutlinedTextField(
                    value = offer.payoutType.uppercase(),
                    onValueChange = {},
                    readOnly = true,
                    label = { Text("Payout Type") },
                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expandedPayout) },
                    modifier = Modifier.menuAnchor().fillMaxWidth()
                )
                ExposedDropdownMenu(
                    expanded = expandedPayout,
                    onDismissRequest = { expandedPayout = false }
                ) {
                    listOf("default", "fixed", "skip", "percent").forEach { type ->
                        DropdownMenuItem(
                            text = { Text(type.uppercase()) },
                            onClick = {
                                onUpdate(offer.copy(payoutType = type))
                                expandedPayout = false
                            }
                        )
                    }
                }
            }

            if (offer.payoutType == "fixed" || offer.payoutType == "percent") {
                OutlinedTextField(
                    value = offer.payoutValue?.toString() ?: "",
                    onValueChange = { onUpdate(offer.copy(payoutValue = it.toDoubleOrNull())) },
                    label = { Text(if (offer.payoutType == "percent") "Percentage Value" else "Fixed Amount ($)") },
                    modifier = Modifier.fillMaxWidth()
                )
            }
        }
    }
}
