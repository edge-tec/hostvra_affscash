package com.example.affscash.data.repository

import com.example.affscash.data.model.MarkNotificationRequest
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class NotificationRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getNotifications() = withContext(Dispatchers.IO) {
        val response = apiService.getNotifications()
        if (response.isSuccessful) {
            response.body() ?: throw Exception("Empty response body")
        } else {
            throw Exception("Failed to load notifications: ${response.code()}")
        }
    }

    suspend fun markAsRead(id: Int?) = withContext(Dispatchers.IO) {
        val response = apiService.markNotificationAsRead(MarkNotificationRequest(id))
        if (!response.isSuccessful) {
            throw Exception("Failed to mark as read: ${response.code()}")
        }
    }
}
