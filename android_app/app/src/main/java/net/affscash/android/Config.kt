package net.affscash.android

import android.net.Uri

object Config {
    // Centralized Base URL configuration
    // Change this URL to point to your live server or local development instance (e.g. "http://10.0.2.2/")
    const val BASE_URL = "https://affscash.net/"

    // Computed cookie host for OkHttp and CookieJar persistence
    val COOKIE_HOST: String
        get() = try {
            Uri.parse(BASE_URL).host ?: "affscash.net"
        } catch (e: Exception) {
            "affscash.net"
        }
}
