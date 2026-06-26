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
    private var hasMoreData = true

    init {
        // Observe local DB as the single source of truth
        viewModelScope.launch {
            repository.getLocalNotifications().collect { localList ->
                val unread = localList.count { it.isRead == 0 }
                badgeManager.setCount(unread)
                _uiState.value = NotificationsState.Success(
                    notifications = localList,
                    unreadCount = unread,
                    hasMore = hasMoreData,
                    isLoadingMore = false
                )
            }
        }
        
        loadNotifications()
    }

    fun loadNotifications() {
        currentPage = 1
        hasMoreData = true
        viewModelScope.launch {
            if (_uiState.value !is NotificationsState.Success) {
                _uiState.value = NotificationsState.Loading
            }
            try {
                val response = repository.fetchAndSync(page = 1)
                hasMoreData = response.hasMore
                // UI state will update automatically via the flow
            } catch (e: Exception) {
                if (_uiState.value !is NotificationsState.Success) {
                    _uiState.value = NotificationsState.Error(e.message ?: "An error occurred")
                }
            }
        }
    }

    fun loadMore() {
        val current = _uiState.value
        if (current !is NotificationsState.Success || !hasMoreData || current.isLoadingMore) return

        _uiState.value = current.copy(isLoadingMore = true)
        currentPage++

        viewModelScope.launch {
            try {
                val response = repository.fetchAndSync(page = currentPage)
                hasMoreData = response.hasMore
                // UI state will update automatically via the flow
            } catch (e: Exception) {
                currentPage-- // Revert page on error
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
            } catch (e: Exception) {
                // Ignore error — will sync on next load or DB will update
            }
        }
    }

    fun deleteNotification(id: Int) {
        viewModelScope.launch {
            try {
                repository.deleteNotification(id)
            } catch (e: Exception) {
                // Ignore error
            }
        }
    }

    fun syncUnreadCount() {
        viewModelScope.launch {
            try {
                repository.fetchAndSync(1) // Full sync is better than just count
            } catch (_: Exception) {}
        }
    }
}
