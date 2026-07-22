import re
with open('views/layouts/admin.php', 'r') as f:
    content = f.read()

bad_html = """    </a>
        </div>
</div>
</div>
<div class="sidebar-group">
    <div class="sidebar-group-header" onclick="toggleSidebarGroup(this)">
        <span>Fraud Detector</span>"""

good_html = """    </a>
    </div>
</div>
<div class="sidebar-group">
    <div class="sidebar-group-header" onclick="toggleSidebarGroup(this)">
        <span>Fraud Detector</span>"""

if bad_html in content:
    content = content.replace(bad_html, good_html)
    with open('views/layouts/admin.php', 'w') as f:
        f.write(content)
    print("Fixed extra div.")
else:
    print("Bad HTML not found.")
