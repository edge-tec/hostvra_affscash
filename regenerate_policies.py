import os
import re

dirs = [
    "/Users/mizanurrahman/claude/edgecash script/affscash2",
    "/Users/mizanurrahman/claude/affscash script/Affscash.net  latest script/project aapanel"
]

template_path = "/Users/mizanurrahman/claude/affscash script/Affscash.net  latest script/project aapanel/cookie-policy.php"
with open(template_path, "r", encoding="utf-8") as f:
    template_content = f.read()

# Extract top part (up to <main class="tos-content">), notice block, and footer
top_match = re.search(r'(.*?)<main class="tos-content">', template_content, re.DOTALL)
if not top_match:
    print("Could not find top part in template.")
    exit(1)
top_template = top_match.group(1)

footer_match = re.search(r'(</main>\s*</div>\s*<!-- ── FOOTER ── -->.*)', template_content, re.DOTALL)
if not footer_match:
    print("Could not find footer in template.")
    exit(1)
footer_template = footer_match.group(1)

# Notice block
notice_match = re.search(r'(<div class="notice-block">.*?</div>)', template_content, re.DOTALL)
notice_template = notice_match.group(1) if notice_match else ""

# Helper to extract main content and TOC items from an existing policy
def parse_existing_policy(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Extract TOC list
    toc_match = re.search(r'<ul class="toc-list">(.*?)</ul>', content, re.DOTALL)
    toc_items = toc_match.group(1) if toc_match else ""
    
    # Extract Title from Hero
    title_match = re.search(r'<h1>(.*?)</h1>', content)
    title = title_match.group(1) if title_match else "Policy"
    
    # Extract notice block contents
    notice_content_match = re.search(r'<div class="notice-block">(.*?)</div>', content, re.DOTALL)
    notice_content = notice_content_match.group(1) if notice_content_match else ""
    
    # Extract all tos-sections
    sections = re.findall(r'(<section class="tos-section.*?>(?:.*?)</section>)', content, re.DOTALL)
    
    return title, toc_items, notice_content, sections

files_to_update = [
    "affiliate-agreement.php",
    "anti-fraud-policy.php",
    "gdpr-compliance-policy.php",
    "refund-payment-policy.php",
    "cookie-policy.php",
    "dashboard-disclaimers.php"
]

for d in dirs:
    for filename in files_to_update:
        filepath = os.path.join(d, filename)
        if not os.path.exists(filepath):
            continue
            
        print(f"Updating {filepath}")
        title, toc_items, notice_content, sections = parse_existing_policy(filepath)
        
        # Build new top part
        new_top = top_template
        # Replace title
        new_top = re.sub(r'<title>.*?— Affscash</title>', f'<title>{title} — Affscash</title>', new_top)
        new_top = re.sub(r'<h1>.*?</h1>', f'<h1>{title}</h1>', new_top)
        # Replace TOC
        new_top = re.sub(r'<ul class="toc-list">.*?</ul>', f'<ul class="toc-list">{toc_items}</ul>', new_top, flags=re.DOTALL)
        
        # Build main content
        main_content = '\n<main class="tos-content">\n\n'
        if notice_content:
            main_content += f'<div class="notice-block">\n{notice_content}\n</div>\n\n'
            
        for sec in sections:
            # Check if section needs 'is-open' - let's make only section-1 open
            if 'id="section-1"' in sec and 'is-open' not in sec:
                sec = sec.replace('class="tos-section"', 'class="tos-section is-open"')
            elif 'id="section-1"' not in sec and 'is-open' in sec:
                sec = sec.replace('class="tos-section is-open"', 'class="tos-section"')
                
            main_content += sec + '\n\n'
            
        # Active link in footer
        new_footer = footer_template
        new_footer = re.sub(r'class="active-link"', '', new_footer) # remove all active
        active_href = f'href="/{filename.replace(".php", "")}"'
        new_footer = new_footer.replace(active_href, f'{active_href} class="active-link"')
        
        new_file_content = new_top + main_content + new_footer
        
        with open(filepath, "w", encoding="utf-8") as f:
            f.write(new_file_content)

print("Done updating all policies.")
