package net.affscash.android.data.repository

import net.affscash.android.data.local.LocalNotification
import net.affscash.android.data.local.NotificationDao
import net.affscash.android.data.model.MarkNotificationReadRequest
import net.affscash.android.data.model.NotificationDeleteRequest
import net.affscash.android.data.network.ApiService
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class NotificationRepository @Inject constructor(
    private val apiService: ApiService,
    private val notificationDao: NotificationDao
) {
    /**
     * Fetches notifications from API and syncs to local DB.
     * Also returns the local DB flow for reactive UI.
     */
    fun getLocalNotifications(): Flow<List<net.affscash.android.data.model.NotificationItem>> {
        return notificationDao.getAllNotifications().map { localList ->
            localList.map { 
                net.affscash.android.data.model.NotificationItem(
                    id = it.id,
                    title = it.title,
                    message = it.message,
                    type = it.type,
                    link = it.link,
                    notificationType = it.notificationType,
                    deepLinkRoute = it.deepLinkRoute,
                    isRead = it.isRead,
                    createdAt = it.createdAt
                )
            }
        }
    }

    suspend fun fetchAndSync(page: Int = 1) = withContext(Dispatchers.IO) {
        val response = apiService.getNotifications(page = page)
        if (response.isSuccessful) {
            val body = response.body() ?: throw Exception("Empty response body")
            
            // Sync to local DB
            val localNotifs = body.notifications.map {
                LocalNotification(
                    id = it.id,
                    title = it.title,
                    message = it.message,
                    type = it.type ?: "info",
                    link = it.link,
                    notificationType = it.notificationType,
                    deepLinkRoute = it.deepLinkRoute,
                    isRead = it.isRead,
                    createdAt = it.createdAt
                )
            }
            
            if (page == 1) {
                // Clear old only if we're refreshing from scratch and we get valid data
                // In a production app you might want more sophisticated sync logic
                if (localNotifs.isNotEmpty()) {
                    notificationDao.deleteAll()
                }
            }
            notificationDao.insertAll(localNotifs)
            
            body
        } else {
            throw Exception("Failed to load notifications: ${response.code()}")
        }
    }

    suspend fun getUnreadCount() = withContext(Dispatchers.IO) {
        // Fallback to API if we need the absolute truth, but local DB is often enough
        val response = apiService.getUnreadCount()
        if (response.isSuccessful) {
            response.body()?.unread ?: notificationDao.getUnreadCount()
        } else {
            notificationDao.getUnreadCount()
        }
    }

    suspend fun markAsRead(id: Int?) = withContext(Dispatchers.IO) {
        if (id == null) {
            val response = apiService.markAllNotificationsRead()
            if (!response.isSuccessful) {
                throw Exception("Failed to mark all as read: ${response.code()}")
            }
            notificationDao.markAllAsRead()
        } else {
            val response = apiService.markNotificationRead(MarkNotificationReadRequest(id))
            if (!response.isSuccessful) {
                throw Exception("Failed to mark as read: ${response.code()}")
            }
            notificationDao.markAsRead(id)
        }
    }

    suspend fun deleteNotification(id: Int) = withContext(Dispatchers.IO) {
        val response = apiService.deleteNotification(NotificationDeleteRequest(id))
        if (!response.isSuccessful) {
            throw Exception("Failed to delete notification: ${response.code()}")
        }
        notificationDao.delete(id)
    }
}

