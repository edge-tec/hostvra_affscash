package net.affscash.android.ui.admin.shop

import android.net.Uri
import net.affscash.android.Config
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import coil.compose.AsyncImage
import net.affscash.android.util.ImageUtils
import net.affscash.android.ui.dashboard.PremiumUI

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminShopProductFormScreen(
    productId: Int?,
    viewModel: AdminShopProductFormViewModel = hiltViewModel(),
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val formData by viewModel.formData.collectAsState()
    val context = LocalContext.current
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(productId) {
        viewModel.loadInitialData(productId)
    }

    LaunchedEffect(uiState) {
        when (val state = uiState) {
            is AdminShopProductFormUiState.Success -> {
                snackbarHostState.showSnackbar(state.message)
                onNavigateBack()
            }
            is AdminShopProductFormUiState.Error -> {
                snackbarHostState.showSnackbar(state.message)
            }
            else -> {}
        }
    }

    val imagePickerLauncher = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { uri: Uri? ->
        uri?.let {
            val base64 = ImageUtils.uriToBase64(context, it)
            if (base64 != null) {
                viewModel.updateFormData { copy(imageBase64 = "data:image/jpeg;base64,$base64") }
            }
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            net.affscash.android.ui.components.CompactTopBar(
                title = { Text(if (productId == null) "Create Product" else "Edit Product") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { padding ->
        Box(modifier = Modifier.fillMaxSize().padding(padding)) {
            if (uiState is AdminShopProductFormUiState.Loading) {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            } else {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .verticalScroll(rememberScrollState())
                        .padding(8.dp)
                ) {
                    OutlinedTextField(
                        value = formData.name,
                        onValueChange = { viewModel.updateFormData { copy(name = it) } },
                        label = { Text("Product Name *") },
                        modifier = Modifier.fillMaxWidth()
                    )
                    Spacer(modifier = Modifier.height(4.dp))

                    OutlinedTextField(
                        value = formData.description,
                        onValueChange = { viewModel.updateFormData { copy(description = it) } },
                        label = { Text("Description") },
                        modifier = Modifier.fillMaxWidth(),
                        minLines = 3
                    )
                    Spacer(modifier = Modifier.height(4.dp))

                    OutlinedTextField(
                        value = formData.pricePoints,
                        onValueChange = { viewModel.updateFormData { copy(pricePoints = it) } },
                        label = { Text("Price (Points) *") },
                        modifier = Modifier.fillMaxWidth(),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number)
                    )
                    Spacer(modifier = Modifier.height(4.dp))

                    OutlinedTextField(
                        value = formData.stock,
                        onValueChange = { viewModel.updateFormData { copy(stock = it) } },
                        label = { Text("Stock (Leave empty for infinite)") },
                        modifier = Modifier.fillMaxWidth(),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number)
                    )
                    Spacer(modifier = Modifier.height(4.dp))

                    // Status Dropdown
                    var expanded by remember { mutableStateOf(false) }
                    ExposedDropdownMenuBox(
                        expanded = expanded,
                        onExpandedChange = { expanded = !expanded }
                    ) {
                        OutlinedTextField(
                            value = formData.status,
                            onValueChange = {},
                            readOnly = true,
                            label = { Text("Status") },
                            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                            colors = ExposedDropdownMenuDefaults.outlinedTextFieldColors(),
                            modifier = Modifier.fillMaxWidth().menuAnchor()
                        )
                        ExposedDropdownMenu(
                            expanded = expanded,
                            onDismissRequest = { expanded = false }
                        ) {
                            listOf("active", "inactive").forEach { status ->
                                DropdownMenuItem(
                                    text = { Text(status) },
                                    onClick = {
                                        viewModel.updateFormData { copy(status = status) }
                                        expanded = false
                                    }
                                )
                            }
                        }
                    }
                    Spacer(modifier = Modifier.height(4.dp))

                    // Image Upload
                    Text("Product Image")
                    Spacer(modifier = Modifier.height(4.dp))
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Button(onClick = { imagePickerLauncher.launch("image/*") }) {
                            Text("Select Image")
                        }
                        Spacer(modifier = Modifier.width(4.dp))
                        if (formData.imageBase64 != null) {
                            Text("Image selected", color = MaterialTheme.colorScheme.primary)
                        } else if (formData.existingImagePath != null) {
                            AsyncImage(
                                model = (Config.BASE_URL.removeSuffix("/") + "/" + (formData.existingImagePath ?: "").removePrefix("/")),
                                contentDescription = "Current Image",
                                modifier = Modifier.size(60.dp)
                            )
                        } else {
                            Text("No image selected")
                        }
                    }

                    Spacer(modifier = Modifier.height(32.dp))

                    Button(
                        onClick = { viewModel.submit(productId) },
                        modifier = Modifier.fillMaxWidth(),
                        enabled = uiState !is AdminShopProductFormUiState.Loading
                    ) {
                        Text(if (productId == null) "Create Product" else "Update Product")
                    }
                    Spacer(modifier = Modifier.height(32.dp))
                }
            }
        }
    }
}
