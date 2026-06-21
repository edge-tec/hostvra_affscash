package net.affscash.android.ui.screens.admin.support

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.LockOpen
import androidx.compose.material.icons.filled.Send
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import net.affscash.android.data.model.AdminSupportMessage
import java.text.SimpleDateFormat
import java.util.Locale
import java.util.TimeZone
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.combinedClickable
import androidx.compose.ui.draw.clip
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import coil.compose.AsyncImage
import coil.request.ImageRequest
import android.content.Intent
import android.net.Uri
import android.provider.OpenableColumns
import java.io.File
import java.io.FileOutputStream
import androidx.compose.material.icons.filled.AttachFile
import androidx.compose.material.icons.filled.Image
import androidx.compose.material.icons.filled.InsertDriveFile


@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminSupportChatScreen(
    onNavigateBack: () -> Unit,
    viewModel: AdminSupportViewModel
) {
    val uiState by viewModel.uiState.collectAsState()
    var messageText by remember { mutableStateOf("") }
    val listState = rememberLazyListState()
    val context = LocalContext.current

    val launcher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetContent()
    ) { uri: Uri? ->
        uri?.let {
            val file = getFileFromUri(context, it)
            if (file != null) {
                val mimeType = context.contentResolver.getType(it) ?: "application/octet-stream"
                viewModel.uploadAndSendMessage(file, mimeType, "")
            }
        }
    }

    val imageLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.PickVisualMedia()
    ) { uri: Uri? ->
        uri?.let {
            val file = getFileFromUri(context, it)
            if (file != null) {
                val mimeType = context.contentResolver.getType(it) ?: "image/jpeg"
                viewModel.uploadAndSendMessage(file, mimeType, "")
            }
        }
    }

    // Scroll to bottom when new messages arrive
    LaunchedEffect(uiState.messages.size) {
        if (uiState.messages.isNotEmpty()) {
            listState.animateScrollToItem(uiState.messages.size - 1)
        }
    }

    val isClosed = uiState.selectedFilter == "closed"

    Scaffold(
        topBar = {
            net.affscash.android.ui.components.CompactTopBar(
                title = {
                    Column {
                        Text(uiState.selectedName, maxLines = 1, fontSize = 18.sp)
                        Text(if (isClosed) "Closed" else "Active", fontSize = 12.sp, color = Color.LightGray)
                    }
                },
                navigationIcon = {
                    IconButton(onClick = {
                        viewModel.clearSelection()
                        onNavigateBack()
                    }) {
                        Icon(Icons.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    if (isClosed) {
                        IconButton(onClick = { viewModel.reopenConversation() }) {
                            Icon(Icons.Filled.LockOpen, contentDescription = "Reopen")
                        }
                    } else {
                        IconButton(onClick = { viewModel.closeConversation() }) {
                            Icon(Icons.Filled.Lock, contentDescription = "Close")
                        }
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
                .imePadding()
        ) {
            // Error display
            if (uiState.error != null) {
                Card(
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer),
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(8.dp)
                ) {
                    Row(
                        modifier = Modifier.padding(8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(Icons.Filled.Warning, contentDescription = null, tint = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(uiState.error ?: "", color = MaterialTheme.colorScheme.onErrorContainer)
                        Spacer(modifier = Modifier.weight(1f))
                        TextButton(onClick = { viewModel.clearError() }) {
                            Text("Dismiss")
                        }
                    }
                }
            }

            // Message List
            Box(modifier = Modifier.weight(1f)) {
                if (uiState.isLoadingMessages && uiState.messages.isEmpty()) {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                } else {
                    LazyColumn(
                        state = listState,
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(horizontal = 8.dp),
                        contentPadding = PaddingValues(vertical = 8.dp)
                    ) {
                        items(uiState.messages) { message ->
                            MessageBubble(
                                message = message,
                                onDelete = { viewModel.deleteMessage(message.id) },
                                onEdit = { newText -> viewModel.editMessage(message.id, newText) }
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                        }
                    }
                }
            }

            // Input Area
            if (!isClosed) {
                Surface(
                    color = MaterialTheme.colorScheme.surface,
                    shadowElevation = 8.dp
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 8.dp, vertical = 12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        IconButton(
                            onClick = { launcher.launch("*/*") },
                            enabled = !uiState.isSendingMessage && !uiState.isUploading
                        ) {
                            Icon(Icons.Default.AttachFile, contentDescription = "Attach File", tint = MaterialTheme.colorScheme.primary)
                        }

                        IconButton(
                            onClick = { imageLauncher.launch(androidx.activity.result.PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageOnly)) },
                            enabled = !uiState.isSendingMessage && !uiState.isUploading
                        ) {
                            Icon(Icons.Default.Image, contentDescription = "Attach Image", tint = MaterialTheme.colorScheme.primary)
                        }

                        OutlinedTextField(
                            value = messageText,
                            onValueChange = { messageText = it },
                            modifier = Modifier
                                .weight(1f)
                                .padding(horizontal = 4.dp),
                            placeholder = { Text("Type a message...") },
                            maxLines = 4,
                            shape = RoundedCornerShape(24.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        FloatingActionButton(
                            onClick = {
                                if (messageText.isNotBlank()) {
                                    viewModel.sendMessage(messageText)
                                    messageText = ""
                                }
                            },
                            containerColor = MaterialTheme.colorScheme.primary,
                            contentColor = MaterialTheme.colorScheme.onPrimary,
                            modifier = Modifier.size(48.dp),
                            shape = RoundedCornerShape(24.dp)
                        ) {
                            if (uiState.isSendingMessage || uiState.isUploading) {
                                CircularProgressIndicator(color = MaterialTheme.colorScheme.onPrimary, modifier = Modifier.size(24.dp), strokeWidth = 2.dp)
                            } else {
                                Icon(Icons.Filled.Send, contentDescription = "Send")
                            }
                        }
                    }
                }
            } else {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(MaterialTheme.colorScheme.surfaceVariant)
                        .padding(8.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text("This conversation is closed.", color = Color.Gray, fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}

@OptIn(ExperimentalFoundationApi::class)
@Composable
fun MessageBubble(message: AdminSupportMessage, onDelete: () -> Unit = {}, onEdit: (String) -> Unit = {}) {
    val isAdmin = message.senderRole == "admin"
    val alignment = if (isAdmin) Alignment.CenterEnd else Alignment.CenterStart
    val bgColor = if (isAdmin) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.secondaryContainer
    val textColor = if (isAdmin) MaterialTheme.colorScheme.onPrimary else MaterialTheme.colorScheme.onSecondaryContainer
    val context = LocalContext.current
    var showMenu by remember { mutableStateOf(false) }
    var showDeleteDialog by remember { mutableStateOf(false) }
    var showEditDialog by remember { mutableStateOf(false) }
    var editText by remember { mutableStateOf(message.message) }

    if (showDeleteDialog) {
        AlertDialog(
            onDismissRequest = { showDeleteDialog = false },
            title = { Text("Delete Message") },
            text = { Text("Are you sure you want to delete this message?") },
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

    if (showEditDialog) {
        AlertDialog(
            onDismissRequest = { showEditDialog = false },
            title = { Text("Edit Message") },
            text = {
                OutlinedTextField(
                    value = editText,
                    onValueChange = { editText = it },
                    modifier = Modifier.fillMaxWidth()
                )
            },
            confirmButton = {
                TextButton(onClick = {
                    showEditDialog = false
                    if (editText.isNotBlank() && editText != message.message) {
                        onEdit(editText)
                    }
                }) {
                    Text("Save")
                }
            },
            dismissButton = {
                TextButton(onClick = { showEditDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    Box(
        modifier = Modifier.fillMaxWidth(),
        contentAlignment = alignment
    ) {
        Column(
            horizontalAlignment = if (isAdmin) Alignment.End else Alignment.Start,
            modifier = Modifier.fillMaxWidth(0.8f) // Max width 80%
        ) {
            if (!isAdmin) {
                Text(
                    text = message.senderName,
                    fontSize = 12.sp,
                    color = Color.Gray,
                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp)
                )
            }
            Surface(
                color = bgColor,
                shape = RoundedCornerShape(
                    topStart = 16.dp,
                    topEnd = 16.dp,
                    bottomStart = if (isAdmin) 16.dp else 4.dp,
                    bottomEnd = if (isAdmin) 4.dp else 16.dp
                ),
                shadowElevation = 1.dp,
                modifier = Modifier.combinedClickable(
                    onClick = {},
                    onLongClick = {
                        showMenu = true
                    }
                )
            ) {
                Box {
                    Column(modifier = Modifier.padding(8.dp)) {
                        if (message.attachmentPath != null) {
                            val isImage = message.attachmentType?.startsWith("image/") == true
                            val attachmentUrl = "https://affscash.net/api/v2/chat?action=download&id=${message.id}"
                            
                            if (isImage) {
                                AsyncImage(
                                    model = ImageRequest.Builder(context).data(attachmentUrl).crossfade(true).build(),
                                    contentDescription = "Attachment",
                                    contentScale = ContentScale.Crop,
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .height(200.dp)
                                        .clip(RoundedCornerShape(8.dp))
                                        .padding(bottom = if (message.message.isNotBlank()) 8.dp else 0.dp)
                                        .clickable {
                                            val fileName = message.attachmentPath?.substringAfterLast("/") ?: "attachment_${message.id}"
                                            net.affscash.android.utils.FileDownloader.downloadSecureFile(
                                                context = context,
                                                url = attachmentUrl,
                                                fileName = fileName
                                            )
                                        }
                                )
                            } else {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .background(Color.Black.copy(alpha = 0.1f), RoundedCornerShape(8.dp))
                                        .padding(8.dp)
                                        .clickable {
                                            val fileName = message.attachmentPath?.substringAfterLast("/") ?: "attachment_${message.id}"
                                            net.affscash.android.utils.FileDownloader.downloadSecureFile(
                                                context = context,
                                                url = attachmentUrl,
                                                fileName = fileName
                                            )
                                        },
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Icon(Icons.Default.InsertDriveFile, contentDescription = "File", tint = textColor, modifier = Modifier.size(24.dp))
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text(
                                        text = message.attachmentName ?: "File",
                                        color = textColor,
                                        style = MaterialTheme.typography.bodyMedium,
                                        maxLines = 1,
                                        overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis
                                    )
                                }
                                if (message.message.isNotBlank()) {
                                    Spacer(modifier = Modifier.height(4.dp))
                                }
                            }
                        }

                        if (message.message.isNotBlank()) {
                            Text(
                                text = message.message,
                                color = textColor,
                                fontSize = 16.sp
                            )
                        }
                    }

                    DropdownMenu(
                        expanded = showMenu,
                        onDismissRequest = { showMenu = false }
                    ) {
                        if (isAdmin) {
                            DropdownMenuItem(
                                text = { Text("Edit") },
                                onClick = {
                                    showMenu = false
                                    editText = message.message
                                    showEditDialog = true
                                }
                            )
                            DropdownMenuItem(
                                text = { Text("Delete") },
                                onClick = {
                                    showMenu = false
                                    showDeleteDialog = true
                                }
                            )
                        }
                    }
                }
            }
            Row(verticalAlignment = Alignment.CenterVertically) {
                if (message.isEdited == 1) {
                    Text(
                        text = "(Edited) ",
                        fontSize = 10.sp,
                        color = Color.Gray,
                        modifier = Modifier.padding(vertical = 2.dp)
                    )
                }
                Text(
                    text = formatTime(message.createdAt),
                    fontSize = 10.sp,
                    color = Color.Gray,
                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp)
                )
            }
        }
    }
}

private fun formatTime(dateStr: String?): String {
    if (dateStr.isNullOrEmpty()) return ""
    return try {
        val format = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
        format.timeZone = TimeZone.getTimeZone("UTC")
        val date = format.parse(dateStr)
        val outFormat = SimpleDateFormat("HH:mm", Locale.getDefault())
        outFormat.timeZone = TimeZone.getDefault()
        date?.let { outFormat.format(it) } ?: ""
    } catch (e: Exception) {
        ""
    }
}


private fun getFileFromUri(context: android.content.Context, uri: Uri): File? {
    return try {
        val inputStream = context.contentResolver.openInputStream(uri) ?: return null
        // Get original filename to preserve the extension
        var fileName = "upload_admin_${System.currentTimeMillis()}"
        context.contentResolver.query(uri, null, null, null, null)?.use { cursor ->
            val nameIndex = cursor.getColumnIndex(android.provider.OpenableColumns.DISPLAY_NAME)
            if (cursor.moveToFirst() && nameIndex != -1) {
                fileName = cursor.getString(nameIndex) ?: fileName
            }
        }
        val tempFile = File(context.cacheDir, fileName)
        val outputStream = FileOutputStream(tempFile)
        inputStream.copyTo(outputStream)
        inputStream.close()
        outputStream.close()
        tempFile
    } catch (e: Exception) {
        null
    }
}
