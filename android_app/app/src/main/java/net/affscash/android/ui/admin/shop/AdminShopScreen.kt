package net.affscash.android.ui.admin.shop

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import coil.compose.AsyncImage
import net.affscash.android.data.model.AdminShopProduct
import net.affscash.android.data.model.AdminShopOrder

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminShopScreen(
    viewModel: AdminShopViewModel = hiltViewModel(),
    onNavigateToCreateProduct: () -> Unit,
    onNavigateToEditProduct: (Int) -> Unit,
    onNavigateToOrders: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val actionMessage by viewModel.actionMessage.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(Unit) {
        viewModel.loadDashboard()
    }

    LaunchedEffect(actionMessage) {
        actionMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearActionMessage()
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        floatingActionButton = {
            FloatingActionButton(onClick = onNavigateToCreateProduct) {
                Icon(Icons.Default.Add, contentDescription = "Add Product")
            }
        }
    ) { padding ->
        Box(modifier = Modifier.fillMaxSize().padding(padding)) {
            when (val state = uiState) {
                is AdminShopUiState.Loading -> {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
                is AdminShopUiState.Error -> {
                    Text(
                        text = state.message,
                        color = MaterialTheme.colorScheme.error,
                        modifier = Modifier.align(Alignment.Center)
                    )
                }
                is AdminShopUiState.Success -> {
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp)
                    ) {
                        item {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text("Recent Orders", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                                TextButton(onClick = onNavigateToOrders) {
                                    Text("View all ->")
                                }
                            }
                            Spacer(modifier = Modifier.height(8.dp))
                        }
                        
                        if (state.data.recentOrders.isEmpty()) {
                            item {
                                Text("No recent orders", modifier = Modifier.padding(bottom = 16.dp))
                            }
                        } else {
                            items(state.data.recentOrders) { order ->
                                AdminShopOrderSummaryCard(order)
                                Spacer(modifier = Modifier.height(8.dp))
                            }
                            item { Spacer(modifier = Modifier.height(8.dp)) }
                        }

                        item {
                            Text("Products", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                            Spacer(modifier = Modifier.height(8.dp))
                        }

                        if (state.data.products.isEmpty()) {
                            item {
                                Text("No products found")
                            }
                        } else {
                            items(state.data.products) { product ->
                                AdminShopProductCard(
                                    product = product,
                                    onEdit = { onNavigateToEditProduct(product.id) },
                                    onDelete = { viewModel.deleteProduct(product.id) }
                                )
                                Spacer(modifier = Modifier.height(8.dp))
                            }
                        }
                        
                        item { Spacer(modifier = Modifier.height(80.dp)) } // Space for FAB
                    }
                }
            }
        }
    }
}

@Composable
fun AdminShopOrderSummaryCard(order: AdminShopOrder) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Row(
            modifier = Modifier.padding(12.dp).fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Column {
                Text(order.productName ?: "Unknown Product", fontWeight = FontWeight.Bold)
                Text("Affiliate: ${order.affiliateName}", style = MaterialTheme.typography.bodySmall)
                Text(order.createdAt ?: "", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            Column(horizontalAlignment = Alignment.End) {
                Text("${order.pointsSpent} PTS", color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Bold)
                Surface(
                    color = if (order.status == "pending") MaterialTheme.colorScheme.errorContainer else MaterialTheme.colorScheme.primaryContainer,
                    shape = MaterialTheme.shapes.small
                ) {
                    Text(
                        text = order.status.uppercase(),
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp),
                        style = MaterialTheme.typography.labelSmall,
                        color = if (order.status == "pending") MaterialTheme.colorScheme.onErrorContainer else MaterialTheme.colorScheme.onPrimaryContainer
                    )
                }
            }
        }
    }
}

@Composable
fun AdminShopProductCard(
    product: AdminShopProduct,
    onEdit: () -> Unit,
    onDelete: () -> Unit
) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Row(
            modifier = Modifier.padding(12.dp).fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Row(modifier = Modifier.weight(1f), verticalAlignment = Alignment.CenterVertically) {
                product.imagePath?.trim()?.takeIf { it.isNotEmpty() }?.let { rawPath ->
                    val imageUrl = (if (!rawPath.startsWith("http")) {
                        "https://affscash.net/" + rawPath.removePrefix("/")
                    } else {
                        rawPath
                    }).replace(" ", "%20")
                    AsyncImage(
                        model = coil.request.ImageRequest.Builder(androidx.compose.ui.platform.LocalContext.current)
                            .data(imageUrl)
                            .addHeader("User-Agent", "Mozilla/5.0 (Linux; Android 13; Mobile) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Mobile Safari/537.36")
                            .addHeader("Accept", "image/webp,image/apng,image/*,*/*;q=0.8")
                            .addHeader("Referer", "https://affscash.net/")
                            .crossfade(true)
                            .build(),
                        contentDescription = "Product Image",
                        contentScale = androidx.compose.ui.layout.ContentScale.Crop,
                        modifier = Modifier.size(50.dp)
                    )
                    Spacer(modifier = Modifier.width(16.dp))
                }
                Column {
                    Text(product.name, fontWeight = FontWeight.Bold)
                    Text("${product.pricePoints} PTS", color = MaterialTheme.colorScheme.primary)
                    Text("Stock: ${product.stock ?: "∞"}", style = MaterialTheme.typography.bodySmall)
                }
            }
            Column(horizontalAlignment = Alignment.End) {
                Row {
                    TextButton(onClick = onEdit) { Text("Edit") }
                    TextButton(onClick = onDelete, colors = ButtonDefaults.textButtonColors(contentColor = MaterialTheme.colorScheme.error)) { Text("Delete") }
                }
                Surface(
                    color = if (product.status == "active") MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.errorContainer,
                    shape = MaterialTheme.shapes.small
                ) {
                    Text(
                        text = product.status.uppercase(),
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp),
                        style = MaterialTheme.typography.labelSmall,
                        color = if (product.status == "active") MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onErrorContainer
                    )
                }
            }
        }
    }
}
