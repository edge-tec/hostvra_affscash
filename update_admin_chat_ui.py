import os

filepath = "android_app/app/src/main/java/net/affscash/android/ui/screens/admin/support/AdminSupportChatScreen.kt"
with open(filepath, "r") as f:
    content = f.read()

# Add necessary imports
imports = """import androidx.activity.compose.rememberLauncherForActivityResult
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
"""
content = content.replace("import java.util.TimeZone", "import java.util.TimeZone\n" + imports)

# Get File From Uri logic
get_file_fun = """
private fun getFileFromUri(context: android.content.Context, uri: Uri): File? {
    return try {
        val inputStream = context.contentResolver.openInputStream(uri) ?: return null
        val tempFile = File(context.cacheDir, "upload_admin_${System.currentTimeMillis()}.jpg")
        val outputStream = FileOutputStream(tempFile)
        inputStream.copyTo(outputStream)
        inputStream.close()
        outputStream.close()
        tempFile
    } catch (e: Exception) {
        null
    }
}
"""
content = content + "\n" + get_file_fun

# Add UI state for file picker
old_ui_state = """    val uiState by viewModel.uiState.collectAsState()
    var messageText by remember { mutableStateOf("") }
    val listState = rememberLazyListState()"""

new_ui_state = """    val uiState by viewModel.uiState.collectAsState()
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
                viewModel.uploadAndSendMessage(file, mimeType, "Sent an attachment")
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
                viewModel.uploadAndSendMessage(file, mimeType, "Sent an image")
            }
        }
    }"""
content = content.replace(old_ui_state, new_ui_state)

# Add imePadding to Column
content = content.replace("modifier = Modifier\n                .fillMaxSize()\n                .padding(paddingValues)", "modifier = Modifier\n                .fillMaxSize()\n                .padding(paddingValues)\n                .imePadding()")

# Update Message Bubble call
content = content.replace("MessageBubble(message = message)", "MessageBubble(message = message, onDelete = { viewModel.deleteMessage(message.id) })")

# Replace Input Area
old_input = """            // Input Area
            if (!isClosed) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(MaterialTheme.colorScheme.surfaceVariant)
                        .padding(8.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    OutlinedTextField(
                        value = messageText,
                        onValueChange = { messageText = it },
                        modifier = Modifier.weight(1f),
                        placeholder = { Text("Type a message...") },
                        maxLines = 4,
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedContainerColor = MaterialTheme.colorScheme.surface,
                            unfocusedContainerColor = MaterialTheme.colorScheme.surface
                        )
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                    FloatingActionButton(
                        onClick = {
                            if (messageText.isNotBlank()) {
                                viewModel.sendMessage(messageText)
                                messageText = ""
                            }
                        },
                        containerColor = MaterialTheme.colorScheme.primary,
                        contentColor = MaterialTheme.colorScheme.onPrimary,
                        modifier = Modifier.size(48.dp)
                    ) {
                        if (uiState.isSendingMessage) {
                            CircularProgressIndicator(color = MaterialTheme.colorScheme.onPrimary, modifier = Modifier.size(24.dp))
                        } else {
                            Icon(Icons.Filled.Send, contentDescription = "Send")
                        }
                    }
                }
            }"""

new_input = """            // Input Area
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
            }"""
content = content.replace(old_input, new_input)

# Update MessageBubble to support Coil and Delete
old_bubble = """@Composable
fun MessageBubble(message: AdminSupportMessage) {
    val isAdmin = message.senderRole == "admin"
    val alignment = if (isAdmin) Alignment.CenterEnd else Alignment.CenterStart
    val bgColor = if (isAdmin) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.secondaryContainer
    val textColor = if (isAdmin) MaterialTheme.colorScheme.onPrimary else MaterialTheme.colorScheme.onSecondaryContainer

    Box(
        modifier = Modifier.fillMaxWidth(),
        contentAlignment = alignment
    ) {
        Column(
            horizontalAlignment = if (isAdmin) Alignment.End else Alignment.Start,
            modifier = Modifier.fillMaxWidth(0.8f) // Max width 80%
        ) {
            Text(
                text = message.senderName,
                fontSize = 12.sp,
                color = Color.Gray,
                modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp)
            )
            Box(
                modifier = Modifier
                    .background(
                        color = bgColor,
                        shape = RoundedCornerShape(
                            topStart = 16.dp,
                            topEnd = 16.dp,
                            bottomStart = if (isAdmin) 16.dp else 4.dp,
                            bottomEnd = if (isAdmin) 4.dp else 16.dp
                        )
                    )
                    .padding(12.dp)
            ) {
                Text(
                    text = message.message,
                    color = textColor,
                    fontSize = 16.sp
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
}"""

new_bubble = """@OptIn(ExperimentalFoundationApi::class)
@Composable
fun MessageBubble(message: AdminSupportMessage, onDelete: () -> Unit = {}) {
    val isAdmin = message.senderRole == "admin"
    val alignment = if (isAdmin) Alignment.CenterEnd else Alignment.CenterStart
    val bgColor = if (isAdmin) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.secondaryContainer
    val textColor = if (isAdmin) MaterialTheme.colorScheme.onPrimary else MaterialTheme.colorScheme.onSecondaryContainer
    val context = LocalContext.current
    var showMenu by remember { mutableStateOf(false) }
    var showDeleteDialog by remember { mutableStateOf(false) }

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
                    Column(modifier = Modifier.padding(12.dp)) {
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
                                            val intent = Intent(Intent.ACTION_VIEW).apply { data = Uri.parse(attachmentUrl) }
                                            context.startActivity(intent)
                                        }
                                )
                            } else {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .background(Color.Black.copy(alpha = 0.1f), RoundedCornerShape(8.dp))
                                        .padding(8.dp)
                                        .clickable {
                                            val intent = Intent(Intent.ACTION_VIEW).apply { data = Uri.parse(attachmentUrl) }
                                            context.startActivity(intent)
                                        },
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Icon(Icons.Default.InsertDriveFile, contentDescription = "File", tint = textColor, modifier = Modifier.size(24.dp))
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Text(
                                        text = message.attachmentName ?: "File",
                                        color = textColor,
                                        style = MaterialTheme.typography.bodyMedium,
                                        maxLines = 1,
                                        overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis
                                    )
                                }
                                if (message.message.isNotBlank()) {
                                    Spacer(modifier = Modifier.height(8.dp))
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
            Text(
                text = formatTime(message.createdAt),
                fontSize = 10.sp,
                color = Color.Gray,
                modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp)
            )
        }
    }
}"""
content = content.replace(old_bubble, new_bubble)

with open(filepath, "w") as f:
    f.write(content)
print("Updated AdminSupportChatScreen.kt!")
