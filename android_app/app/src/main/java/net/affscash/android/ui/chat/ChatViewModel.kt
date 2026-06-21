package net.affscash.android.ui.chat

import android.content.Context
import android.net.Uri
import android.provider.OpenableColumns
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import net.affscash.android.data.model.ChatMessage
import net.affscash.android.data.repository.ChatRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ChatViewModel @Inject constructor(
    private val chatRepository: ChatRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(ChatUiState())
    val uiState: StateFlow<ChatUiState> = _uiState.asStateFlow()

    init {
        fetchMessages()
        startPolling()
    }

    private fun startPolling() {
        viewModelScope.launch {
            while (isActive) {
                delay(5000) // Poll every 5 seconds
                fetchMessagesSilently()
            }
        }
    }

    private fun fetchMessages() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, error = null)
            val result = chatRepository.getMessages()
            result.onSuccess { response ->
                if (response.success) {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        messages = response.messages
                    )
                } else {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        error = response.error ?: "Failed to load messages"
                    )
                }
            }.onFailure {
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = it.message ?: "Failed to load messages"
                )
            }
        }
    }

    private fun fetchMessagesSilently() {
        viewModelScope.launch {
            val result = chatRepository.getMessages()
            result.onSuccess { response ->
                if (response.success) {
                    _uiState.value = _uiState.value.copy(
                        messages = response.messages
                    )
                }
            }
        }
    }

    fun selectFile(uri: Uri?, name: String?, mimeType: String?) {
        _uiState.value = _uiState.value.copy(
            selectedFileUri = uri,
            selectedFileName = name,
            selectedFileMimeType = mimeType
        )
    }

    fun clearSelectedFile() {
        _uiState.value = _uiState.value.copy(
            selectedFileUri = null,
            selectedFileName = null,
            selectedFileMimeType = null
        )
    }

    fun sendMessage(context: Context, message: String) {
        val uri = _uiState.value.selectedFileUri
        if (message.isBlank() && uri == null) return
        
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isSending = true, error = null)
            
            var attachmentId: Int? = null
            
            // Upload file first if exists
            if (uri != null) {
                try {
                    val bytes = context.contentResolver.openInputStream(uri)?.use { it.readBytes() }
                    val name = _uiState.value.selectedFileName ?: "attachment"
                    val mimeType = _uiState.value.selectedFileMimeType ?: "application/octet-stream"
                    
                    if (bytes != null) {
                        val uploadResult = chatRepository.uploadFile(bytes, name, mimeType)
                        uploadResult.onSuccess {
                            if (it.success) {
                                attachmentId = it.attachmentId
                            } else {
                                throw Exception(it.error ?: "File upload failed")
                            }
                        }.onFailure {
                            throw it
                        }
                    }
                } catch (e: Exception) {
                    val errorMsg = if (e.message.isNullOrBlank()) "Unknown error (HTTP or Network)" else e.message
                    _uiState.value = _uiState.value.copy(
                        isSending = false,
                        error = "Upload failed: $errorMsg"
                    )
                    return@launch
                }
            }

            // Send message with optional attachment
            val result = chatRepository.sendMessage(message, attachmentId)
            result.onSuccess { response ->
                if (response.success) {
                    _uiState.value = _uiState.value.copy(
                        isSending = false,
                        selectedFileUri = null,
                        selectedFileName = null,
                        selectedFileMimeType = null
                    )
                    // Fetch all messages again to get the updated list
                    fetchMessagesSilently()
                } else {
                    _uiState.value = _uiState.value.copy(
                        isSending = false,
                        error = response.error ?: "Failed to send message"
                    )
                }
            }.onFailure {
                _uiState.value = _uiState.value.copy(
                    isSending = false,
                    error = it.message ?: "Failed to send message"
                )
            }
        }
    }

    fun clearError() {
        _uiState.value = _uiState.value.copy(error = null)
    }

    fun deleteMessage(messageId: Int) {
        // Optimistic UI update: instantly remove it from the list
        val currentMessages = _uiState.value.messages.toMutableList()
        val index = currentMessages.indexOfFirst { it.id == messageId }
        if (index != -1) {
            currentMessages.removeAt(index)
            _uiState.value = _uiState.value.copy(messages = currentMessages)
        }

        viewModelScope.launch {
            val result = chatRepository.deleteMessage(messageId)
            result.onSuccess { response ->
                if (response.status != "success") {
                    // Revert or show error if needed, but we fetch anyway to sync
                    _uiState.value = _uiState.value.copy(error = response.message ?: "Failed to delete message")
                }
                fetchMessagesSilently()
            }.onFailure {
                _uiState.value = _uiState.value.copy(error = it.message ?: "Failed to delete message")
                fetchMessagesSilently()
            }
        }
    }

    fun editMessage(messageId: Int, newText: String) {
        viewModelScope.launch {
            val result = chatRepository.editMessage(messageId, newText)
            result.onSuccess { response ->
                if (!response.success) {
                    _uiState.value = _uiState.value.copy(error = response.error ?: "Failed to edit message")
                }
                fetchMessagesSilently()
            }.onFailure {
                _uiState.value = _uiState.value.copy(error = it.message ?: "Failed to edit message")
                fetchMessagesSilently()
            }
        }
    }
}

data class ChatUiState(
    val messages: List<ChatMessage> = emptyList(),
    val isLoading: Boolean = false,
    val isSending: Boolean = false,
    val error: String? = null,
    val selectedFileUri: Uri? = null,
    val selectedFileName: String? = null,
    val selectedFileMimeType: String? = null
)
