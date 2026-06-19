package net.affscash.android.ui.screens.admin.support

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.AdminSupportConversationRow
import java.text.SimpleDateFormat
import java.util.Locale
import java.util.TimeZone

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminSupportScreen(
    onNavigateBack: () -> Unit,
    onNavigateToChat: (Int, Int, String) -> Unit, // convId, affId, name
    viewModel: AdminSupportViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Live Support") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    navigationIconContentColor = MaterialTheme.colorScheme.onPrimary,
                    actionIconContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            // Main Tabs
            TabRow(
                selectedTabIndex = when (uiState.selectedTab) {
                    "affiliate" -> 0
                    "advertiser" -> 1
                    else -> 2
                }
            ) {
                Tab(
                    selected = uiState.selectedTab == "affiliate",
                    onClick = { viewModel.setTab("affiliate") },
                    text = { Text("Affiliates") }
                )
                Tab(
                    selected = uiState.selectedTab == "advertiser",
                    onClick = { viewModel.setTab("advertiser") },
                    text = { Text("Advertisers") }
                )
                Tab(
                    selected = uiState.selectedTab == "manager",
                    onClick = { viewModel.setTab("manager") },
                    text = { Text("Managers") }
                )
            }

            // Sub Tabs (Filters)
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
                    .padding(8.dp),
                horizontalArrangement = Arrangement.Center
            ) {
                FilterChip(
                    selected = uiState.selectedFilter == "open",
                    onClick = { viewModel.setFilter("open") },
                    label = { Text("Open") },
                    modifier = Modifier.padding(end = 8.dp)
                )
                FilterChip(
                    selected = uiState.selectedFilter == "closed",
                    onClick = { viewModel.setFilter("closed") },
                    label = { Text("Closed") }
                )
            }

            // Error display
            if (uiState.error != null) {
                Card(
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer),
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp)
                ) {
                    Row(
                        modifier = Modifier.padding(16.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(Icons.Filled.Warning, contentDescription = null, tint = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(uiState.error ?: "", color = MaterialTheme.colorScheme.onErrorContainer)
                    }
                }
            }

            // Content
            if (uiState.isLoadingConversations && uiState.conversations.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            } else if (uiState.conversations.isEmpty()) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text("No ${uiState.selectedFilter} conversations found.", color = Color.Gray)
                }
            } else {
                LazyColumn(modifier = Modifier.fillMaxSize()) {
                    items(uiState.conversations) { conv ->
                        ConversationItem(
                            conv = conv,
                            onClick = {
                                viewModel.selectConversation(conv.conversationId, conv.affiliateId, conv.name)
                                onNavigateToChat(conv.conversationId, conv.affiliateId, conv.name)
                            }
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun ConversationItem(conv: AdminSupportConversationRow, onClick: () -> Unit) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(horizontal = 16.dp, vertical = 6.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(11.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Avatar
            Box(
                modifier = Modifier
                    .size(34.dp)
                    .clip(CircleShape)
                    .background(MaterialTheme.colorScheme.primaryContainer),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = conv.name.take(1).uppercase(),
                    color = MaterialTheme.colorScheme.onPrimaryContainer,
                    fontWeight = FontWeight.Bold,
                    fontSize = 14.sp
                )
            }

            Spacer(modifier = Modifier.width(11.dp))

            // Details
            Column(modifier = Modifier.weight(1f)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = conv.name,
                        fontWeight = FontWeight.Bold,
                        fontSize = 11.sp,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                        modifier = Modifier.weight(1f)
                    )
                    if (conv.unread > 0) {
                        Badge(
                            containerColor = MaterialTheme.colorScheme.error,
                            contentColor = MaterialTheme.colorScheme.onError
                        ) {
                            Text(text = conv.unread.toString(), fontSize = 12.sp)
                        }
                    }
                }
                Spacer(modifier = Modifier.height(3.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = conv.affiliateCode,
                        fontSize = 13.sp,
                        color = Color.Gray
                    )
                    Text(
                        text = formatDate(conv.lastMessageAt),
                        fontSize = 13.sp,
                        color = Color.Gray
                    )
                }
                Spacer(modifier = Modifier.height(3.dp))
                Text(
                    text = conv.lastMsg.orEmpty().ifEmpty { "No messages yet." },
                    fontSize = 10.sp,
                    color = Color.DarkGray,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }
        }
    }
}

private fun formatDate(dateStr: String?): String {
    if (dateStr.isNullOrEmpty()) return ""
    return try {
        val format = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
        format.timeZone = TimeZone.getTimeZone("UTC")
        val date = format.parse(dateStr)
        val outFormat = SimpleDateFormat("MMM dd, yyyy", Locale.getDefault())
        outFormat.timeZone = TimeZone.getDefault()
        date?.let { outFormat.format(it) } ?: ""
    } catch (e: Exception) {
        ""
    }
}
