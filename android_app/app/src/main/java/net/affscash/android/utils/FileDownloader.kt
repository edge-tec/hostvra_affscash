package net.affscash.android.utils

import android.app.DownloadManager
import android.content.Context
import android.net.Uri
import android.os.Environment
import android.widget.Toast

object FileDownloader {

    fun downloadSecureFile(context: Context, url: String, fileName: String) {
        try {
            val prefs = context.getSharedPreferences("cookie_prefs", Context.MODE_PRIVATE)
            val savedCookies = prefs.getStringSet("cookies", emptySet()) ?: emptySet()
            
            // Extract just "name=value" pairs to pass in the Cookie header
            val cookieString = savedCookies.mapNotNull { it.split(";").firstOrNull() }.joinToString("; ")
            
            val request = DownloadManager.Request(Uri.parse(url))
                .setTitle(fileName)
                .setDescription("Downloading file...")
                .setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                .setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, fileName)
            
            if (cookieString.isNotEmpty()) {
                request.addRequestHeader("Cookie", cookieString)
            }
            
            val downloadManager = context.getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
            downloadManager.enqueue(request)
            
            Toast.makeText(context, "Download started...", Toast.LENGTH_SHORT).show()
        } catch (e: Exception) {
            Toast.makeText(context, "Failed to start download: ${e.message}", Toast.LENGTH_SHORT).show()
        }
    }
}
