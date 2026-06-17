import re
import os

api_file = "android_app/app/src/main/java/com/example/affscash/data/network/ApiService.kt"
models_dir = "android_app/app/src/main/java/com/example/affscash/data/model"

with open(api_file, 'r') as f:
    api_content = f.read()

# Find all return types including dots
return_types = re.findall(r'suspend fun \w+\([^)]*\)\s*:\s*(?:Response<)?([\w.]+)(?:>)?', api_content)

print(f"Found {len(return_types)} return types in ApiService.")

missing = set()
for t_full in set(return_types):
    t = t_full.split('.')[-1]
    if t in ['Unit', 'ResponseBody', 'String', 'Int', 'Boolean', 'JsonObject']: continue
    
    # search for this type in models
    found_serializable = False
    for filename in os.listdir(models_dir):
        if not filename.endswith('.kt'): continue
        with open(os.path.join(models_dir, filename), 'r') as f:
            content = f.read()
            # check if it's defined here
            if f"data class {t}" in content or f"class {t}" in content:
                # check if it has @Serializable
                if f"@Serializable\ndata class {t}" in content or f"@Serializable\nclass {t}" in content:
                    found_serializable = True
                break
    if not found_serializable:
        missing.add(t)

print("Missing @Serializable for:", missing)
