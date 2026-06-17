package com.example.affscash.ui.manager

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.example.affscash.data.model.EditAffiliateRequest
import com.example.affscash.data.model.ManagerAffiliateListModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ManagerEditAffiliateScreen(
    affiliate: ManagerAffiliateListModel,
    onNavigateBack: () -> Unit,
    viewModel: ManagerAffiliatesViewModel
) {
    var firstName by remember { mutableStateOf(affiliate.firstName) }
    var lastName by remember { mutableStateOf(affiliate.lastName) }
    var company by remember { mutableStateOf(affiliate.company ?: "") }
    var phone by remember { mutableStateOf("") }
    var country by remember { mutableStateOf("US") }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Edit Affiliate") },
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
                value = company,
                onValueChange = { company = it },
                label = { Text("Company") },
                modifier = Modifier.fillMaxWidth()
            )

            OutlinedTextField(
                value = phone,
                onValueChange = { phone = it },
                label = { Text("Phone") },
                modifier = Modifier.fillMaxWidth()
            )

            OutlinedTextField(
                value = country,
                onValueChange = { country = it.take(2).uppercase() },
                label = { Text("Country Code") },
                modifier = Modifier.fillMaxWidth()
            )

            Spacer(modifier = Modifier.height(16.dp))

            Button(
                onClick = {
                    if (firstName.isNotBlank() && lastName.isNotBlank()) {
                        viewModel.editAffiliate(
                            EditAffiliateRequest(
                                id = affiliate.affId,
                                firstName = firstName,
                                lastName = lastName,
                                company = company.ifBlank { null },
                                phone = phone.ifBlank { null },
                                country = country
                            ),
                            onSuccess = onNavigateBack
                        )
                    }
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = firstName.isNotBlank() && lastName.isNotBlank()
            ) {
                Text("Save Changes")
            }
        }
    }
}
