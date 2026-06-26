import re

with open("app/src/main/java/net/affscash/android/service/MyFirebaseMessagingService.kt", "r") as f:
    content = f.read()

# Replace requestCode logic
replacement_request_code = """        val requestCode = (System.currentTimeMillis() % Integer.MAX_VALUE).toInt()
        val notificationIdInt = requestCode"""

content = re.sub(r"val requestCode = System\.currentTimeMillis\(\)\.toInt\(\)", replacement_request_code, content)

# Replace notify logic with try-catch and positive ID
replacement_notify = """        try {
            notificationManager.notify(notificationIdInt, notificationBuilder.build())
            Log.d(TAG, "Notification delivered to system manager. id=$notificationIdInt")
        } catch (e: Exception) {
            Log.e(TAG, "FATAL ERROR posting notification to system: ${e.message}", e)
        }"""

content = re.sub(r"notificationManager\.notify\(requestCode, notificationBuilder\.build\(\)\)\n\s*Log\.d\(TAG, \"Notification delivered to system manager\. requestCode=\$requestCode\"\)", replacement_notify, content)

with open("app/src/main/java/net/affscash/android/service/MyFirebaseMessagingService.kt", "w") as f:
    f.write(content)
