import os
import re

dirs = [
    "/Users/mizanurrahman/claude/edgecash script/affscash2",
    "/Users/mizanurrahman/claude/affscash script/Affscash.net  latest script/project aapanel"
]

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
            
        with open(filepath, "r", encoding="utf-8") as f:
            content = f.read()
            
        content = content.replace("Edgecash", "Affscash")
        content = content.replace("EdgeCash", "AffsCash")
        
        with open(filepath, "w", encoding="utf-8") as f:
            f.write(content)

print("Done replacing Edgecash to Affscash.")
