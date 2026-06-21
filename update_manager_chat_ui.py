import os

filepath = "android_app/app/src/main/java/net/affscash/android/ui/manager/support/ManagerChatScreen.kt"
with open(filepath, "r") as f:
    content = f.read()

# Add necessary imports
imports = """import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.combinedClickable
import androidx.compose.ui.layout.ContentScale
import android.content.Intent
import android.net.Uri
import java.io.File
import java.io.FileOutputStream
"""
content = content.replace("import java.io.FileOutputStream", imports)

# Add imePadding to Column
content = content.replace("modifier = Modifier\n                .fillMaxSize()\n                .padding(paddingValues)", "modifier = Modifier\n                .fillMaxSize()\n                .padding(paddingValues)\n                .imePadding()")

# Update Message Bubble call
content = content.replace("MessageBubble(message = message, affiliateId = selectedConv?.affiliateId ?: 0)", "MessageBubble(message = message, affiliateId = selectedConv?.affiliateId ?: 0, onDelete = { viewModel.deleteMessage(message.id) })")

# Update MessageBubble to support Delete
old_bubble = """@Composable
fun MessageBubble(message: ManagerMessage, affiliateId: Int) {"""

new_bubble = """@OptIn(ExperimentalFoundationApi::class)
@Composable
fun MessageBubble(message: ManagerMessage, affiliateId: Int, onDelete: () -> Unit = {}) {
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
    }"""
content = content.replace(old_bubble, new_bubble)

# Replace Box modifier in MessageBubble to include combinedClickable for showMenu
content = content.replace("""        Column(
            modifier = Modifier
                .widthIn(max = 280.dp)
                .clip(shape)
                .background(bgColor)
                .padding(12.dp)
        )""", """        Surface(
            color = bgColor,
            shape = shape,
            modifier = Modifier.combinedClickable(
                onClick = {},
                onLongClick = { showMenu = true }
            )
        ) {
            Box {
                Column(
                    modifier = Modifier
                        .widthIn(max = 280.dp)
                        .padding(12.dp)
                )""")

content = content.replace("""                modifier = Modifier
                    .align(Alignment.End)
                    .padding(top = 4.dp)
            )
        }
    }
}""", """                modifier = Modifier
                    .align(Alignment.End)
                    .padding(top = 4.dp)
            )
            
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
    }
}""")

with open(filepath, "w") as f:
    f.write(content)
print("Updated ManagerChatScreen.kt!")
