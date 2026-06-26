import re

with open("app/src/main/java/net/affscash/android/service/MyFirebaseMessagingService.kt", "r") as f:
    content = f.read()

# Remove setGroup and showGroupSummary calls
content = re.sub(r"\s*// Group notifications to avoid flooding the notification shade\s*\.setGroup\(GROUP_KEY\)", "", content)
content = re.sub(r"\s*// Show summary notification for grouping\s*showGroupSummary\(notificationManager\)", "", content)
content = re.sub(r"\s*private fun showGroupSummary\(.*?\)\s*\{.*?\n    \}", "", content, flags=re.DOTALL)
content = re.sub(r"\s*private const val SUMMARY_NOTIFICATION_ID = 0", "", content)

with open("app/src/main/java/net/affscash/android/service/MyFirebaseMessagingService.kt", "w") as f:
    f.write(content)
