package net.affscash.android.ui.screens.admin.smartlinks

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Pause
import androidx.compose.material.icons.filled.PlayArrow
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.navigation.NavController
import net.affscash.android.data.model.AdminSmartlink

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminSmartlinksScreen(
    viewModel: AdminSmartlinksViewModel,
    navController: NavController
) {
    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Smartlinks") },
                actions = {
                    if (uiState is AdminSmartlinksUiState.Success) {
                        val state = uiState as AdminSmartlinksUiState.Success
                        BadgedBox(
                            badge = {
                                if (state.pendingRequestsCount > 0) {
                                    Badge { Text(state.pendingRequestsCount.toString()) }
                                }
                            },
                            modifier = Modifier.padding(end = 16.dp)
                        ) {
                            IconButton(onClick = { navController.navigate("admin_smartlink_requests") }) {
                                Icon(Icons.Default.Notifications, contentDescription = "Requests")
                            }
                        }
                    }
                }
            )
        },
        floatingActionButton = {
            FloatingActionButton(onClick = { navController.navigate("admin_smartlink_create") }) {
                Icon(Icons.Default.Add, contentDescription = "Create Smartlink")
            }
        }
    ) { padding ->
        Box(modifier = Modifier.padding(padding).fillMaxSize()) {
            when (val state = uiState) {
                is AdminSmartlinksUiState.Loading -> {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
                is AdminSmartlinksUiState.Error -> {
                    Text(
                        text = state.message,
                        color = MaterialTheme.colorScheme.error,
                        modifier = Modifier.align(Alignment.Center)
                    )
                }
                is AdminSmartlinksUiState.Success -> {
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        contentPadding = PaddingValues(16.dp),
                        verticalArrangement = Arrangement.spacedBy(16.dp)
                    ) {
                        items(state.smartlinks) { smartlink ->
                            AdminSmartlinkItem(
                                smartlink = smartlink,
                                onEdit = { navController.navigate("admin_smartlink_edit/${smartlink.id}") },
                                onToggleStatus = { viewModel.toggleStatus(smartlink.id) },
                                onDelete = { viewModel.deleteSmartlink(smartlink.id) }
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun AdminSmartlinkItem(
    smartlink: AdminSmartlink,
    onEdit: () -> Unit,
    onToggleStatus: () -> Unit,
    onDelete: () -> Unit
) {
    var showDeleteDialog by remember { mutableStateOf(false) }

    if (showDeleteDialog) {
        AlertDialog(
            onDismissRequest = { showDeleteDialog = false },
            title = { Text("Delete Smartlink") },
            text = { Text("Are you sure you want to delete this smartlink?") },
            confirmButton = {
                TextButton(onClick = {
                    showDeleteDialog = false
                    onDelete()
                }) {
                    Text("Delete", color = MaterialTheme.colorScheme.error)
                }
            },
            dismissButton = {
                TextButton(onClick = { showDeleteDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = smartlink.name,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
                Badge(
                    containerColor = if (smartlink.status == "active") Color(0xFF4CAF50) else Color.Gray,
                    contentColor = Color.White
                ) {
                    Text(text = smartlink.status.uppercase(), modifier = Modifier.padding(horizontal = 4.dp))
                }
            }

            Spacer(modifier = Modifier.height(4.dp))
            Text(text = "Slug: ${smartlink.slug}", style = MaterialTheme.typography.bodySmall)
            Text(text = "Rotation: ${smartlink.rotationType.uppercase()}", style = MaterialTheme.typography.bodySmall)

            Spacer(modifier = Modifier.height(8.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(text = "Offers: ${smartlink.offerCount ?: 0}", style = MaterialTheme.typography.bodySmall)
                Text(text = "Clicks: ${smartlink.totalClicks ?: 0}", style = MaterialTheme.typography.bodySmall)
                Text(text = "Convs: ${smartlink.totalConvs ?: 0}", style = MaterialTheme.typography.bodySmall)
            }

            Spacer(modifier = Modifier.height(12.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.End
            ) {
                IconButton(onClick = onToggleStatus) {
                    Icon(
                        imageVector = if (smartlink.status == "active") Icons.Default.Pause else Icons.Default.PlayArrow,
                        contentDescription = if (smartlink.status == "active") "Pause" else "Activate"
                    )
                }
                IconButton(onClick = onEdit) {
                    Icon(Icons.Default.Edit, contentDescription = "Edit")
                }
                IconButton(onClick = { showDeleteDialog = true }) {
                    Icon(Icons.Default.Delete, contentDescription = "Delete", tint = MaterialTheme.colorScheme.error)
                }
            }
        }
    }
}
