package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class NewsListResponse(
    val success: Boolean,
    val news: List<NewsItem> = emptyList(),
    val error: String? = null
)

@Serializable
data class NewsItem(
    val id: Int,
    val title: String,
    val summary: String?,
    val body: String?,
    val image: String?,
    @SerialName("is_hot") val isHot: Boolean,
    @SerialName("is_read") val isRead: Boolean,
    @SerialName("published_at") val publishedAt: String?
)

@Serializable
data class MarkNewsReadRequest(
    @SerialName("news_id") val newsId: Int
)
