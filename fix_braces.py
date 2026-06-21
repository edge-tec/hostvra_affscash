import os
import sys

files_to_check = [
    "android_app/app/src/main/java/net/affscash/android/ui/offers/OfferScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/offers/AffiliateInHouseOffersScreen.kt",
    "android_app/app/src/main/java/net/affscash/android/ui/smartlinks/SmartlinkScreen.kt",
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

def add_closing_brace(file_path):
    if not os.path.exists(file_path):
        return
        
    with open(file_path, "r") as f:
        content = f.read()
        
    # We look for "Box(modifier = Modifier.windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {"
    # Or "Box(modifier = Modifier.fillMaxWidth().windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {"
    target_1 = "Box(modifier = Modifier.windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {"
    target_2 = "Box(modifier = Modifier.fillMaxWidth().windowInsetsPadding(WindowInsets.safeDrawing.only(WindowInsetsSides.Top))) {"
    
    idx = content.find(target_1)
    if idx == -1:
        idx = content.find(target_2)
        if idx == -1:
            return
            
    # We found the Box. Now we need to find the matching closing brace for the NEXT block (Row or Column)
    # Actually, the Box itself has an opening brace `{`.
    # Let's find the matching closing brace for THIS Box!
    # The Box opening brace is the last character of the target string.
    brace_start = idx + (len(target_1) if content[idx:idx+len(target_1)] == target_1 else len(target_2)) - 1
    
    # We need to find the matching '}' for brace_start
    depth = 0
    match_end = -1
    for i in range(brace_start, len(content)):
        if content[i] == '{':
            depth += 1
        elif content[i] == '}':
            depth -= 1
            if depth == 0:
                match_end = i
                break
                
    if match_end != -1:
        print(f"File {file_path} already has a matching closing brace. WAIT!")
        # If we just inserted it, we didn't add a closing brace. So the '{' we added caused the tree to be unbalanced.
        # This means the current matching '}' is actually the one that belonged to the PARENT `Surface`!!
        # So we must insert `}` right BEFORE `match_end`.
        
        new_content = content[:match_end] + "                }\n" + content[match_end:]
        with open(file_path, "w") as f:
            f.write(new_content)
        print(f"Fixed {file_path}")

for file_path in files_to_check:
    add_closing_brace(file_path)

