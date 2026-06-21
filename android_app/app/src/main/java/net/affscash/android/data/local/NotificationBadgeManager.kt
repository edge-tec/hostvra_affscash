package net.affscash.android.data.local

import android.content.Context
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import javax.inject.Inject
import javax.inject.Singleton

/**
 * Singleton managing exact badge counts synced via FCM.
 * Persists in SharedPreferences for offline access.
 */
@Singleton
class NotificationBadgeManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val prefs = context.getSharedPreferences("notification_badge", Context.MODE_PRIVATE)
    
    private val _unreadNotifs = MutableStateFlow(prefs.getInt("unread_notifs", 0))
    val unreadNotifs: StateFlow<Int> = _unreadNotifs

    private val _unreadChats = MutableStateFlow(prefs.getInt("unread_chats", 0))
    val unreadChats: StateFlow<Int> = _unreadChats

    private val _unreadAlerts = MutableStateFlow(prefs.getInt("unread_alerts", 0))
    val unreadAlerts: StateFlow<Int> = _unreadAlerts

    private val _pendingApprovals = MutableStateFlow(prefs.getInt("pending_approvals", 0))
    val pendingApprovals: StateFlow<Int> = _pendingApprovals

    fun updateCounts(notifs: Int?, chats: Int?, alerts: Int?, approvals: Int?) {
        val editor = prefs.edit()
        
        notifs?.let {
            val safe = maxOf(0, it)
            _unreadNotifs.value = safe
            editor.putInt("unread_notifs", safe)
        }
        chats?.let {
            val safe = maxOf(0, it)
            _unreadChats.value = safe
            editor.putInt("unread_chats", safe)
        }
        alerts?.let {
            val safe = maxOf(0, it)
            _unreadAlerts.value = safe
            editor.putInt("unread_alerts", safe)
        }
        approvals?.let {
            val safe = maxOf(0, it)
            _pendingApprovals.value = safe
            editor.putInt("pending_approvals", safe)
        }
        
        editor.apply()
    }

    fun reset() {
        _unreadNotifs.value = 0
        _unreadChats.value = 0
        _unreadAlerts.value = 0
        _pendingApprovals.value = 0
        
        prefs.edit()
            .putInt("unread_notifs", 0)
            .putInt("unread_chats", 0)
            .putInt("unread_alerts", 0)
            .putInt("pending_approvals", 0)
            .apply()
    }
}
