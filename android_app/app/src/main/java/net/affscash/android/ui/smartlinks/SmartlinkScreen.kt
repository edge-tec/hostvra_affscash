package net.affscash.android.ui.smartlinks

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.BasicTextField
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.ContentCopy
import androidx.compose.material.icons.filled.Link
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.Smartlink

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SmartlinkScreen(
    viewModel: SmartlinkViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    var showApplyDialog by remember { mutableStateOf<Smartlink?>(null) }
    var promoDesc by remember { mutableStateOf("") }
    var applyLoading by remember { mutableStateOf(false) }
    
    val context = LocalContext.current

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Smartlinks") },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        }
    ) { paddingValues ->
        Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {
            Box(modifier = Modifier.weight(1f).fillMaxWidth()) {
                when (uiState) {
                    is SmartlinkState.Loading -> {
                        CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                    }
                    is SmartlinkState.Error -> {
                        val msg = (uiState as SmartlinkState.Error).message
                        Column(
                            modifier = Modifier.align(Alignment.Center),
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            Text(msg, color = MaterialTheme.colorScheme.error)
                            Spacer(modifier = Modifier.height(8.dp))
                            Button(onClick = { viewModel.loadSmartlinks() }) {
                                Text("Retry")
                            }
                        }
                    }
                    is SmartlinkState.Success -> {
                        val smartlinks = (uiState as SmartlinkState.Success).smartlinks
                        if (smartlinks.isEmpty()) {
                            Text("No smartlinks available.", modifier = Modifier.align(Alignment.Center))
                        } else {
                            LazyColumn(
                                contentPadding = PaddingValues(16.dp),
                                verticalArrangement = Arrangement.spacedBy(16.dp)
                            ) {
                                items(smartlinks) { smartlink ->
                                    SmartlinkCard(
                                        smartlink = smartlink,
                                        onApplyClick = { showApplyDialog = smartlink },
                                        onCopyClick = { textToCopy ->
                                            val clipboard = context.getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
                                            val clip = ClipData.newPlainText("Tracking Link", textToCopy)
                                            clipboard.setPrimaryClip(clip)
                                            Toast.makeText(context, "Link copied!", Toast.LENGTH_SHORT).show()
                                        }
                                    )
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Apply Dialog
        showApplyDialog?.let { sl ->
            AlertDialog(
                onDismissRequest = { showApplyDialog = null; promoDesc = "" },
                title = { Text("Request Access to ${sl.name}") },
                text = {
                    Column {
                        if (sl.requireApproval == 1) {
                            Text("This smartlink requires approval. Please describe how you plan to promote it:", style = MaterialTheme.typography.bodyMedium)
                            Spacer(modifier = Modifier.height(8.dp))
                            OutlinedTextField(
                                value = promoDesc,
                                onValueChange = { promoDesc = it },
                                modifier = Modifier.fillMaxWidth().height(100.dp),
                                placeholder = { Text("E.g., Facebook ads, email list, etc.") },
                                maxLines = 4
                            )
                        } else {
                            Text("This smartlink is instantly approved. Click confirm to get access.", style = MaterialTheme.typography.bodyMedium)
                        }
                    }
                },
                confirmButton = {
                    Button(
                        onClick = {
                            applyLoading = true
                            viewModel.applySmartlink(sl.id, promoDesc) { success, msg ->
                                applyLoading = false
                                showApplyDialog = null
                                promoDesc = ""
                                Toast.makeText(context, msg, Toast.LENGTH_SHORT).show()
                            }
                        },
                        enabled = !applyLoading
                    ) {
                        if (applyLoading) {
                            CircularProgressIndicator(modifier = Modifier.size(24.dp), color = MaterialTheme.colorScheme.onPrimary)
                        } else {
                            Text("Confirm")
                        }
                    }
                },
                dismissButton = {
                    TextButton(onClick = { showApplyDialog = null; promoDesc = "" }) {
                        Text("Cancel")
                    }
                }
            )
        }
    }
}

@Composable
fun SmartlinkCard(
    smartlink: Smartlink,
    onApplyClick: () -> Unit,
    onCopyClick: (String) -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(11.dp)) {
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.Top) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = "#${smartlink.id}",
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        fontSize = 12.sp,
                        fontWeight = FontWeight.Bold
                    )
                    Spacer(modifier = Modifier.height(3.dp))
                    Text(smartlink.name, fontSize = 16.sp, fontWeight = FontWeight.Bold)
                }
                
                Badge(containerColor = MaterialTheme.colorScheme.primaryContainer) {
                    Text(smartlink.distributionType.uppercase(), color = MaterialTheme.colorScheme.onPrimaryContainer, fontSize = 10.sp, modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp))
                }
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            
            smartlink.description?.let {
                Text(it, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, lineHeight = 18.sp)
                Spacer(modifier = Modifier.height(8.dp))
            }
            
            Text("${smartlink.offerCount} active offers in rotation", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
            
            Spacer(modifier = Modifier.height(11.dp))
            
            when (smartlink.accessStatus) {
                "approved" -> {
                    // Access Granted Box
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(Color(0xFFD1FAE5), RoundedCornerShape(8.dp))
                            .padding(8.dp)
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.CheckCircle, contentDescription = null, tint = Color(0xFF10B981), modifier = Modifier.size(16.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Access Granted — you can use this smartlink", color = Color(0xFF065F46), fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                        }
                    }
                    
                    Spacer(modifier = Modifier.height(8.dp))
                    
                    Row(modifier = Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                        Box(
                            modifier = Modifier
                                .weight(1f)
                                .height(36.dp)
                                .border(1.dp, MaterialTheme.colorScheme.outline, RoundedCornerShape(4.dp))
                                .padding(horizontal = 8.dp),
                            contentAlignment = Alignment.CenterStart
                        ) {
                            BasicTextField(
                                value = smartlink.trackingLink ?: "",
                                onValueChange = {},
                                readOnly = true,
                                singleLine = true,
                                textStyle = TextStyle(
                                    fontSize = 12.sp,
                                    color = MaterialTheme.colorScheme.onSurface
                                ),
                                modifier = Modifier.fillMaxWidth()
                            )
                        }
                        Spacer(modifier = Modifier.width(6.dp))
                        Button(
                            onClick = { smartlink.trackingLink?.let { onCopyClick(it) } },
                            contentPadding = PaddingValues(horizontal = 12.dp),
                            modifier = Modifier.height(36.dp)
                        ) {
                            Icon(Icons.Default.ContentCopy, contentDescription = "Copy", modifier = Modifier.size(14.dp))
                            Spacer(modifier = Modifier.width(4.dp))
                            Text("Copy", fontSize = 12.sp)
                        }
                    }
                    Spacer(modifier = Modifier.height(6.dp))
                    Text("Replace sub1= with your sub-parameter value", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                "pending" -> {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(Color(0xFFFEF3C7), RoundedCornerShape(8.dp))
                            .padding(8.dp)
                    ) {
                        Text("Pending Approval", color = Color(0xFFB45309), fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                    }
                }
                "rejected" -> {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .background(Color(0xFFFEE2E2), RoundedCornerShape(8.dp))
                            .padding(8.dp)
                    ) {
                        Text("Access Rejected", color = Color(0xFF991B1B), fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                    }
                }
                else -> {
                    Button(
                        onClick = onApplyClick,
                        colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary),
                        modifier = Modifier.fillMaxWidth().height(40.dp),
                        contentPadding = PaddingValues(0.dp)
                    ) {
                        Text("Request Access", fontSize = 13.sp)
                    }
                }
            }
        }
    }
}
