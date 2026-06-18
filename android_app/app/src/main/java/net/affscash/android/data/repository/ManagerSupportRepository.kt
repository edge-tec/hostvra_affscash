package net.affscash.android.data.repository

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import net.affscash.android.data.model.ManagerConversationResponse
import net.affscash.android.data.model.ManagerMessageResponse
import net.affscash.android.data.model.ManagerSendMessageResponse
import net.affscash.android.data.model.ManagerUploadResponse
import net.affscash.android.data.network.ApiService
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ManagerSupportRepository @Inject constructor(
    private val apiService: ApiService
) {
    suspend fun getConversations(status: String): Result<ManagerConversationResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerConversations(status)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Failed to load conversations: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getMessages(conversationId: Int, affiliateId: Int): Result<ManagerMessageResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.getManagerMessages(conversationId, affiliateId)
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Failed to load messages: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun sendMessage(affiliateId: Int, message: String, attachmentId: Int? = null): Result<ManagerSendMessageResponse> = withContext(Dispatchers.IO) {
        try {
            val response = apiService.sendManagerMessage(
                affiliateId = affiliateId,
                message = message,
                attachmentId = attachmentId
            )
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Failed to send message: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun uploadAttachment(affiliateId: Int, file: File, mimeType: String): Result<ManagerUploadResponse> = withContext(Dispatchers.IO) {
        try {
            val requestFile = file.asRequestBody(mimeType.toMediaTypeOrNull())
            val filePart = MultipartBody.Part.createFormData("file", file.name, requestFile)
            val actionBody = "upload".toRequestBody("text/plain".toMediaTypeOrNull())
            val affiliateIdBody = affiliateId.toString().toRequestBody("text/plain".toMediaTypeOrNull())

            val response = apiService.uploadManagerAttachment(
                action = actionBody,
                affiliateId = affiliateIdBody,
                file = filePart
            )

            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Upload failed: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
