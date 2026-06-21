package net.affscash.android.data.repository

import net.affscash.android.data.model.ChatMessagesResponse
import net.affscash.android.data.model.SendChatMessageRequest
import net.affscash.android.data.model.SendChatMessageResponse
import net.affscash.android.data.model.UploadFileResponse
import net.affscash.android.data.network.ApiService
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
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

    suspend fun sendMessage(message: String, attachmentId: Int? = null): Result<SendChatMessageResponse> {
        return try {
            val response = apiService.sendChatMessage(
                SendChatMessageRequest(message = message, attachmentId = attachmentId)
            )
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

    suspend fun uploadFile(bytes: ByteArray, filename: String, mimeType: String): Result<UploadFileResponse> {
        return try {
            val requestBody = bytes.toRequestBody(mimeType.toMediaTypeOrNull())
            val part = MultipartBody.Part.createFormData("file", filename, requestBody)
            
            val response = apiService.uploadFile(part)
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

    suspend fun deleteMessage(messageId: Int): Result<net.affscash.android.data.model.GenericResponse> {
        return try {
            val response = apiService.deleteChatMessage(messageId = messageId)
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

    suspend fun editMessage(messageId: Int, newText: String): Result<net.affscash.android.data.model.SendChatMessageResponse> {
        return try {
            val response = apiService.editChatMessage(messageId = messageId, message = newText)
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
