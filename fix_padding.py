import os

files_to_check = [
    "android_app/app/src/main/java/net/affscash/android/ui/offers/OfferScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/offers/AffiliateInHouseOffersScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/smartlinks/SmartlinkScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/admin/AdminDashboardScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerFraudReportsScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/referral/ManagerReferralScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerAffiliatesScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerAffiliateDetailsScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerDuplicateConversionsScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerDashboardScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerInvoicesScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerEditAffiliateScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerOfferApprovalsScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerCreateAffiliateScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerReportsScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerProfileScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/manager/ManagerSmartlinkRequestsScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/reports/ReportScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/navigation/MainScreen.kt"
]

imports_to_add = """
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.WindowInsetsSides
import androidx.compose.foundation.layout.only
import androidx.compose.foundation.layout.safeDrawing
import androidx.compose.foundation.layout.windowInsetsPadding
"""

for file_path in files_to_check:
    if not os.path.exists(file_path):
        continue
        
    with open(file_path, "r") as f:
        content = f.read()
        
    if "statusBarsPadding()" not in content:
        continue
        
    new_content = content.replace("statusBarsPadding()", "windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))")
    
    # Add imports after package declaration
    if "import androidx.compose.foundation.layout.WindowInsets" not in new_content:
        lines = new_content.split('\n')
        for i, line in enumerate(lines):
            if line.startswith("package "):
                lines.insert(i + 1, imports_to_add)
                break
        new_content = '\n'.join(lines)
        
    with open(file_path, "w") as f:
        f.write(new_content)
        
print("Replacement completed.")
