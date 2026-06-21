import os
import glob

directory = "android_app/app/src/main/java/net/affscash/android/ui"

for filepath in glob.glob(directory + "/**/*.kt", recursive=True):
    with open(filepath, "r") as f:
        content = f.read()

    original_content = content
    
    # 1. Global Screen Content Padding
    content = content.replace("contentPadding = PaddingValues(16.dp)", "contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp)")
    content = content.replace("contentPadding = PaddingValues(vertical = 16.dp)", "contentPadding = PaddingValues(vertical = 8.dp)")
    
    # 2. General Padding Reductions (mostly hits Cards, Columns, Rows)
    content = content.replace("Modifier.padding(16.dp)", "Modifier.padding(12.dp)")
    content = content.replace("Modifier.padding(horizontal = 16.dp", "Modifier.padding(horizontal = 12.dp")
    content = content.replace("Modifier.padding(vertical = 16.dp)", "Modifier.padding(vertical = 8.dp)")
    
    # 3. Spacers
    content = content.replace("Spacer(modifier = Modifier.height(24.dp))", "Spacer(modifier = Modifier.height(16.dp))")
    content = content.replace("Spacer(modifier = Modifier.height(16.dp))", "Spacer(modifier = Modifier.height(8.dp))")
    
    # 4. FilterChips Height
    content = content.replace("FilterChip(", "FilterChip(\nmodifier = Modifier.height(32.dp),")
    
    # 5. Fix up FilterChip if we doubled the modifier incorrectly
    content = content.replace("FilterChip(\nmodifier = Modifier.height(32.dp),\nmodifier = ", "FilterChip(\nmodifier = Modifier.height(32.dp).then(")

    if content != original_content:
        with open(filepath, "w") as f:
            f.write(content)
        print(f"Optimized layout for {filepath}")
