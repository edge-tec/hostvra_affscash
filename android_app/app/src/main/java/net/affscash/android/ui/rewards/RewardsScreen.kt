package net.affscash.android.ui.rewards

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.CheckCircle
import java.util.Locale
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.text.HtmlCompat
import androidx.core.graphics.toColorInt
import android.widget.TextView
import androidx.hilt.navigation.compose.hiltViewModel
import coil.compose.AsyncImage
import coil.compose.SubcomposeAsyncImage
import coil.imageLoader
import coil.request.ImageRequest
import androidx.compose.ui.platform.LocalContext
import android.net.Uri
import androidx.compose.material.icons.filled.Image
import net.affscash.android.data.model.RewardRule
import net.affscash.android.ui.dashboard.PremiumUI
import net.affscash.android.data.model.RewardsResponse
import net.affscash.android.theme.Purple40
import net.affscash.android.utils.CoilImageGetter

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RewardsScreen(
    onNavigateBack: () -> Unit,
    viewModel: RewardsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            net.affscash.android.ui.components.CompactTopBar(
                title = { Text("My Rewards") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = Purple40,
                    titleContentColor = Color.White,
                    navigationIconContentColor = Color.White
                )
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .background(Color(0xFFF5F6FA))
        ) {
            when (val state = uiState) {
                is RewardsState.Loading -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator()
                    }
                }
                is RewardsState.Error -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text(text = state.message, color = Color.Red)
                            Spacer(modifier = Modifier.height(4.dp))
                            Button(onClick = { viewModel.loadData() }) {
                                Text("Retry")
                            }
                        }
                    }
                }
                is RewardsState.Success -> {
                    RewardsContent(state.data)
                }
            }
        }
    }
}

@Composable
fun RewardsContent(data: RewardsResponse) {
    LazyColumn(
        modifier = Modifier.padding(8.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        // Next Milestone
        if (data.nextMilestone?.rule != null) {
            item {
                NextMilestoneCard(
                    rule = data.nextMilestone.rule,
                    earned = data.nextMilestone.earned
                )
            }
        }

        // Earned Rewards
        if (data.earnedRewards.isNotEmpty()) {
            item {
                Text(
                    text = "Earned Rewards",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(bottom = 8.dp)
                )
            }
            items(data.earnedRewards) { grant ->
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                    colors = CardDefaults.cardColors(containerColor = Color.White)
                ) {
                    Row(
                        modifier = Modifier.padding(8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(Icons.Default.CheckCircle, contentDescription = "Earned", tint = Color(0xFF4CAF50))
                        Spacer(modifier = Modifier.width(4.dp))
                        Column {
                            Text("Reward Unlocked!", fontWeight = FontWeight.Bold)
                            Text("Status: ${grant.status}", fontSize = 13.sp, color = Color.Gray)
                            Text("Date: ${grant.grantedAt}", fontSize = 12.sp, color = Color.Gray)
                        }
                    }
                }
            }
        }

        // Available Rewards
        if (data.availableRewards.isNotEmpty()) {
            item {
                Text(
                    text = "Available Rewards",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(top = 8.dp, bottom = 8.dp)
                )
            }
            items(data.availableRewards) { rule ->
                AvailableRewardItem(rule)
            }
        }
    }
}

@Composable
fun NextMilestoneCard(rule: RewardRule, earned: Double) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White)
    ) {
        Column(modifier = Modifier.padding(8.dp)) {
            Text(text = "Next Milestone", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Purple40)
            Spacer(modifier = Modifier.height(4.dp))
            Text(text = rule.title, fontSize = 18.sp, fontWeight = FontWeight.Bold)
            if (rule.description != null) {
                Spacer(modifier = Modifier.height(4.dp))
                HtmlText(html = rule.description)
            }
            Spacer(modifier = Modifier.height(4.dp))
            val progress = if (rule.thresholdUsd > 0) (earned / rule.thresholdUsd).toFloat().coerceIn(0f, 1f) else 0f
            LinearProgressIndicator(
                progress = { progress },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(8.dp),
                color = Purple40,
                trackColor = Color(0xFFE0E0E0),
            )
            Spacer(modifier = Modifier.height(4.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(text = "$${String.format(Locale.US, "%.2f", earned)} earned", fontSize = 12.sp, color = Color.Gray)
                Text(text = "$${String.format(Locale.US, "%.2f", rule.thresholdUsd)} goal", fontSize = 12.sp, color = Color.Gray)
            }
        }
    }
}

@Composable
fun AvailableRewardItem(rule: RewardRule) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White)
    ) {
        Row(
            modifier = Modifier.padding(8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            if (rule.imagePath != null) {
                val cleanPath = rule.imagePath.trim().removePrefix("/")
                val fullImageUrl = (if (cleanPath.startsWith("http")) cleanPath else "https://affscash.net/" + cleanPath).replace(" ", "%20")
                SubcomposeAsyncImage(
                    model = ImageRequest.Builder(LocalContext.current)
                        .data(fullImageUrl)
                        .addHeader("User-Agent", "Mozilla/5.0 (Linux; Android 13; Mobile) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Mobile Safari/537.36")
                        .addHeader("Accept", "image/webp,image/apng,image/*,*/*;q=0.8")
                        .addHeader("Referer", "https://affscash.net/")
                        .crossfade(true)
                        .build(),
                    contentDescription = rule.title,
                    modifier = Modifier.size(60.dp),
                    loading = {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            CircularProgressIndicator(modifier = Modifier.size(24.dp))
                        }
                    },
                    error = {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Icon(Icons.Default.Image, contentDescription = "No Image", tint = Color.Gray, modifier = Modifier.size(24.dp))
                        }
                    }
                )
                Spacer(modifier = Modifier.width(4.dp))
            }
            Column(modifier = Modifier.weight(1f)) {
                Text(text = rule.title, fontWeight = FontWeight.Bold, fontSize = 16.sp)
                if (rule.badgeLabel != null) {
                    Spacer(modifier = Modifier.height(4.dp))
                    Surface(
                        color = parseColor(rule.badgeColor ?: "#000000"),
                        shape = RoundedCornerShape(4.dp)
                    ) {
                        Text(
                            text = rule.badgeLabel,
                            color = Color.White,
                            fontSize = 10.sp,
                            modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                        )
                    }
                }
                Spacer(modifier = Modifier.height(4.dp))
                Text(text = "Threshold: $${(( rule.thresholdUsd )?.toString()?.toDoubleOrNull() ?: 0.0).let { "%.2f".format(it) } }", fontSize = 13.sp, color = Purple40, fontWeight = FontWeight.Bold)
                if (rule.isUnlocked) {
                    Text(text = "Unlocked", fontSize = 12.sp, color = Color(0xFF4CAF50), fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}

fun parseColor(colorString: String): Color {
    return try {
        Color(colorString.toColorInt())
    } catch (_: Exception) {
        Purple40
    }
}

@Composable
fun HtmlText(html: String, modifier: Modifier = Modifier) {
    val context = LocalContext.current
    val imageLoader = context.imageLoader
    AndroidView(
        modifier = modifier,
        factory = { ctx -> 
            TextView(ctx).apply {
                setTextColor(android.graphics.Color.GRAY)
                textSize = 14f
            }
        },
        update = { textView -> 
            textView.text = HtmlCompat.fromHtml(
                html, 
                HtmlCompat.FROM_HTML_MODE_COMPACT,
                CoilImageGetter(textView, imageLoader),
                null
            ) 
        }
    )
}
