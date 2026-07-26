package net.affscash.android.ui.invoices

import android.content.Intent
import android.net.Uri
import android.widget.Toast
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Download
import androidx.compose.material.icons.filled.PictureAsPdf
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import net.affscash.android.data.model.Invoice
import net.affscash.android.ui.dashboard.GlassCard
import net.affscash.android.ui.dashboard.PremiumUI
import net.affscash.android.ui.dashboard.StatusBadge

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun InvoiceScreen(
    onNavigateBack: () -> Unit,
    viewModel: InvoiceViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    var isDownloading by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            Surface(
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.background,
                shadowElevation = 2.dp
            ) {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(start = 8.dp, end = 24.dp, top = 8.dp, bottom = 8.dp)
                    ) {
                        IconButton(onClick = onNavigateBack) {
                            Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                        }
                        Text(
                            text = "My Invoices",
                            style = PremiumUI.HeaderStyle,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                    }
                }
            }
        }
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .padding(paddingValues)
                .fillMaxSize()
                .background(PremiumUI.PageBackground)
        ) {
            when (val state = uiState) {
                is InvoiceState.Loading -> {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
                is InvoiceState.Error -> {
                    Column(
                        modifier = Modifier.align(Alignment.Center),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(state.message, color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(4.dp))
                        Button(onClick = { viewModel.loadInvoices() }) {
                            Text("Retry")
                        }
                    }
                }
                is InvoiceState.Success -> {
                    val invoices = state.invoices
                    if (invoices.isEmpty()) {
                        Text(
                            "No invoices found.",
                            modifier = Modifier.align(Alignment.Center),
                            style = MaterialTheme.typography.bodyLarge,
                            color = Color.Gray
                        )
                    } else {
                        LazyColumn(
                            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 12.dp),
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            items(invoices) { invoice ->
                                InvoiceCard(
                                    invoice = invoice,
                                    onPdfClick = {
                                        if (isDownloading) return@InvoiceCard
                                        isDownloading = true
                                        Toast.makeText(context, "Preparing PDF...", Toast.LENGTH_SHORT).show()
                                        viewModel.downloadPdf(
                                            invoiceId = invoice.invoiceId,
                                            onSuccess = { url ->
                                                isDownloading = false
                                                net.affscash.android.utils.FileDownloader.downloadSecureFile(
                                                    context = context,
                                                    url = url,
                                                    fileName = "Invoice_${invoice.invoiceNumber}.pdf"
                                                )
                                            },
                                            onError = { msg ->
                                                isDownloading = false
                                                Toast.makeText(context, msg, Toast.LENGTH_SHORT).show()
                                            }
                                        )
                                    }
                                )
                            }
                        }
                    }
                }
            }
            
            if (isDownloading) {
                Box(
                    modifier = Modifier.fillMaxSize().background(Color.Black.copy(alpha = 0.3f)),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator()
                }
            }
        }
    }
}

@Composable
fun InvoiceCard(invoice: Invoice, onPdfClick: () -> Unit) {
    GlassCard(
        modifier = Modifier.fillMaxWidth()
    ) {
        Column(modifier = Modifier.padding(14.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = invoice.invoiceNumber,
                    fontSize = 14.sp,
                    fontWeight = FontWeight.ExtraBold,
                    color = Color(0xFF4338CA)
                )
                
                StatusBadge(status = invoice.status)
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            HorizontalDivider(color = Color(0xFFE2E8F0))
            Spacer(modifier = Modifier.height(8.dp))
            
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Period", fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF94A3B8))
                    Text(
                        text = if (invoice.periodStart != null && invoice.periodEnd != null) 
                            "${invoice.periodStart} to ${invoice.periodEnd}" 
                        else "-", 
                        fontSize = 12.sp,
                        fontWeight = FontWeight.SemiBold,
                        color = Color(0xFF334155)
                    )
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("Total Amount", fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF94A3B8))
                    Text(
                        text = "$${(( invoice.total )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", 
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Black,
                        color = Color(0xFF4F46E5)
                    )
                }
            }
            
            Spacer(modifier = Modifier.height(8.dp))
            
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text("Issued On", fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF94A3B8))
                    Text(text = invoice.createdAt?.take(10) ?: "-", fontSize = 11.sp, fontWeight = FontWeight.Medium, color = Color(0xFF64748B))
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("Due Date", fontSize = 10.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFF94A3B8))
                    Text(text = invoice.dueDate ?: "-", fontSize = 11.sp, fontWeight = FontWeight.Medium, color = Color(0xFF64748B))
                }
            }
            
            Spacer(modifier = Modifier.height(10.dp))
            
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.End) {
                OutlinedButton(
                    onClick = onPdfClick,
                    modifier = Modifier.padding(end = 8.dp),
                    shape = PremiumUI.ButtonShape,
                    contentPadding = PaddingValues(horizontal = 14.dp, vertical = 6.dp)
                ) {
                    Icon(Icons.Default.Visibility, contentDescription = "View", modifier = Modifier.size(15.dp))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("View", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                }
                Button(
                    onClick = onPdfClick,
                    shape = PremiumUI.ButtonShape,
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF4F46E5)),
                    contentPadding = PaddingValues(horizontal = 14.dp, vertical = 6.dp)
                ) {
                    Icon(Icons.Default.PictureAsPdf, contentDescription = "PDF", modifier = Modifier.size(15.dp))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("PDF", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}
