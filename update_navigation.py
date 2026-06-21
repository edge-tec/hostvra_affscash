import os

filepath = "android_app/app/src/main/java/net/affscash/android/ui/navigation/MainScreen.kt"
with open(filepath, "r") as f:
    content = f.read()

# Reduce NavigationBar padding and height
content = content.replace(
    "bottomBar = {\n            Box(modifier = Modifier.padding(16.dp)) {",
    "bottomBar = {\n            Box(modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp)) {"
)

content = content.replace(
    "modifier = Modifier.height(72.dp)",
    "modifier = Modifier.height(56.dp)"
)

# Reduce icon sizes
content = content.replace(
    "modifier = Modifier.size(if (isSelected) 32.dp else 24.dp)",
    "modifier = Modifier.size(if (isSelected) 24.dp else 20.dp)"
)

content = content.replace(
    "modifier = Modifier.size(if (isSelected) 24.dp else 22.dp)",
    "modifier = Modifier.size(if (isSelected) 20.dp else 18.dp)"
)

# Reduce text sizes
content = content.replace(
    "fontSize = androidx.compose.ui.unit.TextUnit(11f, androidx.compose.ui.unit.TextUnitType.Sp)",
    "fontSize = androidx.compose.ui.unit.TextUnit(9f, androidx.compose.ui.unit.TextUnitType.Sp)"
)

with open(filepath, "w") as f:
    f.write(content)
print("Updated MainScreen.kt")
