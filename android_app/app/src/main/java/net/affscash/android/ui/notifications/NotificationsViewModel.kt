package net.affscash.android.ui.notifications

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.local.NotificationBadgeManager
import net.affscash.android.data.model.NotificationItem
import net.affscash.android.data.repository.NotificationRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class NotificationsState {
    object Loading : NotificationsState()
    data class Success(
        val notifications: List<NotificationItem>,
        val unreadCount: Int,
        val hasMore: Boolean = false,
        val isLoadingMore: Boolean = false
    ) : NotificationsState()
    data class Error(val message: String) : NotificationsState()
}

@HiltViewModel
class NotificationsViewModel @Inject constructor(
    private val repository: NotificationRepository,
    private val badgeManager: NotificationBadgeManager
) : ViewModel() {

    private val _uiState = MutableStateFlow<NotificationsState>(NotificationsState.Loading)
    val uiState: StateFlow<NotificationsState> = _uiState.asStateFlow()

    private var currentPage = 1
    private var allNotifications = mutableListOf<NotificationItem>()

    init {
        loadNotifications()
    }

    fun loadNotifications() {
        currentPage = 1
        allNotifications.clear()
        viewModelScope.launch {
            _uiState.value = NotificationsState.Loading
            try {
                val response = repository.getNotifications(page = 1)
                if (response.success) {
                    allNotifications.addAll(response.notifications)
                    badgeManager.setCount(response.unread)
                    _uiState.value = NotificationsState.Success(
                        notifications = allNotifications.toList(),
                        unreadCount = response.unread,
                        hasMore = response.hasMore
                    )
                } else {
                    _uiState.value = NotificationsState.Error(response.error ?: "Failed to load notifications")
                }
            } catch (e: Exception) {
                _uiState.value = NotificationsState.Error(e.message ?: "An error occurred")
            }
        }
    }

    fun loadMore() {
        val current = _uiState.value
        if (current !is NotificationsState.Success || !current.hasMore || current.isLoadingMore) return

        _uiState.value = current.copy(isLoadingMore = true)
        currentPage++

        viewModelScope.launch {
            try {
                val response = repository.getNotifications(page = currentPage)
                if (response.success) {
                    allNotifications.addAll(response.notifications)
                    _uiState.value = NotificationsState.Success(
                        notifications = allNotifications.toList(),
                        unreadCount = response.unread,
                        hasMore = response.hasMore
                    )
                }
            } catch (e: Exception) {
                // Revert page on error
                currentPage--
                val prev = _uiState.value
                if (prev is NotificationsState.Success) {
                    _uiState.value = prev.copy(isLoadingMore = false)
                }
            }
        }
    }

    fun markAsRead(id: Int?) {
        viewModelScope.launch {
            try {
                repository.markAsRead(id)
                if (id != null) {
                    badgeManager.decrement()
                    // Optimistic update: mark the item as read in the list
                    allNotifications.replaceAll { notif ->
                        if (notif.id == id) notif.copy(isRead = 1) else notif
                    }
                } else {
                    // Mark all as read
                    badgeManager.reset()
                    allNotifications.replaceAll { it.copy(isRead = 1) }
                }
                val current = _uiState.value
                if (current is NotificationsState.Success) {
                    val newUnread = allNotifications.count { it.isRead == 0 }
                    _uiState.value = current.copy(
                        notifications = allNotifications.toList(),
                        unreadCount = newUnread
                    )
                }
            } catch (e: Exception) {
                // Ignore error — will sync on next load
            }
        }
    }

    fun deleteNotification(id: Int) {
        viewModelScope.launch {
            try {
                repository.deleteNotification(id)
                val removed = allNotifications.find { it.id == id }
                allNotifications.removeAll { it.id == id }
                if (removed?.isRead == 0) {
                    badgeManager.decrement()
                }
                val current = _uiState.value
                if (current is NotificationsState.Success) {
                    val newUnread = allNotifications.count { it.isRead == 0 }
                    _uiState.value = current.copy(
                        notifications = allNotifications.toList(),
                        unreadCount = newUnread
                    )
                }
            } catch (e: Exception) {
                // Ignore error
            }
        }
    }

    fun syncUnreadCount() {
        viewModelScope.launch {
            try {
                val unread = repository.getUnreadCount()
                badgeManager.setCount(unread)
            } catch (_: Exception) {}
        }
    }
}
