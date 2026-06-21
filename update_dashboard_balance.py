import os

filepath = "android_app/app/src/main/java/net/affscash/android/ui/dashboard/DashboardScreen.kt"

with open(filepath, "r") as f:
    content = f.read()

# Make balance widget smaller
old_balance = """                        modifier = Modifier.padding(end = 8.dp),
                        onClick = onNavigateToInvoices
                    ) {
                        Row(
                            modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                "$ $balance", 
                                color = Color(0xFF059669),
                                fontWeight = FontWeight.ExtraBold,
                                style = MaterialTheme.typography.labelLarge
                            )
                            Spacer(modifier = Modifier.width(4.dp))
                            Icon(
                                Icons.Default.ArrowDropDown,
                                contentDescription = "Dropdown",
                                tint = Color(0xFF059669),
                                modifier = Modifier.size(16.dp)
                            )
                        }
                    }"""

new_balance = """                        modifier = Modifier.padding(end = 4.dp),
                        onClick = onNavigateToInvoices
                    ) {
                        Row(
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                "$ $balance", 
                                color = Color(0xFF059669),
                                fontWeight = FontWeight.ExtraBold,
                                fontSize = 12.sp
                            )
                            Spacer(modifier = Modifier.width(2.dp))
                            Icon(
                                Icons.Default.ArrowDropDown,
                                contentDescription = "Dropdown",
                                tint = Color(0xFF059669),
                                modifier = Modifier.size(14.dp)
                            )
                        }
                    }"""

content = content.replace(old_balance, new_balance)

with open(filepath, "w") as f:
    f.write(content)
print("Balance updated!")
