import os
import re

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
    "android_app/app/src/main/java/net/affscash/android/ui/navigation/MainScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/components/CompactTopBar.kt"
]

def process_file(file_path):
    if not os.path.exists(file_path):
        return
        
    with open(file_path, "r") as f:
        content = f.read()

    # Wait, did we use .windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))?
    padding_str = ".windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))"
    
    if padding_str not in content:
        return
        
    print(f"Processing: {file_path}")
    
    # 1. MainScreen.kt
    if "MainScreen.kt" in file_path:
        content = content.replace("modifier = Modifier.fillMaxWidth().windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))", "modifier = Modifier.fillMaxWidth()")
        content = content.replace("Row(\n                        modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 8.dp),", "Box(modifier = Modifier.windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {\n                    Row(\n                        modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 8.dp),")
        # Find closing brace for Row and add one for Box. We can just add it manually with multi_replace_file_content if it's too hard to script.
        # Wait, let's just write the changes back and then print instructions.
        with open(file_path, "w") as f:
            f.write(content)
        return
        
    # 2. AdminDashboardScreen.kt
    if "AdminDashboardScreen.kt" in file_path:
        # It's a Box, not a Surface.
        # Box(modifier = Modifier.fillMaxSize().background(PremiumUI.BackgroundGradient).windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {
        # This is actually correct for AdminDashboardScreen IF we want the padding inside the background!
        # Wait, if `.background()` is before `.windowInsetsPadding()`, the background DOES cover the status bar!
        # So AdminDashboardScreen might already be correct!
        return
        
    # 3. CompactTopBar.kt
    if "CompactTopBar.kt" in file_path:
        # In CompactTopBar, we used Spacer. It is already correct!
        return

    # 4. ManagerDashboardScreen.kt
    if "ManagerDashboardScreen.kt" in file_path:
        # Same as others, but has background
        content = content.replace(").windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))", ")")
        content = content.replace("Column(\n                    modifier = Modifier", "Box(modifier = Modifier.windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {\n                Column(\n                    modifier = Modifier")
        with open(file_path, "w") as f:
            f.write(content)
        return

    # All others (OfferScreen, Reports, Affiliates, etc.)
    # They have:
    # modifier = Modifier.fillMaxWidth().windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top)),
    
    content = content.replace("modifier = Modifier.fillMaxWidth().windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top)),", "modifier = Modifier.fillMaxWidth(),")
    content = content.replace(") {\n                Row(", ") {\n                Box(modifier = Modifier.fillMaxWidth().windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {\n                    Row(")
    content = content.replace(") {\n                Column(", ") {\n                Box(modifier = Modifier.fillMaxWidth().windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {\n                    Column(")
    
    with open(file_path, "w") as f:
        f.write(content)

for file_path in files_to_check:
    process_file(file_path)

print("Done. Now I need to add the closing braces `}` to all the Boxes.")
