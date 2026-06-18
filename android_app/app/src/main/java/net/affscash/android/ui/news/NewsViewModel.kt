package net.affscash.android.ui.news

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.NewsItem
import net.affscash.android.data.repository.NewsRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class NewsUiState(
    val isLoading: Boolean = false,
    val news: List<NewsItem> = emptyList(),
    val error: String? = null,
    val actionMessage: String? = null
)

@HiltViewModel
class NewsViewModel @Inject constructor(
    private val newsRepository: NewsRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(NewsUiState())
    val uiState: StateFlow<NewsUiState> = _uiState.asStateFlow()

    init {
        loadNews()
    }

    fun loadNews() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, error = null)
            try {
                val response = newsRepository.getNews()
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body?.success == true) {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            news = body.news
                        )
                    } else {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            error = body?.error ?: "Failed to load news"
                        )
                    }
                } else {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        error = "Server error: ${response.code()}"
                    )
                }
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = e.localizedMessage ?: "Network error"
                )
            }
        }
    }

    fun markAsRead(newsId: Int) {
        viewModelScope.launch {
            try {
                // Optimistically update UI
                val updatedNews = _uiState.value.news.map { 
                    if (it.id == newsId) it.copy(isRead = true) else it 
                }
                _uiState.value = _uiState.value.copy(news = updatedNews)

                val response = newsRepository.markNewsAsRead(newsId)
                if (!response.isSuccessful || response.body()?.success != true) {
                    // Revert on failure (optional)
                    loadNews()
                }
            } catch (e: Exception) {
                // Ignore silent failures for mark as read
            }
        }
    }

    fun markAllAsRead() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true)
            try {
                val response = newsRepository.markAllNewsAsRead()
                if (response.isSuccessful && response.body()?.success == true) {
                    // Optimistically update UI
                    val updatedNews = _uiState.value.news.map { it.copy(isRead = true) }
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        news = updatedNews,
                        actionMessage = "All marked as read"
                    )
                } else {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        error = "Failed to mark all as read"
                    )
                }
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = e.localizedMessage ?: "Network error"
                )
            }
        }
    }

    fun clearActionMessage() {
        _uiState.value = _uiState.value.copy(actionMessage = null)
    }
}
