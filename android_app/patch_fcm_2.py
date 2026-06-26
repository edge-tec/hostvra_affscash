import re

with open("app/src/main/java/net/affscash/android/service/MyFirebaseMessagingService.kt", "r") as f:
    content = f.read()

# Replace ActivityCompat with ContextCompat
content = content.replace("androidx.core.app.ActivityCompat.checkSelfPermission", "androidx.core.content.ContextCompat.checkSelfPermission")

with open("app/src/main/java/net/affscash/android/service/MyFirebaseMessagingService.kt", "w") as f:
    f.write(content)
