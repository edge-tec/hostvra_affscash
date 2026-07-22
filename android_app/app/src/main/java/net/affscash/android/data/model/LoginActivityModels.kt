package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class LiveUserSession(
    val id: Int,
    @SerialName("session_id") val sessionId: String,
    @SerialName("user_id") val userId: Int,
    @SerialName("user_name") val userName: String = "",
    val email: String = "",
    val role: String = "",
    @SerialName("affiliate_code") val affiliateCode: String? = null,
    @SerialName("ip_address") val ipAddress: String = "",
    val country: String = "",
    @SerialName("country_code") val countryCode: String = "",
    val city: String = "",
    @SerialName("device_type") val deviceType: String = "",
    val browser: String = "",
    val os: String = "",
    @SerialName("platform_source") val platformSource: String = "Web",
    @SerialName("current_page") val currentPage: String = "/",
    @SerialName("logged_in_at") val loggedInAt: String = "",
    @SerialName("last_active") val lastActive: String = "",
    @SerialName("idle_sec") val idleSec: Int = 0,
    @SerialName("session_sec") val sessionSec: Int = 0
)

@Serializable
data class LiveUsersData(
    @SerialName("live_users") val liveUsers: List<LiveUserSession> = emptyList(),
    val total: Int = 0
)

@Serializable
data class LiveUsersResponse(
    val status: String = "success",
    val message: String? = null,
    val data: LiveUsersData? = null
)

@Serializable
data class LoginLogItem(
    val id: Int,
    @SerialName("user_id") val userId: Int,
    @SerialName("user_name") val userName: String = "",
    val email: String = "",
    val role: String = "",
    @SerialName("affiliate_code") val affiliateCode: String? = null,
    @SerialName("ip_address") val ipAddress: String = "",
    val country: String = "",
    @SerialName("country_code") val countryCode: String = "",
    val city: String = "",
    @SerialName("device_type") val deviceType: String = "",
    val browser: String = "",
    val os: String = "",
    @SerialName("platform_source") val platformSource: String = "Web",
    @SerialName("login_time") val loginTime: String = "",
    @SerialName("is_active") val isActive: Boolean = false
)

@Serializable
data class LoginLogsData(
    @SerialName("login_logs") val loginLogs: List<LoginLogItem> = emptyList(),
    val total: Int = 0
)

@Serializable
data class LoginLogsResponse(
    val status: String = "success",
    val message: String? = null,
    val data: LoginLogsData? = null
)
