package com.example.affscash.ui.settings

import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ExitToApp
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import android.content.Intent
import android.widget.Toast
import com.example.affscash.MainActivity

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SettingsScreen(
    role: String,
    onLogout: () -> Unit,
    onNavigateToInvoices: () -> Unit = {},
    viewModel: SettingsViewModel = hiltViewModel()
) {
    val context = LocalContext.current
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Settings") }
            )
        }
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            Card(
                modifier = Modifier.fillMaxWidth(),
                elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(16.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Icon(
                        imageVector = Icons.Default.Person,
                        contentDescription = "Profile",
                        modifier = Modifier.size(48.dp),
                        tint = MaterialTheme.colorScheme.primary
                    )
                    Spacer(modifier = Modifier.width(16.dp))
                    Column {
                        Text(
                            text = "Logged in as",
                            style = MaterialTheme.typography.bodyMedium
                        )
                        Text(
                            text = role.replaceFirstChar { it.uppercase() },
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold
                        )
                    }
                }
            }



            Spacer(modifier = Modifier.height(16.dp))

            if (role == "affiliate") {
                Button(
                    onClick = onNavigateToInvoices,
                    modifier = Modifier.fillMaxWidth(),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = MaterialTheme.colorScheme.primary
                    ),
                    contentPadding = PaddingValues(vertical = 12.dp)
                ) {
                    Icon(androidx.compose.material.icons.Icons.Default.PictureAsPdf, contentDescription = "Invoices")
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("My Invoices", style = MaterialTheme.typography.titleMedium)
                }
            }

            Spacer(modifier = Modifier.weight(1f))

            if (role == "affiliate") {
                Button(
                    onClick = {
                        viewModel.stopImpersonating(
                            onSuccess = { newRole ->
                                if (newRole != null) {
                                    Toast.makeText(context, "Returned to $newRole", Toast.LENGTH_SHORT).show()
                                    val intent = Intent(context, MainActivity::class.java).apply {
                                        flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                                    }
                                    context.startActivity(intent)
                                } else {
                                    Toast.makeText(context, "Not impersonating", Toast.LENGTH_SHORT).show()
                                }
                            },
                            onError = { msg ->
                                Toast.makeText(context, msg, Toast.LENGTH_SHORT).show()
                            }
                        )
                    },
                    modifier = Modifier.fillMaxWidth(),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = MaterialTheme.colorScheme.secondary
                    )
                ) {
                    Icon(Icons.Default.ArrowBack, contentDescription = "Return to Admin/Manager")
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Return to Admin/Manager")
                }
            }

            Button(
                onClick = onLogout,
                modifier = Modifier.fillMaxWidth(),
                colors = ButtonDefaults.buttonColors(
                    containerColor = MaterialTheme.colorScheme.error
                )
            ) {
                Icon(Icons.Default.ExitToApp, contentDescription = "Logout")
                Spacer(modifier = Modifier.width(8.dp))
                Text("Logout")
            }
        }
    }
}
