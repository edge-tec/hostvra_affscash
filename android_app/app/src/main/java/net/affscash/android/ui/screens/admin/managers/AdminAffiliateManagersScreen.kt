package net.affscash.android.ui.screens.admin.managers

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import net.affscash.android.data.model.AdminAffiliateManagerRow

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminAffiliateManagersScreen(
    viewModel: AdminAffiliateManagersViewModel = viewModel(),
    onNavigateBack: () -> Unit,
    onLoginSuccess: (String) -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }
    val context = androidx.compose.ui.platform.LocalContext.current
    val userManager = remember { net.affscash.android.data.local.UserManager(context) }

    LaunchedEffect(uiState.error) {
        uiState.error?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearError()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Affiliate Managers") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, "Back")
                    }
                }
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { padding ->
        Box(modifier = Modifier.fillMaxSize().padding(padding)) {
            if (uiState.isLoading) {
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            }

            LazyColumn(contentPadding = PaddingValues(8.dp)) {
                items(uiState.managers) { manager ->
                    AdminAffiliateManagerCard(
                        manager = manager,
                        onDelete = { viewModel.deleteManager(manager.mgr_id) },
                        onLoginAs = { 
                            viewModel.impersonateManager(manager.user_id) { role, user ->
                                userManager.saveUser(role, user.email, "${user.firstName} ${user.lastName}")
                                userManager.saveIsImpersonating(true)
                                onLoginSuccess(role)
                            }
                        }
                    )
                }
            }
        }
    }
}

@OptIn(ExperimentalLayoutApi::class)
@Composable
fun AdminAffiliateManagerCard(
    manager: AdminAffiliateManagerRow,
    onDelete: () -> Unit,
    onLoginAs: () -> Unit
) {
    var menuExpanded by remember { mutableStateOf(false) }

    val statusColor = when (manager.status.lowercase()) {
        "active" -> Color(0xFF388E3C)
        "pending" -> Color(0xFFF57C00)
        "suspended" -> Color.Red
        else -> Color.Gray
    }

    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp)) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Column {
                    Text(manager.name, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                    Text(manager.email, style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                }
                Box {
                    IconButton(onClick = { menuExpanded = true }) {
                        Icon(Icons.Default.MoreVert, "More Options")
                    }
                    DropdownMenu(expanded = menuExpanded, onDismissRequest = { menuExpanded = false }) {
                        DropdownMenuItem(
                            text = { Text("Login", color = Color(0xFF1976D2)) }, 
                            onClick = { menuExpanded = false; onLoginAs() }
                        )
                        DropdownMenuItem(
                            text = { Text("Delete", color = Color.Red) }, 
                            onClick = { menuExpanded = false; onDelete() }
                        )
                    }
                }
            }
            
            Spacer(Modifier.height(8.dp))
            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Badge(containerColor = statusColor) {
                    Text(manager.status.uppercase(), color = Color.White, modifier = Modifier.padding(horizontal = 4.dp))
                }
                Text("${manager.aff_count} Affiliates", style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
            }
            
            Spacer(Modifier.height(8.dp))
            Divider()
            Spacer(Modifier.height(8.dp))
            
            Text("Permissions:", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
            FlowRow(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(4.dp),
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                manager.permissions.forEach { perm ->
                    Surface(
                        color = Color(0xFFE3F2FD),
                        shape = MaterialTheme.shapes.small
                    ) {
                        Text(
                            text = perm.replace("_", " ").uppercase(),
                            color = Color(0xFF1976D2),
                            style = MaterialTheme.typography.labelSmall,
                            modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                        )
                    }
                }
            }
            
            Spacer(Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Created: ${manager.created_at.take(10)}", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                Text("Balance: $${(( manager.balance )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold)
            }
        }
    }
}
