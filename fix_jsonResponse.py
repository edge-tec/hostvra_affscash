import os
import glob

api_dir = 'api'
changed_files = 0

for filepath in glob.glob(api_dir + '/**/*.php', recursive=True):
    with open(filepath, 'r') as f:
        content = f.read()
    
    if 'Helpers::jsonResponse' in content:
        new_content = content.replace('Helpers::jsonResponse', 'Helpers::json')
        with open(filepath, 'w') as f:
            f.write(new_content)
        print(f"Fixed {filepath}")
        changed_files += 1

print(f"Total files fixed: {changed_files}")
