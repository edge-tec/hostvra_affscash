package com.example.affscash.ui.manager

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import com.example.affscash.data.model.CreateAffiliateRequest

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerCreateAffiliateScreen(
    onNavigateBack: () -> Unit,
    viewModel: ManagerAffiliatesViewModel
) {
    var firstName by remember { mutableStateOf("") }
    var lastName by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var company by remember { mutableStateOf("") }
    var phone by remember { mutableStateOf("") }
    var country by remember { mutableStateOf("US") }
    var status by remember { mutableStateOf("active") }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Create Affiliate") },
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
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            OutlinedTextField(
                value = firstName,
                onValueChange = { firstName = it },
                label = { Text("First Name *") },
                modifier = Modifier.fillMaxWidth()
            )
            
            OutlinedTextField(
                value = lastName,
                onValueChange = { lastName = it },
                label = { Text("Last Name *") },
                modifier = Modifier.fillMaxWidth()
            )

            OutlinedTextField(
                value = email,
                onValueChange = { email = it },
                label = { Text("Email *") },
                modifier = Modifier.fillMaxWidth()
            )

            OutlinedTextField(
                value = password,
                onValueChange = { password = it },
                label = { Text("Password * (min 8 chars)") },
                modifier = Modifier.fillMaxWidth(),
                visualTransformation = PasswordVisualTransformation()
            )

            OutlinedTextField(
                value = company,
                onValueChange = { company = it },
                label = { Text("Company (Optional)") },
                modifier = Modifier.fillMaxWidth()
            )

            OutlinedTextField(
                value = phone,
                onValueChange = { phone = it },
                label = { Text("Phone (Optional)") },
                modifier = Modifier.fillMaxWidth()
            )

            OutlinedTextField(
                value = country,
                onValueChange = { country = it.take(2).uppercase() },
                label = { Text("Country Code (e.g. US, UK)") },
                modifier = Modifier.fillMaxWidth()
            )

            Spacer(modifier = Modifier.height(16.dp))

            Button(
                onClick = {
                    if (firstName.isNotBlank() && lastName.isNotBlank() && email.isNotBlank() && password.length >= 8) {
                        viewModel.createAffiliate(
                            CreateAffiliateRequest(
                                firstName = firstName,
                                lastName = lastName,
                                email = email,
                                password = password,
                                company = company.ifBlank { null },
                                phone = phone.ifBlank { null },
                                country = country,
                                status = status
                            ),
                            onSuccess = onNavigateBack
                        )
                    }
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = firstName.isNotBlank() && lastName.isNotBlank() && email.isNotBlank() && password.length >= 8
            ) {
                Text("Create Affiliate")
            }
        }
    }
}
