package com.example.affscash.data.local

import android.content.Context
import android.content.SharedPreferences
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class UserManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val prefs: SharedPreferences = context.getSharedPreferences("user_prefs", Context.MODE_PRIVATE)

    val unauthFlow = kotlinx.coroutines.flow.MutableSharedFlow<Unit>(extraBufferCapacity = 1)

    fun triggerUnauth() {
        unauthFlow.tryEmit(Unit)
    }

    fun saveUser(role: String, email: String, name: String) {
        prefs.edit()
            .putString("ROLE", role)
            .putString("EMAIL", email)
            .putString("NAME", name)
            .apply()
    }

    fun getRole(): String? {
        return prefs.getString("ROLE", null)
    }

    fun saveIsImpersonating(isImpersonating: Boolean) {
        prefs.edit().putBoolean("IS_IMPERSONATING", isImpersonating).apply()
    }

    fun isImpersonating(): Boolean {
        return prefs.getBoolean("IS_IMPERSONATING", false)
    }

    fun clearUser() {
        prefs.edit().clear().apply()
    }
}
