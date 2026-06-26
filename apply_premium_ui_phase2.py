import os
import glob
import re

directory = "android_app/app/src/main/java/net/affscash/android/ui"

# The old bulky header to match
header_pattern_1 = re.compile(
    r"""Surface\(\s*modifier = Modifier\.fillMaxWidth\(\),\s*color = MaterialTheme\.colorScheme\.primaryContainer\.copy\(alpha = 0\.6f\),\s*shape = androidx\.compose\.foundation\.shape\.RoundedCornerShape\(bottomStart = 24\.dp, bottomEnd = 24\.dp\)\s*\)\s*\{\s*Box\(modifier = Modifier\.fillMaxWidth\(\)\.windowInsetsPadding\(WindowInsets\.safeDrawing\.only\(WindowInsetsSides\.Top\)\)\)\s*\{\s*Row\(\s*verticalAlignment = Alignment\.CenterVertically,\s*modifier = Modifier\s*\.fillMaxWidth\(\)\s*\.padding\(start = 16\.dp, end = 16\.dp, top = 8\.dp, bottom = 8\.dp\)\s*\)\s*\{\s*(.*?)\s*\}\s*\}\s*\}""",
    re.DOTALL
)

header_pattern_2 = re.compile(
    r"""Surface\(\s*modifier = Modifier\.fillMaxWidth\(\),\s*color = MaterialTheme\.colorScheme\.primaryContainer\.copy\(alpha = 0\.6f\),\s*shape = androidx\.compose\.foundation\.shape\.RoundedCornerShape\(bottomStart = 24\.dp, bottomEnd = 24\.dp\)\s*\)\s*\{\s*Row\(\s*verticalAlignment = Alignment\.CenterVertically,\s*modifier = Modifier\s*\.fillMaxWidth\(\)\s*\.windowInsetsPadding\(WindowInsets\.safeDrawing\.only\(WindowInsetsSides\.Top\)\)\s*\.padding\(start = 16\.dp, end = 16\.dp, top = 8\.dp, bottom = 8\.dp\)\s*\)\s*\{\s*(.*?)\s*\}\s*\}""",
    re.DOTALL
)

def replace_header(match):
    inner_content = match.group(1)
    
    # Replace typography
    inner_content = re.sub(r'style = MaterialTheme\.typography\.titleLarge,[\s\n]*fontWeight = FontWeight\.Bold,', r'style = PremiumUI.HeaderStyle,', inner_content)
    inner_content = re.sub(r'style = MaterialTheme\.typography\.titleLarge,[\s\n]*fontWeight = FontWeight\.SemiBold,', r'style = PremiumUI.HeaderStyle,', inner_content)
    inner_content = re.sub(r'style = MaterialTheme\.typography\.titleLarge,', r'style = PremiumUI.HeaderStyle,', inner_content)
    
    return f"""Surface(
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.background,
                shadowElevation = 2.dp
            ) {{
                Box(modifier = Modifier.fillMaxWidth().windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {{
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp, vertical = 12.dp)
                    ) {{
                        {inner_content}
                    }}
                }}
            }}"""


for filepath in glob.glob(directory + "/**/*.kt", recursive=True):
    with open(filepath, "r") as f:
        content = f.read()
    
    modified = False
    
    # 1. Add PremiumUI import
    if "import net.affscash.android.ui.dashboard.PremiumUI" not in content and ("PremiumUI" in content or "topBar = {" in content):
        if "import net.affscash.android" in content:
            content = re.sub(r'(import net\.affscash\.android\.[^\n]+)', r'\1\nimport net.affscash.android.ui.dashboard.PremiumUI', content, count=1)
            modified = True
            
    # 2. Replace bulky header
    new_content = header_pattern_1.sub(replace_header, content)
    if new_content != content:
        content = new_content
        modified = True

    new_content = header_pattern_2.sub(replace_header, content)
    if new_content != content:
        content = new_content
        modified = True
        
    # 3. Add PageBackground
    bg_replace1 = "Column(modifier = Modifier.padding(paddingValues).fillMaxSize()) {"
    bg_replace2 = "Column(modifier = Modifier.padding(paddingValues).fillMaxSize().background(PremiumUI.PageBackground)) {"
    if bg_replace1 in content:
        content = content.replace(bg_replace1, bg_replace2)
        modified = True
        
    bg_replace3 = "LazyColumn(modifier = Modifier.padding(paddingValues).fillMaxSize()) {"
    bg_replace4 = "LazyColumn(modifier = Modifier.padding(paddingValues).fillMaxSize().background(PremiumUI.PageBackground)) {"
    if bg_replace3 in content:
        content = content.replace(bg_replace3, bg_replace4)
        modified = True
        
    # 4. Remove forced height from OutlinedTextField searches
    search_replace1 = "modifier = Modifier.fillMaxWidth().height(52.dp),"
    search_replace2 = "modifier = Modifier.fillMaxWidth(),"
    if search_replace1 in content and "OutlinedTextField" in content:
        content = content.replace(search_replace1, search_replace2)
        modified = True
        
    if modified:
        with open(filepath, "w") as f:
            f.write(content)
        print(f"Updated {filepath}")
