package com.example.affscash.data.network

import com.example.affscash.data.model.AuthRequest
import com.example.affscash.data.model.AuthResponse
import com.example.affscash.data.model.DashboardResponse
import com.example.affscash.data.model.OfferResponse
import com.example.affscash.data.model.OfferDetailsResponse
import com.example.affscash.data.model.ReportResponse
import com.example.affscash.data.model.AdminDashboardResponse
import com.example.affscash.data.model.ManagerDashboardResponse
import com.example.affscash.data.model.AdminOfferResponse
import com.example.affscash.data.model.ManagerOfferResponse
import com.example.affscash.data.model.AdminAffiliateResponse
import com.example.affscash.data.model.ManagerAffiliateResponse
import com.example.affscash.data.model.ConversionResponse
import com.example.affscash.data.model.InvoiceResponse
import com.example.affscash.data.model.AffiliateActionRequest
import com.example.affscash.data.model.ManagerAffiliateActionRequest
import com.example.affscash.data.model.ImpersonateResponse
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {

    @POST("api/v2/auth")
    suspend fun login(@Body request: AuthRequest): Response<AuthResponse>

    @POST("api/v2/auth?action=logout")
    suspend fun logout(): Response<AuthResponse>

    @GET("api/v2/dashboard")
    suspend fun getDashboard(): Response<DashboardResponse>

    @GET("api/v2/admin/dashboard")
    suspend fun getAdminDashboard(): Response<AdminDashboardResponse>

    @GET("api/v2/manager/dashboard")
    suspend fun getManagerDashboard(): Response<ManagerDashboardResponse>

    @GET("api/v2/admin/offers")
    suspend fun getAdminOffers(): Response<AdminOfferResponse>

    @GET("api/v2/manager/offers")
    suspend fun getManagerOffers(): Response<ManagerOfferResponse>

    @GET("api/v2/admin/affiliates")
    suspend fun getAdminAffiliates(
        @Query("status") status: String = "all",
        @Query("search") search: String = ""
    ): Response<AdminAffiliateResponse>

    @POST("api/v2/admin/affiliate-actions")
    suspend fun adminAffiliateAction(@Body request: AffiliateActionRequest): Response<ImpersonateResponse>

    @GET("api/v2/manager/affiliates")
    suspend fun getManagerAffiliates(
        @Query("status") status: String = "all",
        @Query("search") search: String = ""
    ): Response<ManagerAffiliateResponse>

    @POST("api/v2/manager/affiliate-actions")
    suspend fun managerAffiliateAction(@Body request: ManagerAffiliateActionRequest): Response<ImpersonateResponse>

    @POST("api/v2/stop-impersonate")
    suspend fun stopImpersonate(): Response<ImpersonateResponse>

    @GET("api/v2/admin/conversions")
    suspend fun getAdminConversions(): Response<ConversionResponse>

    @GET("api/v2/manager/conversions")
    suspend fun getManagerConversions(): Response<ConversionResponse>

    @GET("api/v2/admin/invoices")
    suspend fun getAdminInvoices(): Response<InvoiceResponse>

    @GET("api/v2/manager/invoices")
    suspend fun getManagerInvoices(): Response<InvoiceResponse>

    @GET("api/v2/offers")
    suspend fun getOffers(
        @Query("action") action: String = "list",
        @Query("q") query: String? = null,
        @Query("category") category: String? = null,
        @Query("payout_type") payoutType: String? = null,
        @Query("offer_type") offerType: String? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null,
        @Query("access_filter") accessFilter: String? = null
    ): Response<OfferResponse>

    @POST("api/v2/offers?action=apply")
    suspend fun applyOffer(@Body request: com.example.affscash.data.model.ApplyOfferRequest): Response<com.example.affscash.data.model.ApplyOfferResponse>

    @GET("api/v2/reports")
    suspend fun getReports(
        @Query("tab") tab: String = "day",
        @Query("from") from: String? = null,
        @Query("to") to: String? = null,
        @Query("offer_id") offerId: Int? = null,
        @Query("country") country: String? = null,
        @Query("sub1") sub1: String? = null
    ): Response<com.example.affscash.data.model.ReportResponse>

    @GET("api/v2/reports?action=filters")
    suspend fun getReportFilters(): Response<com.example.affscash.data.model.ReportFiltersResponse>

    @GET("api/v2/smartlinks")
    suspend fun getSmartlinks(@Query("action") action: String = "list"): Response<com.example.affscash.data.model.SmartlinkResponse>

    @POST("api/v2/smartlinks?action=apply")
    suspend fun applySmartlink(@Body request: com.example.affscash.data.model.ApplySmartlinkRequest): Response<com.example.affscash.data.model.ApplySmartlinkResponse>

    @GET("api/v2/offers")
    suspend fun getOfferDetails(
        @Query("action") action: String = "details",
        @Query("id") offerId: Int
    ): Response<OfferDetailsResponse>

    @GET("api/v2/invoices")
    suspend fun getInvoices(@Query("action") action: String = "list"): Response<com.example.affscash.data.model.InvoiceResponse>

    @GET("api/v2/invoices")
    suspend fun downloadInvoicePdf(
        @Query("action") action: String = "download_pdf",
        @Query("id") invoiceId: Int
    ): Response<com.example.affscash.data.model.PdfDownloadResponse>

    @GET("api/v2/settings")
    suspend fun getSettings(@Query("action") action: String = "load"): Response<com.example.affscash.data.model.SettingsLoadResponse>

    @POST("api/v2/settings?action=update_profile")
    suspend fun updateProfile(@Body request: com.example.affscash.data.model.UpdateProfileRequest): Response<com.example.affscash.data.model.SettingsActionResponse>

    @POST("api/v2/settings?action=update_security")
    suspend fun updateSecurity(@Body request: com.example.affscash.data.model.UpdateSecurityRequest): Response<com.example.affscash.data.model.SettingsActionResponse>

    @POST("api/v2/settings?action=update_payment")
    suspend fun updatePayment(@Body request: com.example.affscash.data.model.UpdatePaymentRequest): Response<com.example.affscash.data.model.SettingsActionResponse>

    @POST("api/v2/settings?action=2fa_start")
    suspend fun start2fa(): Response<com.example.affscash.data.model.SettingsActionResponse>

    @POST("api/v2/settings?action=2fa_verify")
    suspend fun verify2fa(@Body request: com.example.affscash.data.model.TwoFactorVerifyRequest): Response<com.example.affscash.data.model.SettingsActionResponse>

    @POST("api/v2/settings?action=2fa_disable")
    suspend fun disable2fa(@Body request: com.example.affscash.data.model.TwoFactorDisableRequest): Response<com.example.affscash.data.model.SettingsActionResponse>

    @GET("api/v2/fraud-report")
    suspend fun getFraudReport(): Response<com.example.affscash.data.model.FraudReportResponse>

    @GET("api/v2/rewards")
    suspend fun getRewards(): Response<com.example.affscash.data.model.RewardsResponse>
}
