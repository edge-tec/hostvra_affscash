package net.affscash.android.ui.admin

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.AdvertiserOption

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminOfferFormScreen(
    offerId: Int?,
    isInHouse: Boolean = false,
    onNavigateBack: () -> Unit,
    viewModel: AdminOfferFormViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val formData by viewModel.formData.collectAsState()
    val advertisers by viewModel.advertisers.collectAsState()

    LaunchedEffect(offerId) {
        viewModel.loadInitialData(offerId)
        if (offerId == null) {
            viewModel.updateFormData { copy(isInHouse = isInHouse) }
        }
    }

    LaunchedEffect(uiState) {
        if (uiState is AdminOfferFormUiState.Success) {
            onNavigateBack()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (offerId == null) "Create Offer" else "Edit Offer") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { paddingValues ->
        Box(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            when (uiState) {
                is AdminOfferFormUiState.Loading -> {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
                else -> {
                    Column(
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(16.dp)
                            .verticalScroll(rememberScrollState()),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        if (uiState is AdminOfferFormUiState.Error) {
                            Text(
                                text = (uiState as AdminOfferFormUiState.Error).message,
                                color = MaterialTheme.colorScheme.error
                            )
                        }

                        OutlinedTextField(
                            value = formData.name,
                            onValueChange = { viewModel.updateFormData { copy(name = it) } },
                            label = { Text("Offer Name *") },
                            modifier = Modifier.fillMaxWidth()
                        )

                        // Advertiser Dropdown (only if not inHouse)
                        if (!formData.isInHouse) {
                            var advExpanded by remember { mutableStateOf(false) }
                            ExposedDropdownMenuBox(
                                expanded = advExpanded,
                                onExpandedChange = { advExpanded = !advExpanded }
                            ) {
                                val selectedAdv = advertisers.find { it.id.toString() == formData.advertiserId }
                                OutlinedTextField(
                                    value = selectedAdv?.label ?: "Select Advertiser *",
                                    onValueChange = {},
                                    readOnly = true,
                                    label = { Text("Advertiser") },
                                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = advExpanded) },
                                    modifier = Modifier.menuAnchor().fillMaxWidth()
                                )
                                ExposedDropdownMenu(
                                    expanded = advExpanded,
                                    onDismissRequest = { advExpanded = false }
                                ) {
                                    advertisers.forEach { adv ->
                                        DropdownMenuItem(
                                            text = { Text(adv.label) },
                                            onClick = {
                                                viewModel.updateFormData { copy(advertiserId = adv.id.toString()) }
                                                advExpanded = false
                                            }
                                        )
                                    }
                                }
                            }
                        }

                        OutlinedTextField(
                            value = formData.offerUrl,
                            onValueChange = { viewModel.updateFormData { copy(offerUrl = it) } },
                            label = { Text("Offer/Tracking URL *") },
                            modifier = Modifier.fillMaxWidth()
                        )

                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedTextField(
                                value = formData.payoutType,
                                onValueChange = { viewModel.updateFormData { copy(payoutType = it) } },
                                label = { Text("Payout Type") },
                                modifier = Modifier.weight(1f)
                            )
                            OutlinedTextField(
                                value = formData.status,
                                onValueChange = { viewModel.updateFormData { copy(status = it) } },
                                label = { Text("Status") },
                                modifier = Modifier.weight(1f)
                            )
                        }

                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedTextField(
                                value = formData.payout,
                                onValueChange = { viewModel.updateFormData { copy(payout = it) } },
                                label = { Text("Payout ($) *") },
                                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                                modifier = Modifier.weight(1f)
                            )
                            OutlinedTextField(
                                value = formData.revenue,
                                onValueChange = { viewModel.updateFormData { copy(revenue = it) } },
                                label = { Text("Revenue ($)") },
                                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                                modifier = Modifier.weight(1f)
                            )
                        }

                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedTextField(
                                value = formData.category,
                                onValueChange = { viewModel.updateFormData { copy(category = it) } },
                                label = { Text("Category") },
                                modifier = Modifier.weight(1f)
                            )
                            OutlinedTextField(
                                value = formData.visibility,
                                onValueChange = { viewModel.updateFormData { copy(visibility = it) } },
                                label = { Text("Visibility") },
                                modifier = Modifier.weight(1f)
                            )
                        }

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Checkbox(
                                checked = formData.requireApproval,
                                onCheckedChange = { viewModel.updateFormData { copy(requireApproval = it) } }
                            )
                            Text("Requires Approval to Run")
                        }

                        OutlinedTextField(
                            value = formData.geoTargeting,
                            onValueChange = { viewModel.updateFormData { copy(geoTargeting = it) } },
                            label = { Text("GEO Targeting (comma separated e.g. US, UK)") },
                            modifier = Modifier.fillMaxWidth()
                        )

                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedTextField(
                                value = formData.dailyCap,
                                onValueChange = { viewModel.updateFormData { copy(dailyCap = it) } },
                                label = { Text("Daily Cap") },
                                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                                modifier = Modifier.weight(1f)
                            )
                            OutlinedTextField(
                                value = formData.totalCap,
                                onValueChange = { viewModel.updateFormData { copy(totalCap = it) } },
                                label = { Text("Total Cap") },
                                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                                modifier = Modifier.weight(1f)
                            )
                        }

                        OutlinedTextField(
                            value = formData.description,
                            onValueChange = { viewModel.updateFormData { copy(description = it) } },
                            label = { Text("Description") },
                            modifier = Modifier.fillMaxWidth(),
                            minLines = 3
                        )

                        Spacer(modifier = Modifier.height(8.dp))

                        Button(
                            onClick = { viewModel.submit(offerId) },
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text(if (offerId == null) "Create Offer" else "Save Changes")
                        }
                    }
                }
            }
        }
    }
}
