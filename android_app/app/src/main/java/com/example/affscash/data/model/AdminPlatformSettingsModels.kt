package com.example.affscash.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class AdminPlatformSettingsResponse(
    val config: AdminPlatformConfig?
)

@Serializable
data class AdminPlatformSettingsRequest(
    val config: AdminPlatformConfig
)

@Serializable
data class AdminPlatformConfig(
    var app: AppSettings? = null,
    var shortener: ShortenerSettings? = null,
    var turnstile: TurnstileSettings? = null,
    var conversion: ConversionSettings? = null,
    @SerialName("fraud_reports") var fraudReports: FraudReportsSettings? = null,
    var smtp: SmtpSettings? = null,
    @SerialName("vpn_detection") var vpnDetection: VpnDetectionSettings? = null
)

@Serializable
data class AppSettings(
    var name: String? = null,
    var url: String? = null,
    var timezone: String? = null,
    @SerialName("footer_copyright") var footerCopyright: String? = null,
    @SerialName("contact_email") var contactEmail: String? = null,
    @SerialName("manager_name") var managerName: String? = null,
    @SerialName("support_email") var supportEmail: String? = null,
    @SerialName("telegram_handle") var telegramHandle: String? = null,
    @SerialName("teams_skype_url") var teamsSkypeUrl: String? = null,
    @SerialName("tracking_url") var trackingUrl: String? = null,
    @SerialName("default_theme") var defaultTheme: String? = null,
    @SerialName("dashboard_banner_style") var dashboardBannerStyle: String? = null,
    @SerialName("dashboard_card_style") var dashboardCardStyle: String? = null,
    @SerialName("trend_chart_style") var trendChartStyle: String? = null,
    @SerialName("2fa_enabled") var twoFaEnabled: String? = null,
    @SerialName("email_verification") var emailVerification: String? = null,
    @SerialName("registration_message") var registrationMessage: String? = null,
    @SerialName("advertiser_registration_enabled") var advRegEnabled: String? = null,
    @SerialName("inactivity_enabled") var inactivityEnabled: String? = null,
    @SerialName("inactivity_days") var inactivityDays: String? = null,
    @SerialName("inactivity_warn_days") var inactivityWarnDays: String? = null,
    @SerialName("refer_commission_rate") var referCommissionRate: String? = null,
    @SerialName("refer_commission_type") var referCommissionType: String? = null,
    @SerialName("budget_required") var budgetRequired: String? = null,
    @SerialName("traffic_back_url") var trafficBackUrl: String? = null,
    @SerialName("new_offer_notify") var newOfferNotify: String? = null,
    @SerialName("offer_status_notify") var offerStatusNotify: String? = null,
    @SerialName("offer_link_notify") var offerLinkNotify: String? = null
)

@Serializable
data class ShortenerSettings(
    var enabled: String? = null,
    @SerialName("api_key") var apiKey: String? = null
)

@Serializable
data class TurnstileSettings(
    var enabled: String? = null,
    @SerialName("site_key") var siteKey: String? = null,
    @SerialName("secret_key") var secretKey: String? = null
)

@Serializable
data class ConversionSettings(
    @SerialName("approval_mode") var approvalMode: String? = null,
    @SerialName("hide_fraud_rejected_reports") var hideFraudRejectedReports: String? = null,
    @SerialName("one_per_ip_enabled") var onePerIpEnabled: String? = null,
    @SerialName("one_per_ip_redirect_mode") var onePerIpRedirectMode: String? = null,
    @SerialName("one_per_ip_duration_mode") var onePerIpDurationMode: String? = null,
    @SerialName("one_per_ip_duration_days") var onePerIpDurationDays: String? = null
)

@Serializable
data class FraudReportsSettings(
    var enabled: String? = null,
    @SerialName("send_email") var sendEmail: String? = null,
    @SerialName("interval_hours") var intervalHours: String? = null
)

@Serializable
data class SmtpSettings(
    var host: String? = null,
    var port: String? = null,
    var username: String? = null,
    var password: String? = null,
    var encryption: String? = null,
    @SerialName("from_email") var fromEmail: String? = null,
    @SerialName("from_name") var fromName: String? = null
)

@Serializable
data class VpnDetectionSettings(
    var enabled: String? = null
)
