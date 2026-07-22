import re

with open('views/layouts/admin.php', 'r') as f:
    content = f.read()

# Add CSS
css = """
/* ── Sidebar Accordion ───────────────────────────────────────── */
.sidebar-group { margin-bottom: 2px; }
.sidebar-group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    font-size: 11px;
    font-weight: 700;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: .05em;
    cursor: pointer;
    border-radius: 6px;
    transition: background .2s, color .2s;
    margin: 4px 0 2px 0;
}
.sidebar-group-header:hover { background: #F1F5F9; color: #334155; }
.sidebar-group-header svg { width: 14px; height: 14px; transition: transform .2s; }
.sidebar-group.open .sidebar-group-header svg { transform: rotate(180deg); }
.sidebar-group-items { display: none; }
.sidebar-group.open .sidebar-group-items { display: block; }
"""
content = content.replace("/* ── In-House Fraud Detection Sidebar Module ───────────────────────── */", css + "\n/* ── In-House Fraud Detection Sidebar Module ───────────────────────── */")

# Add JS at the end of the file before </body>
js = """
<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.sidebar-group').forEach(function(group) {
        if (group.querySelector('.nav-link.active')) {
            group.classList.add('open');
        }
    });
});
function toggleSidebarGroup(el) {
    el.parentElement.classList.toggle('open');
}
</script>
"""
if "toggleSidebarGroup" not in content:
    content = content.replace("</body>", js + "\n</body>")

# Regex to find <p class="sidebar-section">Name</p> and everything until the next one
# We will do this manually by splitting the content around the sidebar area
start_marker = '<p class="sidebar-section">Main</p>'
end_marker = '<?php\n    // ── In-House Fraud Detection System'

if start_marker in content and end_marker in content:
    pre = content[:content.find(start_marker)]
    mid = content[content.find(start_marker):content.find(end_marker)]
    post = content[content.find(end_marker):]

    # Process mid
    # Split by <p class="sidebar-section">
    parts = mid.split('<p class="sidebar-section">')
    new_mid = parts[0]
    
    for part in parts[1:]:
        name_end = part.find('</p>')
        name = part[:name_end].strip()
        rest = part[name_end+4:].strip()
        
        group_html = f"""<div class="sidebar-group">
    <div class="sidebar-group-header" onclick="toggleSidebarGroup(this)">
        <span>{name}</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
    </div>
    <div class="sidebar-group-items">
        {rest}
    </div>
</div>
"""
        new_mid += group_html + "\n"
        
    with open('views/layouts/admin.php', 'w') as f:
        f.write(pre + new_mid + post)
    print("Successfully refactored sidebar.")
else:
    print("Could not find markers.")
