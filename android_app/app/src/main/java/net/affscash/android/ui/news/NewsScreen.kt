package net.affscash.android.ui.news

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil.compose.SubcomposeAsyncImage
import coil.request.ImageRequest
import androidx.compose.material.icons.filled.Image
import androidx.core.text.HtmlCompat
import android.widget.TextView
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import net.affscash.android.data.model.NewsItem
import net.affscash.android.utils.CoilImageGetter
import coil.imageLoader
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun NewsScreen(
    viewModel: NewsViewModel = hiltViewModel(),
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }
    var selectedNews by remember { mutableStateOf<NewsItem?>(null) }

    LaunchedEffect(uiState.actionMessage) {
        uiState.actionMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.clearActionMessage()
        }
    }

    LaunchedEffect(uiState.error) {
        uiState.error?.let {
            snackbarHostState.showSnackbar(it)
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = { Text("News") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    TextButton(onClick = { viewModel.loadNews() }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Refresh", modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Refresh")
                    }
                    TextButton(onClick = { viewModel.markAllAsRead() }) {
                        Icon(Icons.Default.Check, contentDescription = "Mark all as read", modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Mark all as read")
                    }
                }
            )
        }
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .background(Color(0xFFF9FAFB))
        ) {
            if (uiState.isLoading && uiState.news.isEmpty()) {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            } else if (uiState.news.isEmpty()) {
                Text(
                    text = "No news available at the moment.",
                    modifier = Modifier.align(Alignment.Center),
                    color = Color.Gray
                )
            } else {
                LazyVerticalGrid(
                    columns = GridCells.Adaptive(minSize = 300.dp),
                    contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp),
                    horizontalArrangement = Arrangement.spacedBy(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp),
                    modifier = Modifier.fillMaxSize()
                ) {
                    items(uiState.news) { newsItem ->
                        NewsCard(
                            newsItem = newsItem,
                            onClick = {
                                if (!newsItem.isRead) {
                                    viewModel.markAsRead(newsItem.id)
                                }
                                selectedNews = newsItem
                            }
                        )
                    }
                }
            }
        }
    }

    // Full News Dialog
    selectedNews?.let { news ->
        AlertDialog(
            onDismissRequest = { selectedNews = null },
            title = { Text(news.title) },
            text = {
                Column(modifier = Modifier.fillMaxWidth().verticalScroll(rememberScrollState())) {
                    val rawPath = news.image ?: ""
                    if (rawPath.isNotEmpty()) {
                        val imageUrl = if (!rawPath.startsWith("http")) {
                            val cleanPath = rawPath.removePrefix("/")
                            "https://affscash.net/" + Uri.encode(cleanPath, "/")
                        } else {
                            rawPath
                        }
                        SubcomposeAsyncImage(
                            model = ImageRequest.Builder(LocalContext.current)
                                .data(imageUrl)
                                .addHeader("User-Agent", "Mozilla/5.0 (Linux; Android 13; Mobile) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Mobile Safari/537.36")
                                .crossfade(true)
                                .build(),
                            contentDescription = news.title,
                            contentScale = ContentScale.Crop,
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(200.dp)
                                .clip(RoundedCornerShape(8.dp))
                                .background(Color(0xFFF1F5F9)),
                            loading = {
                                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                                    CircularProgressIndicator(modifier = Modifier.size(24.dp))
                                }
                            },
                            error = {
                                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                                    Icon(Icons.Default.Image, contentDescription = "No Image", tint = Color.Gray, modifier = Modifier.size(48.dp))
                                }
                            }
                        )
                        Spacer(modifier = Modifier.height(8.dp))
                    }
                    
                    val contentHtml = news.body ?: news.summary ?: ""
                    val context = LocalContext.current
                    val imageLoader = context.imageLoader
                    AndroidView(
                        factory = { ctx ->
                            TextView(ctx).apply {
                                text = HtmlCompat.fromHtml(
                                    contentHtml, 
                                    HtmlCompat.FROM_HTML_MODE_COMPACT,
                                    CoilImageGetter(this, imageLoader),
                                    null
                                )
                                textSize = 15f
                                setTextColor(android.graphics.Color.DKGRAY)
                                setLineSpacing(0f, 1.2f)
                            }
                        },
                        update = { textView ->
                            textView.text = HtmlCompat.fromHtml(
                                contentHtml, 
                                HtmlCompat.FROM_HTML_MODE_COMPACT,
                                CoilImageGetter(textView, imageLoader),
                                null
                            )
                        }
                    )
                }
            },
            confirmButton = {
                TextButton(onClick = { selectedNews = null }) {
                    Text("Close")
                }
            }
        )
    }
}

@Composable
fun NewsCard(newsItem: NewsItem, onClick: () -> Unit) {
    Card(
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        shape = RoundedCornerShape(12.dp),
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
    ) {
        Column {
            // Image Box
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(180.dp)
            ) {
                val rawPath = newsItem.image ?: ""
                val imageUrl = if (rawPath.isEmpty()) {
                    "" // Error state
                } else if (!rawPath.startsWith("http")) {
                    val cleanPath = rawPath.removePrefix("/")
                    "https://affscash.net/" + Uri.encode(cleanPath, "/")
                } else {
                    rawPath
                }

                SubcomposeAsyncImage(
                    model = ImageRequest.Builder(LocalContext.current)
                        .data(imageUrl)
                        .addHeader("User-Agent", "Mozilla/5.0 (Linux; Android 13; Mobile) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Mobile Safari/537.36")
                        .crossfade(true)
                        .build(),
                    contentDescription = newsItem.title,
                    contentScale = ContentScale.Crop,
                    modifier = Modifier.fillMaxSize()
                        .background(Color(0xFFF1F5F9)),
                    loading = {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            CircularProgressIndicator(modifier = Modifier.size(24.dp))
                        }
                    },
                    error = {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Icon(Icons.Default.Image, contentDescription = "No Image", tint = Color.Gray, modifier = Modifier.size(48.dp))
                        }
                    }
                )

                // Hot Badge
                if (newsItem.isHot) {
                    Surface(
                        color = Color(0xFFEF4444),
                        shape = RoundedCornerShape(16.dp),
                        modifier = Modifier
                            .align(Alignment.TopStart)
                            .padding(8.dp)
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        ) {
                            Text("🔥 HOT", color = Color.White, fontSize = 12.sp, fontWeight = FontWeight.Bold)
                        }
                    }
                }

                // Time ago badge
                newsItem.publishedAt?.let { dateString ->
                    Surface(
                        color = Color.Black.copy(alpha = 0.6f),
                        shape = RoundedCornerShape(16.dp),
                        modifier = Modifier
                            .align(Alignment.TopEnd)
                            .padding(8.dp)
                    ) {
                        Text(
                            text = getTimeAgo(dateString),
                            color = Color.White,
                            fontSize = 12.sp,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                }
            }

            // Content
            Column(modifier = Modifier.padding(12.dp)) {
                Text(
                    text = "🚀 " + newsItem.title,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis
                )

                Spacer(modifier = Modifier.height(8.dp))

                val summaryHtml = newsItem.summary ?: ""
                AndroidView(
                    factory = { context ->
                        TextView(context).apply {
                            text = HtmlCompat.fromHtml(summaryHtml, HtmlCompat.FROM_HTML_MODE_COMPACT).toString()
                            textSize = 14f
                            setTextColor(android.graphics.Color.GRAY)
                            maxLines = 3
                            ellipsize = android.text.TextUtils.TruncateAt.END
                        }
                    },
                    update = { textView ->
                        textView.text = HtmlCompat.fromHtml(summaryHtml, HtmlCompat.FROM_HTML_MODE_COMPACT).toString()
                    },
                    modifier = Modifier.weight(1f, fill = false)
                )

                Spacer(modifier = Modifier.height(8.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = formatDate(newsItem.publishedAt),
                        color = Color.Gray,
                        style = MaterialTheme.typography.bodySmall
                    )

                    if (!newsItem.isRead) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Box(
                                modifier = Modifier
                                    .size(8.dp)
                                    .clip(CircleShape)
                                    .background(Color(0xFF6C43E8))
                            )
                            Spacer(modifier = Modifier.width(4.dp))
                            Text(
                                "New",
                                color = Color(0xFF6C43E8),
                                fontWeight = FontWeight.Bold,
                                style = MaterialTheme.typography.labelMedium
                            )
                        }
                    }
                }
            }
        }
    }
}

fun getTimeAgo(dateString: String): String {
    try {
        val format = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
        val date = format.parse(dateString) ?: return ""
        val now = Date()
        val diff = now.time - date.time

        val seconds = diff / 1000
        val minutes = seconds / 60
        val hours = minutes / 60
        val days = hours / 24
        val weeks = days / 7

        return when {
            weeks > 0 -> "$weeks weeks ago"
            days > 0 -> "$days days ago"
            hours > 0 -> "$hours hours ago"
            minutes > 0 -> "$minutes mins ago"
            else -> "Just now"
        }
    } catch (e: Exception) {
        return ""
    }
}

fun formatDate(dateString: String?): String {
    if (dateString == null) return ""
    try {
        val inputFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
        val outputFormat = SimpleDateFormat("MMM d, yyyy", Locale.getDefault())
        val date = inputFormat.parse(dateString) ?: return ""
        return outputFormat.format(date)
    } catch (e: Exception) {
        return dateString.split(" ").firstOrNull() ?: ""
    }
}
