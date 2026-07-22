package net.affscash.android.ui.components

import android.app.DatePickerDialog
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.DateRange
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import net.affscash.android.data.model.DateRangeOption
import net.affscash.android.data.model.DateRangeState
import java.util.Calendar

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DateRangeFilterComponent(
    state: DateRangeState,
    onOptionSelected: (DateRangeOption) -> Unit,
    onCustomRangeSelected: (startDate: String, endDate: String) -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    var showCustomDialog by remember { mutableStateOf(false) }
    val (startDate, endDate) = state.getFormattedDates()

    Column(modifier = modifier.fillMaxWidth().padding(vertical = 4.dp)) {
        // Horizontal Scrollable Filter Chips
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .horizontalScroll(rememberScrollState()),
            horizontalArrangement = Arrangement.spacedBy(6.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            DateRangeOption.values().forEach { option ->
                val isSelected = state.option == option
                FilterChip(
                    selected = isSelected,
                    onClick = {
                        if (option == DateRangeOption.CUSTOM) {
                            showCustomDialog = true
                        } else {
                            onOptionSelected(option)
                        }
                    },
                    label = {
                        Text(
                            text = option.label,
                            fontSize = 12.sp,
                            fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal
                        )
                    },
                    leadingIcon = if (isSelected) {
                        { Icon(Icons.Filled.Check, contentDescription = null, modifier = Modifier.size(14.dp)) }
                    } else if (option == DateRangeOption.CUSTOM) {
                        { Icon(Icons.Filled.DateRange, contentDescription = null, modifier = Modifier.size(14.dp)) }
                    } else null,
                    colors = FilterChipDefaults.filterChipColors(
                        selectedContainerColor = MaterialTheme.colorScheme.primaryContainer,
                        selectedLabelColor = MaterialTheme.colorScheme.onPrimaryContainer
                    )
                )
            }
        }

        // Active Date Range Summary Subtitle
        val (startDate, endDate) = state.getFormattedDates()
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 4.dp, vertical = 2.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    Icons.Filled.CalendarMonth,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(14.dp)
                )
                Spacer(modifier = Modifier.width(4.dp))
                Text(
                    text = "$startDate  →  $endDate",
                    fontSize = 11.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }
    }

    // Custom Date Range Picker Dialog
    if (showCustomDialog) {
        var startDateStr by remember { mutableStateOf(startDate) }
        var endDateStr by remember { mutableStateOf(endDate) }

        AlertDialog(
            onDismissRequest = { showCustomDialog = false },
            title = { Text("Select Custom Date Range", style = MaterialTheme.typography.titleMedium) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    Text("Choose start and end dates:", fontSize = 13.sp)

                    // Start Date Button
                    OutlinedButton(
                        onClick = {
                            val calendar = Calendar.getInstance()
                            DatePickerDialog(
                                context,
                                { _, year, month, dayOfMonth ->
                                    startDateStr = String.format("%04d-%02d-%02d", year, month + 1, dayOfMonth)
                                },
                                calendar.get(Calendar.YEAR),
                                calendar.get(Calendar.MONTH),
                                calendar.get(Calendar.DAY_OF_MONTH)
                            ).show()
                        },
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text("Start Date: $startDateStr")
                    }

                    // End Date Button
                    OutlinedButton(
                        onClick = {
                            val calendar = Calendar.getInstance()
                            DatePickerDialog(
                                context,
                                { _, year, month, dayOfMonth ->
                                    endDateStr = String.format("%04d-%02d-%02d", year, month + 1, dayOfMonth)
                                },
                                calendar.get(Calendar.YEAR),
                                calendar.get(Calendar.MONTH),
                                calendar.get(Calendar.DAY_OF_MONTH)
                            ).show()
                        },
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text("End Date: $endDateStr")
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        showCustomDialog = false
                        onCustomRangeSelected(startDateStr, endDateStr)
                    }
                ) {
                    Text("Apply Filter")
                }
            },
            dismissButton = {
                TextButton(onClick = { showCustomDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }
}
