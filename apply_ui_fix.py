import os
import glob
import re

directory = "android_app/app/src/main/java/net/affscash/android/ui"

for filepath in glob.glob(directory + "/**/*.kt", recursive=True):
    with open(filepath, "r") as f:
        content = f.read()
    
    modified = False

    # Fix TopBars
    if "topBar = {" in content and "primaryContainer.copy" in content:
        content = re.sub(r'color\s*=\s*MaterialTheme\.colorScheme\.primaryContainer[^,]+,', 'color = MaterialTheme.colorScheme.background,\n                shadowElevation = 2.dp,', content)
        content = re.sub(r'shape\s*=\s*.*?RoundedCornerShape\(bottomStart.*?24\.dp\)', '', content)
        content = re.sub(r',\s*\)', '\n            )', content)
        content = re.sub(r'style\s*=\s*MaterialTheme\.typography\.titleLarge,[\s\n]*fontWeight\s*=\s*FontWeight\.Bold,', 'style = PremiumUI.HeaderStyle,', content)
        content = re.sub(r'style\s*=\s*MaterialTheme\.typography\.titleLarge,[\s\n]*fontWeight\s*=\s*FontWeight\.SemiBold,', 'style = PremiumUI.HeaderStyle,', content)
        content = re.sub(r'style\s*=\s*MaterialTheme\.typography\.titleLarge,', 'style = PremiumUI.HeaderStyle,', content)
        content = re.sub(r'style\s*=\s*MaterialTheme\.typography\.headlineSmall,[\s\n]*fontWeight\s*=\s*FontWeight\.Bold,', 'style = PremiumUI.HeaderStyle,', content)
        content = re.sub(r'color\s*=\s*MaterialTheme\.colorScheme\.onPrimaryContainer', 'color = MaterialTheme.colorScheme.onSurface', content)
        content = re.sub(r'color\s*=\s*MaterialTheme\.colorScheme\.primary,(\s*modifier.*?size\(48\.dp\))', r'color = MaterialTheme.colorScheme.surfaceVariant,\1', content)
        content = re.sub(r'tint\s*=\s*MaterialTheme\.colorScheme\.onPrimary,', 'tint = MaterialTheme.colorScheme.primary,', content)
        modified = True

    # Standardize cards
    if "Card(" in content:
        content = re.sub(r'shape\s*=\s*RoundedCornerShape\((?:8|12|16)\.dp\)', 'shape = PremiumUI.CardShape', content)
        content = re.sub(r'shape\s*=\s*androidx\.compose\.foundation\.shape\.RoundedCornerShape\((?:8|12|16)\.dp\)', 'shape = PremiumUI.CardShape', content)
        content = re.sub(r'elevation\s*=\s*CardDefaults\.cardElevation\(defaultElevation\s*=\s*[0-9]+\.dp\)', 'elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)', content)
        modified = True

    # Standardize buttons
    if "Button(" in content or "OutlinedButton(" in content:
        content = re.sub(r'shape\s*=\s*RoundedCornerShape\((?:8|12|16)\.dp\)', 'shape = PremiumUI.CardShape', content)
        modified = True

    if modified:
        with open(filepath, "w") as f:
            f.write(content)
        print(f"Updated {filepath}")
