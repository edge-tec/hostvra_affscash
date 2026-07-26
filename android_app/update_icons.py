import os
import re

ui_dir = "/Users/mizanurrahman/claude/affscash script/Affscash.net  latest script/project aapanel/android_app/app/src/main/java/net/affscash/android/ui"

for root, dirs, files in os.walk(ui_dir):
    for file in files:
        if file.endswith(".kt") and file not in ["PremiumUI.kt", "MainScreen.kt"]:
            filepath = os.path.join(root, file)
            with open(filepath, "r") as f:
                content = f.read()
            
            # Replace Icon( with net.affscash.android.ui.dashboard.GradientIcon(
            # We use regex to ensure we only replace the function call Icon(
            # \bIcon\(
            new_content = re.sub(r'\bIcon\(', 'net.affscash.android.ui.dashboard.GradientIcon(', content)
            
            if new_content != content:
                with open(filepath, "w") as f:
                    f.write(new_content)
                print(f"Updated {file}")

print("Done updating icons.")
