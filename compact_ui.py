import os
import re

ui_dir = "android_app/app/src/main/java/net/affscash/android/ui"

# Define regex replacements
replacements = [
    # General Paddings
    (r'padding\(16\.dp\)', 'padding(12.dp)'),
    (r'padding\(start = 16\.dp, end = 16\.dp\)', 'padding(horizontal = 12.dp)'),
    (r'padding\(horizontal = 16\.dp\)', 'padding(horizontal = 12.dp)'),
    (r'padding\(vertical = 16\.dp\)', 'padding(vertical = 12.dp)'),
    
    (r'padding\(12\.dp\)', 'padding(8.dp)'),
    (r'padding\(horizontal = 12\.dp\)', 'padding(horizontal = 8.dp)'),
    (r'padding\(vertical = 12\.dp\)', 'padding(vertical = 8.dp)'),
    
    (r'padding\(24\.dp\)', 'padding(16.dp)'),

    (r'padding\(vertical = 4\.dp\)', 'padding(vertical = 2.dp)'),

    # Spacers
    (r'Spacer\(modifier = Modifier\.height\(24\.dp\)\)', 'Spacer(modifier = Modifier.height(16.dp))'),
    (r'Spacer\(modifier = Modifier\.height\(16\.dp\)\)', 'Spacer(modifier = Modifier.height(8.dp))'),
    (r'Spacer\(modifier = Modifier\.height\(12\.dp\)\)', 'Spacer(modifier = Modifier.height(8.dp))'),
    (r'Spacer\(modifier = Modifier\.height\(8\.dp\)\)', 'Spacer(modifier = Modifier.height(4.dp))'),

    (r'Spacer\(modifier = Modifier\.width\(16\.dp\)\)', 'Spacer(modifier = Modifier.width(8.dp))'),
    (r'Spacer\(modifier = Modifier\.width\(12\.dp\)\)', 'Spacer(modifier = Modifier.width(8.dp))'),
    (r'Spacer\(modifier = Modifier\.width\(8\.dp\)\)', 'Spacer(modifier = Modifier.width(4.dp))'),
    
    # Typography/Text Sizes
    (r'fontSize = 13\.sp', 'fontSize = 12.sp'),
    (r'fontSize = 14\.sp', 'fontSize = 13.sp'),
    (r'style = MaterialTheme\.typography\.titleMedium', 'style = MaterialTheme.typography.titleSmall'),
]

modified_files = 0

for root, _, files in os.walk(ui_dir):
    for file in files:
        if file.endswith(".kt"):
            filepath = os.path.join(root, file)
            with open(filepath, 'r') as f:
                content = f.read()
            
            new_content = content
            for pattern, repl in replacements:
                new_content = re.sub(pattern, repl, new_content)
                
            if new_content != content:
                with open(filepath, 'w') as f:
                    f.write(new_content)
                modified_files += 1
                print(f"Updated: {filepath}")

print(f"Total files updated: {modified_files}")
