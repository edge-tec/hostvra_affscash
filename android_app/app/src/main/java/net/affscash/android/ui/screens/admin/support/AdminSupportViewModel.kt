package net.affscash.android.ui.screens.admin.support

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import net.affscash.android.data.model.AdminSupportActionRequest
import net.affscash.android.data.model.AdminSupportConversationRow
import net.affscash.android.data.model.AdminSupportMessage
import net.affscash.android.data.model.AdminSupportSendRequest
import net.affscash.android.data.network.ApiService
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.RequestBody.Companion.toRequestBody
import javax.inject.Inject

data class AdminSupportUiState(
    val isLoadingConversations: Boolean = false,
    val conversations: List<AdminSupportConversationRow> = emptyList(),
    val error: String? = null,
    val selectedTab: String = "affiliate", // affiliate, advertiser, manager
    val selectedFilter: String = "open", // open, closed
    val selectedConversationId: Int? = null,
    val selectedAffiliateId: Int? = null,
    val selectedName: String = "",
    val isLoadingMessages: Boolean = false,
    val messages: List<AdminSupportMessage> = emptyList(),
    val isSendingMessage: Boolean = false,
    val isUploading: Boolean = false
)

@HiltViewModel
class AdminSupportViewModel @Inject constructor(
    private val apiService: ApiService
) : ViewModel() {

    private val _uiState = MutableStateFlow(AdminSupportUiState())
    val uiState: StateFlow<AdminSupportUiState> = _uiState.asStateFlow()

    private var pollingJob: Job? = null

    init {
        loadConversations()
    }

    fun setTab(tab: String) {
        _uiState.update { it.copy(selectedTab = tab, selectedConversationId = null, messages = emptyList()) }
        stopPolling()
        loadConversations()
    }

    fun setFilter(filter: String) {
        _uiState.update { it.copy(selectedFilter = filter) }
        loadConversations()
    }

    fun loadConversations() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingConversations = true, error = null) }
            try {
                val response = apiService.getAdminConversations(
                    status = _uiState.value.selectedFilter,
                    ownerType = _uiState.value.selectedTab
                )
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body?.success == true && body.data != null) {
                        _uiState.update { it.copy(conversations = body.data.conversations, isLoadingConversations = false) }
                    } else {
                        _uiState.update { it.copy(error = body?.error ?: "Failed to load conversations", isLoadingConversations = false) }
                    }
                } else {
                    _uiState.update { it.copy(error = "API Error: ${response.code()}", isLoadingConversations = false) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(error = e.localizedMessage, isLoadingConversations = false) }
            }
        }
    }

    fun selectConversation(conversationId: Int, affiliateId: Int, name: String) {
        _uiState.update {
            it.copy(
                selectedConversationId = conversationId,
                selectedAffiliateId = affiliateId,
                selectedName = name,
                messages = emptyList() // clear current messages
            )
        }
        loadMessages()
        startPolling()
    }

    fun clearSelection() {
        _uiState.update { it.copy(selectedConversationId = null, selectedAffiliateId = null, messages = emptyList()) }
        stopPolling()
    }

    fun loadMessages() {
        val convId = _uiState.value.selectedConversationId ?: return
        val affId = _uiState.value.selectedAffiliateId ?: return
        
        viewModelScope.launch {
            if (_uiState.value.messages.isEmpty()) {
                _uiState.update { it.copy(isLoadingMessages = true) }
            }
            try {
                val response = apiService.getAdminMessages(
                    affiliateId = affId,
                    ownerType = _uiState.value.selectedTab,
                    conversationId = convId
                )
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body?.success == true && body.data != null) {
                        _uiState.update { it.copy(messages = body.data.messages, isLoadingMessages = false) }
                        // Also update status if changed
                        if (body.data.conversation?.status != null) {
                            val newStatus = body.data.conversation.status
                            if (newStatus != _uiState.value.selectedFilter && _uiState.value.selectedFilter != "all") {
                                // Status changed, maybe reload conversations list in background
                                loadConversationsSilent()
                            }
                        }
                    }
                } else {
                    _uiState.update { it.copy(isLoadingMessages = false) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoadingMessages = false) }
            }
        }
    }

    private fun loadConversationsSilent() {
        viewModelScope.launch {
            try {
                val response = apiService.getAdminConversations(
                    status = _uiState.value.selectedFilter,
                    ownerType = _uiState.value.selectedTab
                )
                if (response.isSuccessful && response.body()?.success == true) {
                    _uiState.update { it.copy(conversations = response.body()?.data?.conversations ?: emptyList()) }
                }
            } catch (e: Exception) {}
        }
    }

    private fun startPolling() {
        stopPolling()
        pollingJob = viewModelScope.launch {
            while (isActive) {
                delay(5000) // Poll every 5 seconds
                loadMessages()
                loadConversationsSilent()
            }
        }
    }

    private fun stopPolling() {
        pollingJob?.cancel()
        pollingJob = null
    }

    fun sendMessage(text: String) {
        val affId = _uiState.value.selectedAffiliateId ?: return
        val ownerType = _uiState.value.selectedTab
        
        viewModelScope.launch {
            _uiState.update { it.copy(isSendingMessage = true) }
            try {
                val request = AdminSupportSendRequest(
                    affiliateId = affId,
                    ownerType = ownerType,
                    message = text
                )
                val response = apiService.sendAdminMessage(request)
                if (response.isSuccessful && response.body()?.success == true) {
                    // reload messages
                    loadMessages()
                    loadConversationsSilent()
                } else {
                    _uiState.update { it.copy(error = response.body()?.error ?: "Failed to send message") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(error = e.localizedMessage) }
            } finally {
                _uiState.update { it.copy(isSendingMessage = false) }
            }
        }
    }


    fun uploadAndSendMessage(file: java.io.File, mimeType: String, messageText: String = "") {
        val affId = _uiState.value.selectedAffiliateId ?: return
        val ownerType = _uiState.value.selectedTab

        viewModelScope.launch {
            _uiState.update { it.copy(isUploading = true) }
            try {
                // Read file bytes
                val bytes = file.readBytes()
                val requestBody = bytes.toRequestBody(mimeType.toMediaTypeOrNull())
                val part = okhttp3.MultipartBody.Part.createFormData("file", file.name, requestBody)
                
                val response = apiService.uploadAdminSupportFile(
                    affiliateId = affId.toString().toRequestBody("text/plain".toMediaTypeOrNull()),
                    ownerType = ownerType.toRequestBody("text/plain".toMediaTypeOrNull()),
                    file = part
                )
                
                if (response.isSuccessful && response.body()?.success == true) {
                    val attachmentId = response.body()?.attachmentId
                    if (attachmentId != null) {
                        val sendReq = AdminSupportSendRequest(
                            affiliateId = affId,
                            ownerType = ownerType,
                            message = messageText,
                            attachmentId = attachmentId
                        )
                        val sendRes = apiService.sendAdminMessage(sendReq)
                        if (sendRes.isSuccessful && sendRes.body()?.success == true) {
                            loadMessages()
                            loadConversationsSilent()
                        } else {
                            _uiState.update { it.copy(error = "Failed to send attachment") }
                        }
                    }
                } else {
                    _uiState.update { it.copy(error = response.body()?.error ?: "Failed to upload file") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(error = e.localizedMessage) }
            } finally {
                _uiState.update { it.copy(isUploading = false) }
            }
        }
    }

    fun deleteMessage(messageId: Int) {
        viewModelScope.launch {
            try {
                val response = apiService.deleteAdminChatMessage(messageId = messageId)
                if (response.isSuccessful && response.body()?.status == "success") {
                    loadMessages()
                } else {
                    _uiState.update { it.copy(error = response.body()?.message ?: "Failed to delete message") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(error = e.localizedMessage) }
            }
        }
    }

    fun closeConversation() {
        val convId = _uiState.value.selectedConversationId ?: return
        viewModelScope.launch {
            try {
                val response = apiService.closeAdminConversation(AdminSupportActionRequest(convId))
                if (response.isSuccessful && response.body()?.success == true) {
                    loadMessages()
                    loadConversations()
                    clearSelection() // Exit chat view
                }
            } catch (e: Exception) {}
        }
    }

    fun reopenConversation() {
        val convId = _uiState.value.selectedConversationId ?: return
        viewModelScope.launch {
            try {
                val response = apiService.reopenAdminConversation(AdminSupportActionRequest(convId))
                if (response.isSuccessful && response.body()?.success == true) {
                    loadMessages()
                    loadConversations()
                }
            } catch (e: Exception) {}
        }
    }

    fun clearError() {
        _uiState.update { it.copy(error = null) }
    }

    override fun onCleared() {
        super.onCleared()
        stopPolling()
    }
}
