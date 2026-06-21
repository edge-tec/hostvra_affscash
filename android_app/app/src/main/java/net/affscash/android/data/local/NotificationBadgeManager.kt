package net.affscash.android.data.local

import android.content.Context
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import javax.inject.Inject
import javax.inject.Singleton

/**
 * Singleton managing the unread notification count as a StateFlow.
 * Persists in SharedPreferences for offline access.
 * Incremented by MyFirebaseMessagingService, decremented by mark-as-read actions.
 */
@Singleton
class NotificationBadgeManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val prefs = context.getSharedPreferences("notification_badge", Context.MODE_PRIVATE)
    
    private val _unreadCount = MutableStateFlow(prefs.getInt("unread_count", 0))
    val unreadCount: StateFlow<Int> = _unreadCount

    fun increment() {
        val newCount = _unreadCount.value + 1
        _unreadCount.value = newCount
        prefs.edit().putInt("unread_count", newCount).apply()
    }

    fun decrement() {
        val newCount = maxOf(0, _unreadCount.value - 1)
        _unreadCount.value = newCount
        prefs.edit().putInt("unread_count", newCount).apply()
    }

    fun setCount(count: Int) {
        val safeCount = maxOf(0, count)
        _unreadCount.value = safeCount
        prefs.edit().putInt("unread_count", safeCount).apply()
    }

    fun reset() {
        _unreadCount.value = 0
        prefs.edit().putInt("unread_count", 0).apply()
    }
}
