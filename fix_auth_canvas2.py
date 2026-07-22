import os
import glob

files = glob.glob('views/auth/*.php')

for filepath in files:
    with open(filepath, 'r') as f:
        content = f.read()

    content = content.replace('w * 0.6', 'w * 1.2')
    content = content.replace('h * 0.6', 'h * 1.2')
    
    with open(filepath, 'w') as f:
        f.write(content)

print("Done fixing canvas limits in auth pages.")
