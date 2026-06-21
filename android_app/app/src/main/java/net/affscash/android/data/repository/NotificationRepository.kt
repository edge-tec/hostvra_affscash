package net.affscash.android.data.repository

import net.affscash.android.data.model.MarkNotificationRequest
import net.affscash.android.data.model.NotificationDeleteRequest
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class NotificationRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getNotifications(page: Int = 1) = withContext(Dispatchers.IO) {
        val response = apiService.getNotifications(page = page)
        if (response.isSuccessful) {
            response.body() ?: throw Exception("Empty response body")
        } else {
            throw Exception("Failed to load notifications: ${response.code()}")
        }
    }

    suspend fun getUnreadCount() = withContext(Dispatchers.IO) {
        val response = apiService.getUnreadCount()
        if (response.isSuccessful) {
            response.body()?.unread ?: 0
        } else {
            0
        }
    }

    suspend fun markAsRead(id: Int?) = withContext(Dispatchers.IO) {
        val response = apiService.markNotificationAsRead(MarkNotificationRequest(id))
        if (!response.isSuccessful) {
            throw Exception("Failed to mark as read: ${response.code()}")
        }
    }

    suspend fun deleteNotification(id: Int) = withContext(Dispatchers.IO) {
        val response = apiService.deleteNotification(NotificationDeleteRequest(id))
        if (!response.isSuccessful) {
            throw Exception("Failed to delete notification: ${response.code()}")
        }
    }
}
