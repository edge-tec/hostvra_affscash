import os

layout_dir = 'views/layouts/'
for filename in os.listdir(layout_dir):
    if filename.endswith('.php'):
        filepath = os.path.join(layout_dir, filename)
        with open(filepath, 'r') as f:
            content = f.read()

        # Fix 16px badges
        content = content.replace(
            'border-radius:50%;width:16px;height:16px;',
            'min-width:16px;height:16px;border-radius:10px;padding:0 4px;box-sizing:border-box;'
        )

        # Fix 15px badges
        content = content.replace(
            'border-radius:50%;width:15px;height:15px;',
            'min-width:15px;height:15px;border-radius:10px;padding:0 3px;box-sizing:border-box;'
        )

        with open(filepath, 'w') as f:
            f.write(content)

print("Badges in layout fixed")
