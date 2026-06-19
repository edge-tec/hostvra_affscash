package net.affscash.android.di

import net.affscash.android.data.network.ApiService
import net.affscash.android.data.network.SessionCookieJar
import retrofit2.converter.kotlinx.serialization.asConverterFactory
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import kotlinx.serialization.json.Json
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import okhttp3.Interceptor
import okhttp3.ResponseBody.Companion.toResponseBody
import java.util.concurrent.TimeUnit
import javax.inject.Singleton
import dagger.hilt.android.qualifiers.ApplicationContext
import android.content.Context
import net.affscash.android.data.local.UserManager

@Module
@InstallIn(SingletonComponent::class)
object NetworkModule {

    private const val BASE_URL = "https://affscash.net/"

    @Provides
    @Singleton
    fun provideSessionCookieJar(@ApplicationContext context: Context): SessionCookieJar {
        return SessionCookieJar(context)
    }

    @Provides
    @Singleton
    fun provideOkHttpClient(cookieJar: SessionCookieJar, userManager: UserManager, @ApplicationContext context: Context): OkHttpClient {
        val loggingInterceptor = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }

        val cleanJsonResponseInterceptor = Interceptor { chain ->
            val response = chain.proceed(chain.request())
            val body = response.body
            if (body != null) {
                val contentType = body.contentType()
                if (contentType?.subtype?.contains("json") == true) {
                    val content = body.string()
                    val startIndex = content.indexOfFirst { it == '{' || it == '[' }
                    val endIndex = content.indexOfLast { it == '}' || it == ']' }
                    
                    val cleanContent = if (startIndex in 0..endIndex) {
                        content.substring(startIndex, endIndex + 1)
                    } else {
                        content
                    }
                    val newBody = cleanContent.toResponseBody(contentType)
                    response.newBuilder().body(newBody).build()
                } else {
                    response
                }
            } else {
                response
            }
        }

        val authInterceptor = Interceptor { chain ->
            val request = chain.request()
            val response = chain.proceed(request)
            if (response.code == 401) {
                val path = request.url.encodedPath
                if (!path.contains("/auth") && !path.contains("/login")) {
                    userManager.triggerUnauth()
                }
            }
            response
        }

        val trustAllCerts = arrayOf<javax.net.ssl.TrustManager>(
            object : javax.net.ssl.X509TrustManager {
                override fun checkClientTrusted(chain: Array<java.security.cert.X509Certificate>, authType: String) {}
                override fun checkServerTrusted(chain: Array<java.security.cert.X509Certificate>, authType: String) {}
                override fun getAcceptedIssuers(): Array<java.security.cert.X509Certificate> = arrayOf()
            }
        )

        val sslContext = javax.net.ssl.SSLContext.getInstance("SSL")
        sslContext.init(null, trustAllCerts, java.security.SecureRandom())

        val cacheSize = (50 * 1024 * 1024).toLong() // 50 MB
        val cache = okhttp3.Cache(context.cacheDir, cacheSize)

        return OkHttpClient.Builder()
            .cache(cache)
            .sslSocketFactory(sslContext.socketFactory, trustAllCerts[0] as javax.net.ssl.X509TrustManager)
            .hostnameVerifier { _, _ -> true }
            .cookieJar(cookieJar)
            .addInterceptor(loggingInterceptor)
            .addInterceptor(authInterceptor)
            .addInterceptor(cleanJsonResponseInterceptor)
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .build()
    }

    @Provides
    @Singleton
    fun provideRetrofit(okHttpClient: OkHttpClient): Retrofit {
        val contentType = "application/json".toMediaType()
        val json = Json { 
            ignoreUnknownKeys = true
            isLenient = true 
            coerceInputValues = true
        }

        return Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(json.asConverterFactory(contentType))
            .build()
    }

    @Provides
    @Singleton
    fun provideApiService(retrofit: Retrofit): ApiService {
        return retrofit.create(ApiService::class.java)
    }
}
