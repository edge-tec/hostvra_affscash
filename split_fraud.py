import re

with open('views/layouts/admin.php', 'r') as f:
    content = f.read()

# We need to extract:
fraud_links = [
    '<a href="/admin/fraud"',
    '<a href="/admin/fraud-score-report"',
    '<a href="/admin/fraud-alerts"'
]

# We will locate the tracking group
track_marker = '<span>Tracking & Postbacks</span>'
if track_marker in content:
    start_idx = content.find(track_marker)
    # The group ends with </div>\n</div>
    # But it's easier to just split out the links from the whole tracking block
    end_idx = content.find('<div class="sidebar-group">', start_idx)
    
    tracking_block = content[start_idx:end_idx]
    
    # We will remove the fraud links from tracking_block and put them in a new block
    parts = tracking_block.split('<a href="')
    
    new_tracking = parts[0]
    fraud_content = ""
    
    for p in parts[1:]:
        link = '<a href="' + p
        is_fraud = False
        for fl in fraud_links:
            if link.startswith(fl):
                is_fraud = True
                break
        
        if is_fraud:
            fraud_content += link
        else:
            new_tracking += link
            
    # Now create the new Fraud block
    fraud_group = f"""<div class="sidebar-group">
    <div class="sidebar-group-header" onclick="toggleSidebarGroup(this)">
        <span>Fraud Detector</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
    </div>
    <div class="sidebar-group-items">
        {fraud_content}    </div>
</div>
"""

    new_content = content[:start_idx] + new_tracking + "</div>\n" + fraud_group + content[end_idx:]
    
    with open('views/layouts/admin.php', 'w') as f:
        f.write(new_content)
    print("Successfully separated Fraud Detector links.")
else:
    print("Tracking group not found.")
