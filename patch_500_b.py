import os

base = "/Users/mizanurrahman/claude/affscash script/Affscash.net  latest script/project aapanel"

def fix_controller(path):
    with open(path, 'r') as f:
        content = f.read()
    
    # Remove old IF NOT EXISTS
    content = content.replace("ADD COLUMN IF NOT EXISTS", "ADD COLUMN")
    
    with open(path, 'w') as f:
        f.write(content)

fix_controller(f"{base}/controllers/api/AffiliateAnalyticsController.php")
fix_controller(f"{base}/api/v2/admin/DashboardController.php")
fix_controller(f"{base}/api/v2/manager/DashboardController.php")
print("Done!")
