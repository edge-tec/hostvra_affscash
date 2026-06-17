package com.example.affscash.ui.chat

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.affscash.data.model.ChatMessage
import com.example.affscash.data.repository.ChatRepository
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

    fun sendMessage(message: String) {
        if (message.isBlank()) return
        
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isSending = true, error = null)
            val result = chatRepository.sendMessage(message)
            result.onSuccess { response ->
                if (response.success) {
                    _uiState.value = _uiState.value.copy(isSending = false)
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
}

data class ChatUiState(
    val messages: List<ChatMessage> = emptyList(),
    val isLoading: Boolean = false,
    val isSending: Boolean = false,
    val error: String? = null
)
