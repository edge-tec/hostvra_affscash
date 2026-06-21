import os
import glob

search_dir = "android_app/app/src/main/java/net/affscash/android/ui"
kt_files = glob.glob(f"{search_dir}/**/*.kt", recursive=True)

count = 0
for filepath in kt_files:
    if "CompactTopBar.kt" in filepath:
        continue
    with open(filepath, "r") as f:
        content = f.read()

    if "TopAppBar(" in content:
        # Check if we need to import or just use fully qualified name
        # We'll just replace 'TopAppBar(' with 'net.affscash.android.ui.components.CompactTopBar('
        # But wait, there might be 'import androidx.compose.material3.TopAppBar' which we can remove or leave
        new_content = content.replace("TopAppBar(", "net.affscash.android.ui.components.CompactTopBar(")
        
        with open(filepath, "w") as f:
            f.write(new_content)
        count += 1
        print(f"Updated {filepath}")

print(f"Total files updated: {count}")
