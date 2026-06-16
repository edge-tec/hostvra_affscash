package com.example.affscash.data.network

import okhttp3.Cookie
import okhttp3.CookieJar
import okhttp3.HttpUrl
import java.util.concurrent.ConcurrentHashMap

class SessionCookieJar : CookieJar {
    private val cookieStore = ConcurrentHashMap<String, MutableList<Cookie>>()

    override fun saveFromResponse(url: HttpUrl, cookies: List<Cookie>) {
        val currentCookies = cookieStore[url.host] ?: mutableListOf()
        // Remove old cookies with same name
        cookies.forEach { newCookie ->
            currentCookies.removeAll { it.name == newCookie.name }
            currentCookies.add(newCookie)
        }
        cookieStore[url.host] = currentCookies
    }

    override fun loadForRequest(url: HttpUrl): List<Cookie> {
        return cookieStore[url.host] ?: mutableListOf()
    }
    
    fun clearSession() {
        cookieStore.clear()
    }
}
