package com.example.affscash.data.model

import com.google.gson.annotations.SerializedName

data class AdminPlatformSettingsResponse(
    val config: AdminPlatformConfig?
)

data class AdminPlatformSettingsRequest(
    val config: AdminPlatformConfig
)

data class AdminPlatformConfig(
    var app: AppSettings? = null,
    var shortener: ShortenerSettings? = null,
    var turnstile: TurnstileSettings? = null,
    var conversion: ConversionSettings? = null,
    @SerializedName("fraud_reports") var fraudReports: FraudReportsSettings? = null,
    var smtp: SmtpSettings? = null,
    @SerializedName("vpn_detection") var vpnDetection: VpnDetectionSettings? = null
)

data class AppSettings(
    var name: String? = null,
    var url: String? = null,
    var timezone: String? = null,
    @SerializedName("footer_copyright") var footerCopyright: String? = null,
    @SerializedName("contact_email") var contactEmail: String? = null,
    @SerializedName("manager_name") var managerName: String? = null,
    @SerializedName("support_email") var supportEmail: String? = null,
    @SerializedName("telegram_handle") var telegramHandle: String? = null,
    @SerializedName("teams_skype_url") var teamsSkypeUrl: String? = null,
    @SerializedName("tracking_url") var trackingUrl: String? = null,
    @SerializedName("default_theme") var defaultTheme: String? = null,
    @SerializedName("dashboard_banner_style") var dashboardBannerStyle: String? = null,
    @SerializedName("dashboard_card_style") var dashboardCardStyle: String? = null,
    @SerializedName("trend_chart_style") var trendChartStyle: String? = null,
    @SerializedName("2fa_enabled") var twoFaEnabled: String? = null,
    @SerializedName("email_verification") var emailVerification: String? = null,
    @SerializedName("registration_message") var registrationMessage: String? = null,
    @SerializedName("advertiser_registration_enabled") var advRegEnabled: String? = null,
    @SerializedName("inactivity_enabled") var inactivityEnabled: String? = null,
    @SerializedName("inactivity_days") var inactivityDays: String? = null,
    @SerializedName("inactivity_warn_days") var inactivityWarnDays: String? = null,
    @SerializedName("refer_commission_rate") var referCommissionRate: String? = null,
    @SerializedName("refer_commission_type") var referCommissionType: String? = null,
    @SerializedName("budget_required") var budgetRequired: String? = null,
    @SerializedName("traffic_back_url") var trafficBackUrl: String? = null,
    @SerializedName("new_offer_notify") var newOfferNotify: String? = null,
    @SerializedName("offer_status_notify") var offerStatusNotify: String? = null,
    @SerializedName("offer_link_notify") var offerLinkNotify: String? = null
)

data class ShortenerSettings(
    var enabled: String? = null,
    @SerializedName("api_key") var apiKey: String? = null
)

data class TurnstileSettings(
    var enabled: String? = null,
    @SerializedName("site_key") var siteKey: String? = null,
    @SerializedName("secret_key") var secretKey: String? = null
)

data class ConversionSettings(
    @SerializedName("approval_mode") var approvalMode: String? = null,
    @SerializedName("hide_fraud_rejected_reports") var hideFraudRejectedReports: String? = null,
    @SerializedName("one_per_ip_enabled") var onePerIpEnabled: String? = null,
    @SerializedName("one_per_ip_redirect_mode") var onePerIpRedirectMode: String? = null,
    @SerializedName("one_per_ip_duration_mode") var onePerIpDurationMode: String? = null,
    @SerializedName("one_per_ip_duration_days") var onePerIpDurationDays: String? = null
)

data class FraudReportsSettings(
    var enabled: String? = null,
    @SerializedName("send_email") var sendEmail: String? = null,
    @SerializedName("interval_hours") var intervalHours: String? = null
)

data class SmtpSettings(
    var host: String? = null,
    var port: String? = null,
    var username: String? = null,
    var password: String? = null,
    var encryption: String? = null,
    @SerializedName("from_email") var fromEmail: String? = null,
    @SerializedName("from_name") var fromName: String? = null
)

data class VpnDetectionSettings(
    var enabled: String? = null
)
