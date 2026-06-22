package net.affscash.android.data.network

import android.content.Context
import android.content.SharedPreferences
import net.affscash.android.Config
import okhttp3.Cookie
import okhttp3.CookieJar
import okhttp3.HttpUrl
import java.util.concurrent.ConcurrentHashMap

class SessionCookieJar(context: Context) : CookieJar {
    private val cookieStore = ConcurrentHashMap<String, MutableList<Cookie>>()
    private val prefs: SharedPreferences = context.getSharedPreferences("cookie_prefs", Context.MODE_PRIVATE)

    init {
        // Load cookies from SharedPreferences
        val savedCookies = prefs.getStringSet("cookies", emptySet()) ?: emptySet()
        val defaultHost = Config.COOKIE_HOST
        val cookies = mutableListOf<Cookie>()
        for (cookieString in savedCookies) {
            Cookie.parse(HttpUrl.Builder().scheme("https").host(defaultHost).build(), cookieString)?.let {
                cookies.add(it)
            }
        }
        if (cookies.isNotEmpty()) {
            cookieStore[defaultHost] = cookies
        }
    }

    override fun saveFromResponse(url: HttpUrl, cookies: List<Cookie>) {
        val currentCookies = cookieStore[url.host] ?: mutableListOf()
        cookies.forEach { newCookie ->
            currentCookies.removeAll { it.name == newCookie.name }
            currentCookies.add(newCookie)
        }
        cookieStore[url.host] = currentCookies

        // Save to SharedPreferences
        val cookieStrings = currentCookies.map { it.toString() }.toSet()
        prefs.edit().putStringSet("cookies", cookieStrings).apply()
    }

    override fun loadForRequest(url: HttpUrl): List<Cookie> {
        return cookieStore[url.host] ?: mutableListOf()
    }
    
    fun clearSession() {
        cookieStore.clear()
        prefs.edit().clear().apply()
    }
}
