import re

with open("app/src/main/AndroidManifest.xml", "r") as f:
    content = f.read()

content = content.replace("@drawable/ic_notification", "@mipmap/ic_launcher")

with open("app/src/main/AndroidManifest.xml", "w") as f:
    f.write(content)
