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
import com.example.affscash.data.model.ChatMessagesResponse
import com.example.affscash.data.model.SendChatMessageRequest
import com.example.affscash.data.model.SendChatMessageResponse
import com.example.affscash.data.model.UploadFileResponse
import com.example.affscash.data.model.ManagerOfferApprovalListResponse
import com.example.affscash.data.model.ReviewOfferApprovalRequest
import com.example.affscash.data.model.DefaultResponse
import com.example.affscash.data.model.DuplicateConversionsResponse
import okhttp3.MultipartBody
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.Part
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

    @GET("api/v2/manager/dashboard")
    suspend fun getManagerDashboardStats(
        @Query("action") action: String = "stats",
        @Query("from") from: String? = null,
        @Query("to") to: String? = null,
        @Query("offer_id") offerId: Int? = null,
        @Query("affiliate_id") affiliateId: Int? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null
    ): Response<com.example.affscash.data.model.ManagerDashboardResponse>

    @GET("api/v2/manager/dashboard")
    suspend fun getManagerDashboardTrend(
        @Query("action") action: String = "trend",
        @Query("from") from: String? = null,
        @Query("to") to: String? = null,
        @Query("offer_id") offerId: Int? = null,
        @Query("affiliate_id") affiliateId: Int? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null
    ): Response<com.example.affscash.data.model.ManagerTrendResponse>

    @GET("api/v2/manager/dashboard")
    suspend fun getManagerDashboardFilters(
        @Query("action") action: String = "filters"
    ): Response<com.example.affscash.data.model.ManagerFiltersResponse>

    @GET("api/v2/admin/offers")
    suspend fun getAdminOffers(): Response<AdminOfferResponse>

    @GET("api/v2/manager/offers")
    suspend fun getManagerOffers(
        @Query("action") action: String = "list",
        @Query("tab") tab: String = "regular",
        @Query("q") query: String? = null,
        @Query("category") category: String? = null,
        @Query("payout_type") payoutType: String? = null,
        @Query("offer_type") offerType: String? = null,
        @Query("status_filter") statusFilter: String? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null,
        @Query("offer_id") offerId: Int? = null,
        @Query("access_filter") accessFilter: String? = null
    ): Response<ManagerOfferResponse>

    @GET("api/v2/manager/offers")
    suspend fun getManagerOfferFilters(
        @Query("action") action: String = "filters"
    ): Response<com.example.affscash.data.model.ManagerOfferFiltersResponse>

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

    @GET("api/v2/manager/affiliates")
    suspend fun getManagerAffiliates(
        @Query("action") action: String = "list",
        @Query("q") query: String? = null,
        @Query("fraud_score_filter") fraudScoreFilter: String = "all"
    ): Response<com.example.affscash.data.model.ManagerAffiliatesResponse>

    @POST("api/v2/manager/affiliates?action=create")
    suspend fun createManagerAffiliate(@Body request: com.example.affscash.data.model.CreateAffiliateRequest): Response<com.example.affscash.data.model.BasicManagerActionResponse>

    @GET("api/v2/manager/affiliates")
    suspend fun getManagerAffiliateDetails(
        @Query("action") action: String = "view",
        @Query("id") affId: Int
    ): Response<com.example.affscash.data.model.ManagerAffiliateDetailsResponse>

    @POST("api/v2/manager/affiliates?action=edit")
    suspend fun editManagerAffiliate(@Body request: com.example.affscash.data.model.EditAffiliateRequest): Response<com.example.affscash.data.model.BasicManagerActionResponse>

    @POST("api/v2/manager/affiliates?action=update_status")
    suspend fun updateManagerAffiliateStatus(@Body request: com.example.affscash.data.model.UpdateAffiliateStatusRequest): Response<com.example.affscash.data.model.BasicManagerActionResponse>

    @POST("api/v2/manager/affiliates?action=impersonate")
    suspend fun impersonateAffiliate(@Body request: com.example.affscash.data.model.ImpersonateAffiliateRequest): Response<com.example.affscash.data.model.ImpersonateResponse>

    @GET("manager/OfferApprovalController.php?action=list")
    suspend fun getOfferApprovals(
        @Query("status") status: String,
        @Query("offer_id") offerId: Int?,
        @Query("aff") aff: String?
    ): ManagerOfferApprovalListResponse

    @POST("api/v2/stop_impersonate")
    suspend fun stopImpersonating(): Response<com.example.affscash.data.model.StopImpersonateResponse>

    @GET("api/v2/manager/smartlinks")
    suspend fun getManagerSmartlinks(
        @Query("action") action: String = "list",
        @Query("q") query: String? = null
    ): Response<com.example.affscash.data.model.ManagerSmartlinkResponse>

    @GET("api/v2/manager/smartlinks")
    suspend fun getManagerSmartlinkRequests(
        @Query("action") action: String = "requests",
        @Query("status") status: String = "all"
    ): Response<com.example.affscash.data.model.ManagerSmartlinkRequestsResponse>

    @POST("api/v2/manager/smartlinks?action=review_request")
    suspend fun reviewManagerSmartlinkRequest(@Body request: com.example.affscash.data.model.ReviewSmartlinkRequestAction): Response<com.example.affscash.data.model.ReviewSmartlinkResponse>

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

    // ── Manager Reports ──────────────────────────────────────────────────────────

    @GET("api/v2/manager/reports")
    suspend fun getManagerReports(
        @Query("tab") tab: String,
        @Query("from") from: String,
        @Query("to") to: String,
        @Query("offer_id") offerId: Int? = null,
        @Query("affiliate_id") affiliateId: Int? = null,
        @Query("country") country: String? = null,
        @Query("sub1") sub1: String? = null
    ): Response<com.example.affscash.data.model.ReportResponse>

    @GET("api/v2/manager/reports?action=filters")
    suspend fun getManagerReportFilters(): Response<com.example.affscash.data.model.ReportFiltersResponse>

    @GET("api/v2/manager/duplicate_conversions")
    suspend fun getManagerDuplicateConversions(
        @Query("from") from: String,
        @Query("to") to: String
    ): Response<DuplicateConversionsResponse>

    @GET("api/v2/manager/fraud-report")
    suspend fun getManagerFraudReport(): Response<com.example.affscash.data.model.ManagerFraudReportResponse>

    @GET("api/v2/fraud-report")
    suspend fun getFraudReport(): Response<com.example.affscash.data.model.FraudReportResponse>

    @GET("api/v2/rewards")
    suspend fun getRewards(): Response<com.example.affscash.data.model.RewardsResponse>

    @GET("api/v2/chat")
    suspend fun getChatMessages(@Query("action") action: String = "messages"): Response<ChatMessagesResponse>

    @POST("api/v2/chat")
    suspend fun sendChatMessage(@Body request: SendChatMessageRequest): Response<SendChatMessageResponse>

    @Multipart
    @POST("api/v2/chat?action=upload")
    suspend fun uploadFile(@Part file: MultipartBody.Part): Response<UploadFileResponse>
    @POST("manager/OfferApprovalController.php?action=review")
    suspend fun reviewOfferApproval(@Body request: ReviewOfferApprovalRequest): DefaultResponse

}
