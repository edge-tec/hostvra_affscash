package com.example.affscash.di

import com.example.affscash.data.network.ApiService
import com.example.affscash.data.network.SessionCookieJar
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

@Module
@InstallIn(SingletonComponent::class)
object NetworkModule {

    private const val BASE_URL = "https://affscash.net/" // Should match PHP server URL

    @Provides
    @Singleton
    fun provideSessionCookieJar(@dagger.hilt.android.qualifiers.ApplicationContext context: android.content.Context): SessionCookieJar {
        return SessionCookieJar(context)
    }

    @Provides
    @Singleton
    fun provideOkHttpClient(cookieJar: SessionCookieJar): OkHttpClient {
        val loggingInterceptor = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }

        val cleanJsonResponseInterceptor = Interceptor { chain ->
            val response = chain.proceed(chain.request())
            val body = response.body
            if (body != null) {
                val contentType = body.contentType()
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
        }

        return OkHttpClient.Builder()
            .cookieJar(cookieJar)
            .addInterceptor(loggingInterceptor)
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
