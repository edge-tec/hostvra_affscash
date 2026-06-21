import os

filepath = "android_app/app/src/main/java/net/affscash/android/ui/dashboard/DashboardScreen.kt"

with open(filepath, "r") as f:
    content = f.read()

# 1. Replace TopAppBar with a custom Row
old_top_bar = """            TopAppBar(
                title = { 
                    Image(
                        painter = painterResource(id = R.drawable.logo),
                        contentDescription = "AffsCash Logo",
                        modifier = Modifier.height(32.dp)
                    )
                },
                actions = {"""
new_top_bar = """            Surface(
                modifier = Modifier.fillMaxWidth().background(PremiumUI.PastelHeader).statusBarsPadding(),
                color = Color.Transparent
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth().padding(horizontal = 12.dp, vertical = 4.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Image(
                        painter = painterResource(id = R.drawable.logo),
                        contentDescription = "AffsCash Logo",
                        modifier = Modifier.height(24.dp)
                    )
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {"""

content = content.replace(old_top_bar, new_top_bar)

old_top_bar_end = """                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = Color.Transparent,
                    titleContentColor = Color.White,
                    navigationIconContentColor = Color.White,
                    actionIconContentColor = Color.White
                ),
                modifier = Modifier.background(PremiumUI.PastelHeader)
            )"""
new_top_bar_end = """                    }
                }
            }"""
content = content.replace(old_top_bar_end, new_top_bar_end)

# 2. LazyColumn padding
content = content.replace("contentPadding = PaddingValues(16.dp)", "contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp)")

# 3. Spacer heights
content = content.replace("Spacer(modifier = Modifier.height(24.dp))", "Spacer(modifier = Modifier.height(16.dp))")
content = content.replace("Spacer(modifier = Modifier.height(16.dp))\n                        }", "Spacer(modifier = Modifier.height(8.dp))\n                        }")

# 4. PeriodTabs padding
content = content.replace("modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)", "modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp), fontSize = 12.sp")

# 5. KpiCard padding
content = content.replace("Column(modifier = Modifier.padding(16.dp)) {", "Column(modifier = Modifier.padding(12.dp)) {")
content = content.replace("style = MaterialTheme.typography.titleLarge", "style = MaterialTheme.typography.titleMedium")

# 6. Chart heights
content = content.replace("height(250.dp)", "height(180.dp)")
content = content.replace("height(200.dp)", "height(160.dp)")

# 7. HeaderIconWithBadge sizes
content = content.replace("size(36.dp)", "size(30.dp)")
content = content.replace("offset(x = 6.dp, y = (-6).dp)", "offset(x = 4.dp, y = (-4).dp)")

with open(filepath, "w") as f:
    f.write(content)
print("Dashboard updated successfully!")
