import os
import re

files_to_update = [
    "android_app/app/src/main/java/net/affscash/android/ui/dashboard/DashboardScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerDashboardScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/admin/AdminDashboardScreen.kt"
]

replacements = {
    r"16\.dp": "8.dp",
    r"20\.dp": "12.dp",
    r"24\.dp": "16.dp",
    r"180\.dp": "140.dp",
    r"32\.dp": "28.dp",
    r"Spacing\s*=\s*8\.dp": "Spacing = 4.dp",
    r"padding\(8\.dp\)": "padding(4.dp)",
    r"padding\(horizontal\s*=\s*12\.dp": "padding(horizontal = 8.dp",
    r"padding\(vertical\s*=\s*8\.dp": "padding(vertical = 4.dp",
    r"Spacer\(modifier\s*=\s*Modifier\.height\(8\.dp\)\)": "Spacer(modifier = Modifier.height(4.dp))",
    r"Spacer\(modifier\s*=\s*Modifier\.height\(16\.dp\)\)": "Spacer(modifier = Modifier.height(8.dp))"
}

for file_path in files_to_update:
    if not os.path.exists(file_path):
        continue
    with open(file_path, "r") as f:
        content = f.read()
        
    for pattern, repl in replacements.items():
        content = re.sub(pattern, repl, content)
        
    with open(file_path, "w") as f:
        f.write(content)
        
print("Dashboards compacted successfully.")
