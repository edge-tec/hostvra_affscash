import re

with open('views/layouts/admin.php', 'r') as f:
    content = f.read()

# Find the Tracking group
track_start_marker = '<div class="sidebar-group-header" onclick="toggleSidebarGroup(this)">\n        <span>Tracking</span>'
# Find the start of Finance group
finance_start_marker = '<div class="sidebar-group-header" onclick="toggleSidebarGroup(this)">\n        <span>Finance</span>'

if track_start_marker in content and finance_start_marker in content:
    pre = content[:content.find(track_start_marker)]
    # the whole Tracking group div actually starts before the header
    # Let's find the <div class="sidebar-group"> right before Tracking
    track_group_start = content.rfind('<div class="sidebar-group">', 0, content.find(track_start_marker))
    pre = content[:track_group_start]
    
    finance_group_start = content.rfind('<div class="sidebar-group">', 0, content.find(finance_start_marker))
    post = content[finance_group_start:]
    
    mid = content[track_group_start:finance_group_start]
    
    # Extract all <a> tags from mid
    # We can split by '<a href=' and reconstruct
    parts = mid.split('<a href="')
    
    # parts[0] contains the group header HTML, we don't need it as we will recreate it
    links = []
    for p in parts[1:]:
        link_html = '<a href="' + p
        links.append(link_html)
        
    conversions = []
    clicks = []
    reports = []
    tracking = []
    
    for l in links:
        if 'href="/admin/conversions"' in l or 'href="/admin/rejection-reasons"' in l or 'href="/admin/reports/duplicate-conversions"' in l:
            conversions.append(l)
        elif 'href="/admin/reports/clicks"' in l or 'href="/admin/traffic"' in l or 'href="/admin/reports/traffic-back"' in l or 'href="/admin/reports/traffic-source-override"' in l or 'href="/admin/traffic-source-override"' in l:
            clicks.append(l)
        elif 'href="/admin/reports"' in l and 'href="/admin/reports/' not in l:
            reports.append(l)
        elif 'href="/admin/reports/affiliates"' in l:
            reports.append(l)
        else:
            tracking.append(l)
            
    def make_group(title, links_arr):
        if not links_arr: return ""
        inner = "".join(links_arr)
        return f"""<div class="sidebar-group">
    <div class="sidebar-group-header" onclick="toggleSidebarGroup(this)">
        <span>{title}</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
    </div>
    <div class="sidebar-group-items">
        {inner}    </div>
</div>
"""

    new_mid = make_group("Tracking & Postbacks", tracking)
    new_mid += make_group("Conversions", conversions)
    new_mid += make_group("Clicks & Traffic", clicks)
    new_mid += make_group("Reports", reports)
    
    with open('views/layouts/admin.php', 'w') as f:
        f.write(pre + new_mid + post)
    print("Successfully restructured sidebar.")
else:
    print("Markers not found.")
