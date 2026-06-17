package com.example.affscash.data.repository

import com.example.affscash.data.model.ChatMessagesResponse
import com.example.affscash.data.model.SendChatMessageRequest
import com.example.affscash.data.model.SendChatMessageResponse
import com.example.affscash.data.network.ApiService
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ChatRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getMessages(): Result<ChatMessagesResponse> {
        return try {
            val response = apiService.getChatMessages()
            if (response.isSuccessful) {
                response.body()?.let {
                    Result.success(it)
                } ?: Result.failure(Exception("Empty response"))
            } else {
                Result.failure(Exception(response.message()))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun sendMessage(message: String): Result<SendChatMessageResponse> {
        return try {
            val response = apiService.sendChatMessage(SendChatMessageRequest(message = message))
            if (response.isSuccessful) {
                response.body()?.let {
                    Result.success(it)
                } ?: Result.failure(Exception("Empty response"))
            } else {
                Result.failure(Exception(response.message()))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
