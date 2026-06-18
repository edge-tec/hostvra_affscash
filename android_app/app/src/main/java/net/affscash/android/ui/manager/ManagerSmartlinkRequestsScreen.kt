package net.affscash.android.ui.manager

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Email
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.window.Dialog
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.ManagerSmartlinkRequest

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerSmartlinkRequestsScreen(
    onNavigateBack: () -> Unit,
    viewModel: ManagerSmartlinksViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    // Trigger load when screen opens
    LaunchedEffect(Unit) {
        viewModel.loadRequests()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Smartlink Requests") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
        ) {
            // Filters
            ScrollableTabRow(
                selectedTabIndex = listOf("all", "pending", "approved", "rejected").indexOf(uiState.requestFilter),
                edgePadding = 16.dp,
                modifier = Modifier.fillMaxWidth()
            ) {
                val tabs = listOf(
                    "all" to "All",
                    "pending" to "Pending",
                    "approved" to "Approved",
                    "rejected" to "Rejected"
                )
                tabs.forEachIndexed { index, (key, label) ->
                    Tab(
                        selected = uiState.requestFilter == key,
                        onClick = { viewModel.setRequestFilter(key) },
                        text = { Text(label) }
                    )
                }
            }

            if (uiState.isRequestsLoading && uiState.requests.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            } else if (uiState.requestError != null && uiState.requests.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(text = uiState.requestError ?: "Unknown error", color = MaterialTheme.colorScheme.error)
                }
            } else if (uiState.requests.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text("No requests found.", color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            } else {
                LazyColumn(
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp),
                    modifier = Modifier.fillMaxSize()
                ) {
                    items(uiState.requests) { request ->
                        ManagerSmartlinkRequestCard(
                            request = request,
                            onReview = { decision, note ->
                                viewModel.reviewRequest(request.id, decision, note)
                            }
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun ManagerSmartlinkRequestCard(
    request: ManagerSmartlinkRequest,
    onReview: (String, String?) -> Unit
) {
    var showReviewDialog by remember { mutableStateOf(false) }

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            // Header
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = request.createdAt,
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                val (bgColor, textColor) = when (request.status.lowercase()) {
                    "pending" -> Color(0xFFFFF3E0) to Color(0xFFEF6C00)
                    "approved" -> Color(0xFFE8F5E9) to Color(0xFF2E7D32)
                    "rejected" -> Color(0xFFFFEBEE) to Color(0xFFC62828)
                    else -> MaterialTheme.colorScheme.surfaceVariant to MaterialTheme.colorScheme.onSurfaceVariant
                }
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(8.dp))
                        .background(bgColor)
                        .padding(horizontal = 8.dp, vertical = 4.dp)
                ) {
                    Text(
                        text = request.status.uppercase(),
                        style = MaterialTheme.typography.labelSmall,
                        color = textColor,
                        fontWeight = FontWeight.Bold
                    )
                }
            }
            
            Spacer(modifier = Modifier.height(8.dp))

            Text(
                text = request.smartlinkName,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )
            
            Spacer(modifier = Modifier.height(12.dp))
            
            // Affiliate Info
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    imageVector = Icons.Default.Person,
                    contentDescription = null,
                    modifier = Modifier.size(16.dp),
                    tint = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Spacer(modifier = Modifier.width(4.dp))
                Text(
                    text = "${request.affName} (${request.affiliateCode})",
                    style = MaterialTheme.typography.bodyMedium
                )
            }
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(top = 4.dp)) {
                Icon(
                    imageVector = Icons.Default.Email,
                    contentDescription = null,
                    modifier = Modifier.size(16.dp),
                    tint = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Spacer(modifier = Modifier.width(4.dp))
                Text(
                    text = request.affEmail,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.primary
                )
            }

            if (!request.promotionDescription.isNullOrEmpty()) {
                Spacer(modifier = Modifier.height(12.dp))
                Text("Promotion Strategy:", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Text(
                    text = request.promotionDescription,
                    style = MaterialTheme.typography.bodySmall,
                    modifier = Modifier.padding(top = 4.dp)
                )
            }

            if (!request.adminNote.isNullOrEmpty()) {
                Spacer(modifier = Modifier.height(12.dp))
                Text("Manager Note:", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.error)
                Text(
                    text = request.adminNote,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.error,
                    modifier = Modifier.padding(top = 4.dp)
                )
            }

            if (request.status.lowercase() == "pending") {
                Spacer(modifier = Modifier.height(16.dp))
                Button(
                    onClick = { showReviewDialog = true },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(8.dp)
                ) {
                    Text("Review Request")
                }
            }
        }
    }

    if (showReviewDialog) {
        var note by remember { mutableStateOf("") }
        
        AlertDialog(
            onDismissRequest = { showReviewDialog = false },
            title = { Text("Review Request") },
            text = {
                Column {
                    Text("You are reviewing the request for ${request.smartlinkName} by ${request.affName}.")
                    Spacer(modifier = Modifier.height(16.dp))
                    OutlinedTextField(
                        value = note,
                        onValueChange = { note = it },
                        label = { Text("Manager Note (Optional)") },
                        modifier = Modifier.fillMaxWidth(),
                        minLines = 3
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        onReview("approved", note)
                        showReviewDialog = false
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4CAF50))
                ) {
                    Text("Approve")
                }
            },
            dismissButton = {
                OutlinedButton(
                    onClick = {
                        onReview("rejected", note)
                        showReviewDialog = false
                    },
                    colors = ButtonDefaults.outlinedButtonColors(contentColor = MaterialTheme.colorScheme.error)
                ) {
                    Text("Reject")
                }
            }
        )
    }
}
