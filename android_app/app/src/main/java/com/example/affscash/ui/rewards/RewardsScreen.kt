package com.example.affscash.ui.rewards

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil.compose.AsyncImage
import com.example.affscash.data.model.RewardRule
import com.example.affscash.data.model.RewardsResponse
import com.example.affscash.theme.Purple40

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RewardsScreen(
    onNavigateBack: () -> Unit,
    viewModel: RewardsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("My Rewards") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
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
                            Spacer(modifier = Modifier.height(8.dp))
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
        modifier = Modifier.padding(16.dp),
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
                        modifier = Modifier.padding(16.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(Icons.Default.CheckCircle, contentDescription = "Earned", tint = Color(0xFF4CAF50))
                        Spacer(modifier = Modifier.width(16.dp))
                        Column {
                            Text("Reward Unlocked!", fontWeight = FontWeight.Bold)
                            Text("Status: ${grant.status}", fontSize = 14.sp, color = Color.Gray)
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
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(text = "Next Milestone", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = Purple40)
            Spacer(modifier = Modifier.height(8.dp))
            Text(text = rule.title, fontSize = 18.sp, fontWeight = FontWeight.Bold)
            if (rule.description != null) {
                Spacer(modifier = Modifier.height(4.dp))
                Text(text = rule.description, fontSize = 14.sp, color = Color.Gray)
            }
            Spacer(modifier = Modifier.height(16.dp))
            val progress = if (rule.thresholdUsd > 0) (earned / rule.thresholdUsd).toFloat().coerceIn(0f, 1f) else 0f
            LinearProgressIndicator(
                progress = { progress },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(8.dp),
                color = Purple40,
                trackColor = Color(0xFFE0E0E0),
            )
            Spacer(modifier = Modifier.height(8.dp))
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(text = "$${String.format("%.2f", earned)} earned", fontSize = 12.sp, color = Color.Gray)
                Text(text = "$${String.format("%.2f", rule.thresholdUsd)} goal", fontSize = 12.sp, color = Color.Gray)
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
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            if (rule.imagePath != null) {
                val fullImageUrl = "https://affscash.net${rule.imagePath}"
                AsyncImage(
                    model = fullImageUrl,
                    contentDescription = rule.title,
                    modifier = Modifier.size(60.dp)
                )
                Spacer(modifier = Modifier.width(16.dp))
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
                Text(text = "Threshold: $${rule.thresholdUsd}", fontSize = 14.sp, color = Purple40, fontWeight = FontWeight.Bold)
                if (rule.isUnlocked) {
                    Text(text = "Unlocked", fontSize = 12.sp, color = Color(0xFF4CAF50), fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}

fun parseColor(colorString: String): Color {
    return try {
        Color(android.graphics.Color.parseColor(colorString))
    } catch (e: Exception) {
        Purple40
    }
}
