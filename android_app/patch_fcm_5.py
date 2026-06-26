import re

with open("app/src/main/java/net/affscash/android/service/MyFirebaseMessagingService.kt", "r") as f:
    content = f.read()

# Replace ic_notification with ic_launcher_round or ic_launcher
content = content.replace("R.drawable.ic_notification", "R.mipmap.ic_launcher")

with open("app/src/main/java/net/affscash/android/service/MyFirebaseMessagingService.kt", "w") as f:
    f.write(content)
