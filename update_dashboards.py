import os

files_to_update = [
    "android_app/app/src/main/java/net/affscash/android/ui/admin/AdminDashboardScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerDashboardScreen.kt"
]

for filepath in files_to_update:
    if not os.path.exists(filepath):
        continue
        
    with open(filepath, "r") as f:
        content = f.read()

    # Padding and margins
    content = content.replace("PaddingValues(vertical = 16.dp)", "PaddingValues(vertical = 8.dp)")
    content = content.replace("Spacer(modifier = Modifier.height(24.dp))", "Spacer(modifier = Modifier.height(16.dp))")
    content = content.replace("Spacer(modifier = Modifier.height(16.dp))\n", "Spacer(modifier = Modifier.height(8.dp))\n")
    content = content.replace("padding(16.dp)", "padding(12.dp)")
    
    # Fonts and Text Styles
    content = content.replace("style = MaterialTheme.typography.headlineSmall", "style = MaterialTheme.typography.titleMedium")
    content = content.replace("style = MaterialTheme.typography.titleLarge", "style = MaterialTheme.typography.titleMedium")
    
    # Chart heights
    content = content.replace("height(300.dp)", "height(180.dp)")
    content = content.replace("height(250.dp)", "height(160.dp)")
    
    # Filter Tabs Spacing
    content = content.replace("horizontalArrangement = Arrangement.spacedBy(8.dp)", "horizontalArrangement = Arrangement.spacedBy(4.dp)")

    with open(filepath, "w") as f:
        f.write(content)
    print(f"Updated {filepath}")
