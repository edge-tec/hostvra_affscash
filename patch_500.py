import os
import re

base = "/Users/mizanurrahman/claude/affscash script/Affscash.net  latest script/project aapanel"

def fix_controller(path):
    with open(path, 'r') as f:
        content = f.read()
    
    # Catch SQL errors and try to add missing columns
    header = """
try {
    Database::query("ALTER TABLE stats_daily ADD COLUMN IF NOT EXISTS revenue DECIMAL(12,4) DEFAULT 0.0000");
    Database::query("ALTER TABLE stats_daily ADD COLUMN IF NOT EXISTS payout DECIMAL(12,4) DEFAULT 0.0000");
} catch(\\Throwable $e) {}
try {
    Database::query("ALTER TABLE conversions ADD COLUMN IF NOT EXISTS fraud_score INT DEFAULT 0");
} catch(\\Throwable $e) {}
"""
    if "ALTER TABLE stats_daily" not in content:
        content = content.replace("<?php", "<?php\n" + header)

    # Change http_response_code(500) to 200 so Android app shows the error instead of crashing
    content = content.replace("http_response_code(500);", "http_response_code(200);")
    
    with open(path, 'w') as f:
        f.write(content)

fix_controller(f"{base}/controllers/api/AffiliateAnalyticsController.php")
fix_controller(f"{base}/api/v2/admin/DashboardController.php")
fix_controller(f"{base}/api/v2/manager/DashboardController.php")
print("Done!")
