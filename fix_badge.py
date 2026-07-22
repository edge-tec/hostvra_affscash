import re

files = [
    'assets/css/app.min.css',
    'assets/css/app.css'
]

for file in files:
    with open(file, 'r') as f:
        content = f.read()

    # match minified
    content = content.replace(
        'width:16px;height:16px;border-radius:50%;',
        'min-width:16px;height:16px;padding:0 4px;box-sizing:border-box;border-radius:10px;'
    )

    # match unminified (with potential spaces)
    content = re.sub(
        r'width:\s*16px;\s*height:\s*16px;\s*border-radius:\s*50%;',
        'min-width: 16px; height: 16px; padding: 0 4px; box-sizing: border-box; border-radius: 10px;',
        content
    )

    with open(file, 'w') as f:
        f.write(content)

print("Done")
