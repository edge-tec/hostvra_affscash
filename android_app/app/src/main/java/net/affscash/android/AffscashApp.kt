package net.affscash.android

import android.app.Application
import android.os.Build
import android.util.Log
import androidx.hilt.work.HiltWorkerFactory
import androidx.work.Configuration
import coil.ImageLoader
import coil.ImageLoaderFactory
import coil.decode.GifDecoder
import coil.decode.ImageDecoderDecoder
import coil.decode.SvgDecoder
import coil.disk.DiskCache
import coil.memory.MemoryCache
import dagger.hilt.android.HiltAndroidApp
import net.affscash.android.data.local.NotificationBadgeManager
import net.affscash.android.service.NotificationChannelManager
import okhttp3.OkHttpClient
import javax.inject.Inject

@HiltAndroidApp
class AffscashApp : Application(), ImageLoaderFactory, Configuration.Provider {
    
    @Inject
    lateinit var okHttpClient: OkHttpClient

    @Inject
    lateinit var notificationBadgeManager: NotificationBadgeManager

    @Inject
    lateinit var workerFactory: HiltWorkerFactory

    override fun onCreate() {
        super.onCreate()
        instance = this

        // Create all notification channels at app startup (required for Android O+).
        // Safe to call multiple times — existing channels are not modified.
        NotificationChannelManager.createAllChannels(this)
    }

    /**
     * WorkManager configuration with Hilt worker factory.
     * This replaces the default initializer disabled in AndroidManifest.xml.
     */
    override val workManagerConfiguration: Configuration
        get() = Configuration.Builder()
            .setWorkerFactory(workerFactory)
            .setMinimumLoggingLevel(Log.INFO)
            .build()

    companion object {
        private var instance: AffscashApp? = null

        fun getBadgeManager(): NotificationBadgeManager? {
            return instance?.notificationBadgeManager
        }
    }

    override fun newImageLoader(): ImageLoader {
        return ImageLoader.Builder(this)
            .okHttpClient(okHttpClient)
            .memoryCache {
                MemoryCache.Builder(this)
                    .maxSizePercent(0.25)
                    .build()
            }
            .diskCache {
                DiskCache.Builder()
                    .directory(cacheDir.resolve("image_cache"))
                    .maxSizePercent(0.02)
                    .build()
            }
            .components {
                add(SvgDecoder.Factory())
                if (Build.VERSION.SDK_INT >= 28) {
                    add(ImageDecoderDecoder.Factory())
                } else {
                    add(GifDecoder.Factory())
                }
            }
            .logger(
                object : coil.util.Logger {
                    override var level: Int = Log.DEBUG
                    override fun log(tag: String, priority: Int, message: String?, throwable: Throwable?) {
                        if (priority >= Log.ERROR) {
                            Log.e("CoilLoader", "$message", throwable)
                        } else {
                            Log.d("CoilLoader", "$message")
                        }
                    }
                }
            )
            .build()
    }
}

