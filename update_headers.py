import os
import glob

directory = "android_app/app/src/main/java/net/affscash/android/ui"

for filepath in glob.glob(directory + "/**/*.kt", recursive=True):
    with open(filepath, "r") as f:
        content = f.read()
    
    if "top = 28.dp, bottom = 10.dp" in content:
        # replace the padding
        content = content.replace("top = 28.dp, bottom = 10.dp", "top = 8.dp, bottom = 8.dp")
        
        # add statusBarsPadding() to the Surface modifier if not already there
        # We look for "modifier = Modifier.fillMaxWidth()," inside the Surface definition
        # It usually precedes the Row.
        # But maybe we can just replace "modifier = Modifier.fillMaxWidth()," with "modifier = Modifier.fillMaxWidth().statusBarsPadding(),"
        # But we must be careful not to replace it everywhere in the file, only the one for Surface.
        
        # Actually, if we just do:
        content = content.replace("modifier = Modifier.fillMaxWidth(),\n                color = MaterialTheme.colorScheme.primaryContainer", "modifier = Modifier.fillMaxWidth().statusBarsPadding(),\n                color = MaterialTheme.colorScheme.primaryContainer")
        
        with open(filepath, "w") as f:
            f.write(content)
        print(f"Updated {filepath}")
