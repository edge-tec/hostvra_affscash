package com.example.affscash.ui.notifications

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.NotificationItem
import com.example.affscash.data.repository.NotificationRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class NotificationsState {
    object Loading : NotificationsState()
    data class Success(val notifications: List<NotificationItem>, val unreadCount: Int) : NotificationsState()
    data class Error(val message: String) : NotificationsState()
}

@HiltViewModel
class NotificationsViewModel @Inject constructor(
    private val repository: NotificationRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<NotificationsState>(NotificationsState.Loading)
    val uiState: StateFlow<NotificationsState> = _uiState.asStateFlow()

    init {
        loadNotifications()
    }

    fun loadNotifications() {
        viewModelScope.launch {
            _uiState.value = NotificationsState.Loading
            try {
                val response = repository.getNotifications()
                if (response.success) {
                    _uiState.value = NotificationsState.Success(response.notifications, response.unread)
                } else {
                    _uiState.value = NotificationsState.Error(response.error ?: "Failed to load notifications")
                }
            } catch (e: Exception) {
                _uiState.value = NotificationsState.Error(e.message ?: "An error occurred")
            }
        }
    }

    fun markAsRead(id: Int?) {
        viewModelScope.launch {
            try {
                repository.markAsRead(id)
                loadNotifications() // Reload after marking as read
            } catch (e: Exception) {
                // Ignore error for now
            }
        }
    }
}
