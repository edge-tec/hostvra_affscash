package net.affscash.android.ui.manager.support

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import net.affscash.android.data.model.ManagerConversation
import net.affscash.android.data.model.ManagerMessage
import net.affscash.android.data.repository.ManagerSupportRepository
import java.io.File
import javax.inject.Inject

data class ManagerSupportState(
    val openConversations: List<ManagerConversation> = emptyList(),
    val closedConversations: List<ManagerConversation> = emptyList(),
    val messages: List<ManagerMessage> = emptyList(),
    val isLoadingConversations: Boolean = false,
    val isLoadingMessages: Boolean = false,
    val isSending: Boolean = false,
    val isUploading: Boolean = false,
    val error: String? = null,
    val selectedConversation: ManagerConversation? = null
)

@HiltViewModel
class ManagerSupportViewModel @Inject constructor(
    private val repository: ManagerSupportRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(ManagerSupportState())
    val uiState: StateFlow<ManagerSupportState> = _uiState.asStateFlow()

    fun loadConversations() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingConversations = true, error = null) }
            
            val openResult = repository.getConversations("open")
            val closedResult = repository.getConversations("closed")

            if (openResult.isSuccess && closedResult.isSuccess) {
                _uiState.update {
                    it.copy(
                        isLoadingConversations = false,
                        openConversations = openResult.getOrNull()?.conversations ?: emptyList(),
                        closedConversations = closedResult.getOrNull()?.conversations ?: emptyList()
                    )
                }
            } else {
                _uiState.update {
                    it.copy(
                        isLoadingConversations = false,
                        error = openResult.exceptionOrNull()?.message ?: closedResult.exceptionOrNull()?.message ?: "Unknown error"
                    )
                }
            }
        }
    }

    fun selectConversation(conversation: ManagerConversation) {
        _uiState.update { it.copy(selectedConversation = conversation, messages = emptyList(), error = null) }
        loadMessages(conversation.conversationId ?: 0, conversation.affiliateId)
    }

    fun loadMessages(conversationId: Int, affiliateId: Int) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingMessages = true, error = null) }
            val result = repository.getMessages(conversationId, affiliateId)
            
            if (result.isSuccess) {
                _uiState.update {
                    it.copy(
                        isLoadingMessages = false,
                        messages = result.getOrNull()?.messages ?: emptyList()
                    )
                }
            } else {
                _uiState.update {
                    it.copy(
                        isLoadingMessages = false,
                        error = result.exceptionOrNull()?.message ?: "Failed to load messages"
                    )
                }
            }
        }
    }

    fun sendMessage(message: String) {
        val selected = _uiState.value.selectedConversation ?: return
        if (message.isBlank()) return

        viewModelScope.launch {
            _uiState.update { it.copy(isSending = true, error = null) }
            val result = repository.sendMessage(selected.affiliateId, message)
            
            if (result.isSuccess) {
                val sentMsg = result.getOrNull()?.message
                if (sentMsg != null) {
                    _uiState.update {
                        it.copy(
                            isSending = false,
                            messages = it.messages + sentMsg
                        )
                    }
                } else {
                    _uiState.update { it.copy(isSending = false) }
                    loadMessages(selected.conversationId ?: 0, selected.affiliateId)
                }
            } else {
                _uiState.update {
                    it.copy(
                        isSending = false,
                        error = result.exceptionOrNull()?.message ?: "Failed to send message"
                    )
                }
            }
        }
    }

    fun uploadAndSendMessage(file: File, mimeType: String, messageText: String) {
        val selected = _uiState.value.selectedConversation ?: return

        viewModelScope.launch {
            _uiState.update { it.copy(isUploading = true, isSending = true, error = null) }
            val uploadResult = repository.uploadAttachment(selected.affiliateId, file, mimeType)

            if (uploadResult.isSuccess) {
                val attachmentId = uploadResult.getOrNull()?.attachmentId
                if (attachmentId != null) {
                    val sendResult = repository.sendMessage(selected.affiliateId, messageText, attachmentId)
                    if (sendResult.isSuccess) {
                        val sentMsg = sendResult.getOrNull()?.message
                        if (sentMsg != null) {
                            _uiState.update {
                                it.copy(
                                    isUploading = false,
                                    isSending = false,
                                    messages = it.messages + sentMsg
                                )
                            }
                        } else {
                            _uiState.update { it.copy(isUploading = false, isSending = false) }
                            loadMessages(selected.conversationId ?: 0, selected.affiliateId)
                        }
                    } else {
                        _uiState.update {
                            it.copy(
                                isUploading = false,
                                isSending = false,
                                error = sendResult.exceptionOrNull()?.message ?: "Failed to send attachment message"
                            )
                        }
                    }
                } else {
                     _uiState.update {
                        it.copy(
                            isUploading = false,
                            isSending = false,
                            error = "Failed to upload file properly."
                        )
                    }
                }
            } else {
                _uiState.update {
                    it.copy(
                        isUploading = false,
                        isSending = false,
                        error = uploadResult.exceptionOrNull()?.message ?: "Failed to upload file"
                    )
                }
            }
        }
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }

    fun deleteMessage(messageId: Int) {
        viewModelScope.launch {
            try {
                val response = repository.deleteMessage(messageId)
                if (response.isSuccess) {
                    val selected = _uiState.value.selectedConversation
                    if (selected != null) {
                        loadMessages(selected.conversationId ?: 0, selected.affiliateId)
                    }
                } else {
                    _uiState.update { it.copy(error = response.exceptionOrNull()?.message ?: "Failed to delete message") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(error = e.localizedMessage) }
            }
        }
    }
}
