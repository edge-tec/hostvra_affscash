import re
with open('views/layouts/admin.php', 'r') as f:
    content = f.read()

# We need to replace:
#     </div>
# </div>
#
#     </div>
# </div>
# <div class="sidebar-group">
#     <div class="sidebar-group-header" onclick="toggleSidebarGroup(this)">
#         <span>Reports</span>

bad_html = """    </a>
    </div>
</div>

    </div>
</div>
<div class="sidebar-group">"""

good_html = """    </a>
    </div>
</div>
<div class="sidebar-group">"""

if bad_html in content:
    content = content.replace(bad_html, good_html)
    with open('views/layouts/admin.php', 'w') as f:
        f.write(content)
    print("Fixed extra closing tags.")
else:
    print("Bad HTML not found.")
