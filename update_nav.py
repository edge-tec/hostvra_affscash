import os

filepath = "android_app/app/src/main/java/net/affscash/android/ui/navigation/MainScreen.kt"
with open(filepath, "r") as f:
    content = f.read()

old_bottom_bar = """        bottomBar = {
            Box(modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp)) {
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    shape = androidx.compose.foundation.shape.RoundedCornerShape(24.dp),
                    color = MaterialTheme.colorScheme.surface,
                    shadowElevation = 8.dp
                ) {
                    NavigationBar(
                        containerColor = androidx.compose.ui.graphics.Color.Transparent,
                        tonalElevation = 0.dp,
                        modifier = Modifier.height(56.dp)
                    ) {"""

new_bottom_bar = """        bottomBar = {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .navigationBarsPadding()
                    .padding(start = 16.dp, end = 16.dp, bottom = 12.dp)
            ) {
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    shape = androidx.compose.foundation.shape.RoundedCornerShape(32.dp),
                    color = MaterialTheme.colorScheme.surface.copy(alpha = 0.9f),
                    shadowElevation = 4.dp
                ) {
                    NavigationBar(
                        containerColor = androidx.compose.ui.graphics.Color.Transparent,
                        tonalElevation = 0.dp,
                        modifier = Modifier.height(54.dp),
                        windowInsets = WindowInsets(0.dp)
                    ) {"""

content = content.replace(old_bottom_bar, new_bottom_bar)

# Update icon and label size
content = content.replace("modifier = Modifier.size(if (isSelected) 24.dp else 20.dp)", "modifier = Modifier.size(if (isSelected) 22.dp else 20.dp)")
content = content.replace("modifier = Modifier.size(if (isSelected) 20.dp else 18.dp)", "modifier = Modifier.size(if (isSelected) 22.dp else 20.dp)")
content = content.replace("fontSize = androidx.compose.ui.unit.TextUnit(9f, androidx.compose.ui.unit.TextUnitType.Sp)", "fontSize = androidx.compose.ui.unit.TextUnit(10f, androidx.compose.ui.unit.TextUnitType.Sp)")
content = content.replace("indicatorColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.6f)", "indicatorColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.4f)")

with open(filepath, "w") as f:
    f.write(content)
print("Updated bottom bar!")
