package com.example.affscash.data.repository

import com.example.affscash.data.model.MarkNewsReadRequest
import com.example.affscash.data.network.ApiService
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
