import os

filepath = "android_app/app/src/main/java/net/affscash/android/ui/screens/admin/support/AdminSupportViewModel.kt"
with open(filepath, "r") as f:
    content = f.read()

# Add isUploading to UI State
old_ui_state = """    val isSendingMessage: Boolean = false
)"""

new_ui_state = """    val isSendingMessage: Boolean = false,
    val isUploading: Boolean = false
)"""
content = content.replace(old_ui_state, new_ui_state)

# Add uploadAndSendMessage and deleteMessage functions
functions_to_add = """
    fun uploadAndSendMessage(file: java.io.File, mimeType: String, messageText: String = "") {
        val affId = _uiState.value.selectedAffiliateId ?: return
        val ownerType = _uiState.value.selectedTab

        viewModelScope.launch {
            _uiState.update { it.copy(isUploading = true) }
            try {
                // Read file bytes
                val bytes = file.readBytes()
                val requestBody = okhttp3.RequestBody.create(okhttp3.MediaType.parse(mimeType), bytes)
                val part = okhttp3.MultipartBody.Part.createFormData("file", file.name, requestBody)
                
                val response = apiService.uploadAdminSupportFile(
                    affiliateId = okhttp3.RequestBody.create(okhttp3.MediaType.parse("text/plain"), affId.toString()),
                    ownerType = okhttp3.RequestBody.create(okhttp3.MediaType.parse("text/plain"), ownerType),
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
                val response = apiService.deleteChatMessage(messageId)
                if (response.isSuccessful && response.body()?.success == true) {
                    loadMessages()
                } else {
                    _uiState.update { it.copy(error = response.body()?.error ?: "Failed to delete message") }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(error = e.localizedMessage) }
            }
        }
    }
"""

# Insert right before closeConversation()
content = content.replace("    fun closeConversation() {", functions_to_add + "\n    fun closeConversation() {")

with open(filepath, "w") as f:
    f.write(content)
print("Updated AdminSupportViewModel.kt!")
