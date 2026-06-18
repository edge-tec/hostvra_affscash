package net.affscash.android.data.repository

import net.affscash.android.data.model.MarkNewsReadRequest
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class NewsRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getNews() = withContext(Dispatchers.IO) {
        apiService.getNews()
    }

    suspend fun markNewsAsRead(newsId: Int) = withContext(Dispatchers.IO) {
        apiService.markNewsAsRead(MarkNewsReadRequest(newsId))
    }

    suspend fun markAllNewsAsRead() = withContext(Dispatchers.IO) {
        apiService.markAllNewsAsRead()
    }
}
