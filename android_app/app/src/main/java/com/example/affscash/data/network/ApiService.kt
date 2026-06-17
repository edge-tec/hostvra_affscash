package com.example.affscash.data.network

import com.example.affscash.data.model.*
import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.Response
import retrofit2.http.*

interface ApiService {

    @POST("api/v2/auth")
    suspend fun login(@Body request: AuthRequest): Response<AuthResponse>

    @POST("api/v2/auth?action=logout")
    suspend fun logout(): Response<AuthResponse>

    @GET("api/v2/dashboard")
    suspend fun getDashboard(): Response<DashboardResponse>

    @GET("api/affiliate-analytics?action=stats")
    suspend fun getAffiliateAnalyticsStats(
        @Query("from") from: String,
        @Query("to") to: String,
        @Query("offer_id") offerId: Int? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null
    ): Response<DashboardAnalyticsStatsResponse>

    @GET("api/affiliate-analytics?action=trend")
    suspend fun getAffiliateAnalyticsTrend(
        @Query("from") from: String,
        @Query("to") to: String,
        @Query("offer_id") offerId: Int? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null
    ): Response<DashboardTrendChartResponse>

    @GET("api/affiliate-analytics?action=devices")
    suspend fun getAffiliateAnalyticsDevices(
        @Query("from") from: String,
        @Query("to") to: String,
        @Query("offer_id") offerId: Int? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null
    ): Response<DashboardPieChartResponse>

    @GET("api/affiliate-analytics?action=browsers")
    suspend fun getAffiliateAnalyticsBrowsers(
        @Query("from") from: String,
        @Query("to") to: String,
        @Query("offer_id") offerId: Int? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null
    ): Response<DashboardPieChartResponse>

    @GET("api/affiliate-analytics?action=sources")
    suspend fun getAffiliateAnalyticsSources(
        @Query("from") from: String,
        @Query("to") to: String,
        @Query("offer_id") offerId: Int? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null
    ): Response<DashboardPieChartResponse>

    @GET("api/affiliate-analytics?action=hourly")
    suspend fun getAffiliateAnalyticsHourly(
        @Query("from") from: String,
        @Query("to") to: String,
        @Query("offer_id") offerId: Int? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null
    ): Response<DashboardHourlyResponse>

    @GET("api/affiliate-analytics?action=countries")
    suspend fun getAffiliateAnalyticsCountries(
        @Query("from") from: String,
        @Query("to") to: String,
        @Query("offer_id") offerId: Int? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null
    ): Response<DashboardCountriesResponse>

    @GET("api/affiliate-analytics?action=offers")
    suspend fun getAffiliateAnalyticsOffers(
        @Query("from") from: String,
        @Query("to") to: String,
        @Query("offer_id") offerId: Int? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null
    ): Response<DashboardOffersResponse>

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
    ): Response<ManagerDashboardResponse>

    @GET("api/v2/manager/dashboard")
    suspend fun getManagerDashboardTrend(
        @Query("action") action: String = "trend",
        @Query("from") from: String? = null,
        @Query("to") to: String? = null,
        @Query("offer_id") offerId: Int? = null,
        @Query("affiliate_id") affiliateId: Int? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null
    ): Response<ManagerTrendResponse>

    @GET("api/v2/manager/dashboard")
    suspend fun getManagerDashboardExtra(
        @Query("action") action: String = "extra",
        @Query("from") from: String,
        @Query("to") to: String,
        @Query("offer_id") offerId: Int? = null,
        @Query("affiliate_id") affiliateId: Int? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null
    ): Response<ManagerDashboardExtraResponse>

    @GET("api/v2/manager/dashboard")
    suspend fun getManagerDashboardFilters(
        @Query("action") action: String = "filters"
    ): Response<ManagerFiltersResponse>

    @GET("api/v2/admin/offers")
    suspend fun getAdminOffers(
        @Query("q") query: String? = null,
        @Query("category") category: String? = null,
        @Query("payout_type") payoutType: String? = null,
        @Query("status") status: String? = null,
        @Query("offer_type") offerType: String? = null,
        @Query("country") country: String? = null,
        @Query("device") device: String? = null,
        @Query("offer_id") offerId: Int? = null,
        @Query("access") access: String? = null,
        @Query("in_house") inHouse: Boolean? = null
    ): Response<AdminOfferResponse>

    @POST("api/v2/admin/offers/action?action=create")
    suspend fun createAdminOffer(@Body request: AdminOfferCreateRequest): Response<AdminOfferActionResponse>

    @POST("api/v2/admin/offers/action?action=edit")
    suspend fun editAdminOffer(@Body request: AdminOfferEditRequest): Response<AdminOfferActionResponse>

    @POST("api/v2/admin/offers/action?action=update_status")
    suspend fun updateAdminOfferStatus(@Body request: Map<String, String>): Response<AdminOfferActionResponse>

    @POST("api/v2/admin/offers/action?action=delete")
    suspend fun deleteAdminOffer(@Body request: Map<String, Int>): Response<AdminOfferActionResponse>

    @GET("api/v2/admin/offer-approvals?action=list")
    suspend fun getAdminOfferApprovals(
        @Query("status") status: String,
        @Query("offer_id") offerId: Int?,
        @Query("aff") aff: String?
    ): ManagerOfferApprovalListResponse

    @POST("api/v2/admin/offer-approvals?action=review")
    suspend fun reviewAdminOfferApproval(@Body request: ReviewOfferApprovalRequest): DefaultResponse

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
    ): Response<ManagerOfferFiltersResponse>

    @GET("api/v2/admin/affiliates")
    suspend fun getAdminAffiliates(
        @Query("status") status: String = "all",
        @Query("search") search: String = ""
    ): Response<AdminAffiliateResponse>

    @POST("api/v2/admin/affiliate-actions")
    suspend fun adminAffiliateAction(@Body request: AffiliateActionRequest): Response<ImpersonateResponse>

    @GET("api/v2/admin/advertisers")
    suspend fun getAdminAdvertisers(
        @Query("action") action: String = "list",
        @Query("status") status: String = "all",
        @Query("search") search: String = ""
    ): Response<AdminAdvertiserResponse>

    @GET("api/v2/admin/advertisers")
    suspend fun getAdminAdvertiserDetails(
        @Query("action") action: String = "view",
        @Query("id") id: Int
    ): Response<AdminAdvertiserDetailsResponse>

    @POST("api/v2/admin/advertiser-actions")
    suspend fun adminAdvertiserActionCreate(@Body request: CreateAdvertiserRequest): Response<BasicManagerActionResponse>

    @POST("api/v2/admin/advertiser-actions")
    suspend fun adminAdvertiserActionEdit(@Body request: EditAdvertiserRequest): Response<BasicManagerActionResponse>

    @POST("api/v2/admin/advertiser-actions")
    suspend fun adminAdvertiserActionDelete(@Body request: AdminAdvertiserActionRequest): Response<BasicManagerActionResponse>

    @POST("api/v2/admin/advertiser-actions")
    suspend fun adminAdvertiserActionStatus(@Body request: AdminAdvertiserActionRequest): Response<BasicManagerActionResponse>

    @POST("api/v2/admin/advertiser-actions")
    suspend fun adminAdvertiserActionImpersonate(@Body request: AdminAdvertiserActionRequest): Response<ImpersonateResponse>

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
    suspend fun getManagerInvoices(): Response<ManagerInvoicesResponse>

    // --- Manager Profile ---
    @GET("api/v2/manager/profile?action=load")
    suspend fun getManagerProfile(): Response<ManagerProfileResponse>

    @Multipart
    @POST("api/v2/manager/profile?action=update_profile")
    suspend fun updateManagerProfile(
        @Part("first_name") firstName: RequestBody,
        @Part("last_name") lastName: RequestBody,
        @Part("email") email: RequestBody,
        @Part("company") company: RequestBody?,
        @Part("phone") phone: RequestBody?,
        @Part("skype") skype: RequestBody?,
        @Part("telegram") telegram: RequestBody?,
        @Part("discord") discord: RequestBody?,
        @Part profilePic: MultipartBody.Part?
    ): Response<SimpleResponse>

    @POST("api/v2/manager/profile?action=update_security")
    suspend fun updateManagerSecurity(@Body request: Map<String, String>): Response<SimpleResponse>

    @POST("api/v2/manager/profile?action=update_payment")
    suspend fun updateManagerPayment(@Body request: Map<String, String>): Response<SimpleResponse>

    @POST("api/v2/manager/profile?action=2fa_start")
    suspend fun startManager2fa(): Response<TwoFactorStartResponse>

    @POST("api/v2/manager/profile?action=2fa_verify")
    suspend fun verifyManager2fa(@Body request: Map<String, String>): Response<SimpleResponse>

    @POST("api/v2/manager/profile?action=2fa_disable")
    suspend fun disableManager2fa(@Body request: Map<String, String>): Response<SimpleResponse>

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
    suspend fun applyOffer(@Body request: ApplyOfferRequest): Response<ApplyOfferResponse>

    @GET("api/v2/reports")
    suspend fun getReports(
        @Query("tab") tab: String = "day",
        @Query("from") from: String? = null,
        @Query("to") to: String? = null,
        @Query("offer_id") offerId: Int? = null,
        @Query("country") country: String? = null,
        @Query("sub1") sub1: String? = null
    ): Response<ReportResponse>

    @GET("api/v2/reports?action=filters")
    suspend fun getReportFilters(): Response<ReportFiltersResponse>

    @GET("api/v2/smartlinks")
    suspend fun getSmartlinks(@Query("action") action: String = "list"): Response<SmartlinkResponse>

    @POST("api/v2/smartlinks?action=apply")
    suspend fun applySmartlink(@Body request: ApplySmartlinkRequest): Response<ApplySmartlinkResponse>

    @GET("api/v2/manager/affiliates")
    suspend fun getManagerAffiliates(
        @Query("action") action: String = "list",
        @Query("q") query: String? = null,
        @Query("fraud_score_filter") fraudScoreFilter: String = "all",
        @Query("status") status: String = "all"
    ): Response<ManagerAffiliatesResponse>

    @POST("api/v2/manager/affiliates?action=create")
    suspend fun createManagerAffiliate(@Body request: CreateAffiliateRequest): Response<BasicManagerActionResponse>

    @GET("api/v2/manager/affiliates")
    suspend fun getManagerAffiliateDetails(
        @Query("action") action: String = "view",
        @Query("id") affId: Int
    ): Response<ManagerAffiliateDetailsResponse>

    @POST("api/v2/manager/affiliates?action=edit")
    suspend fun editManagerAffiliate(@Body request: EditAffiliateRequest): Response<BasicManagerActionResponse>

    @POST("api/v2/manager/affiliates?action=update_status")
    suspend fun updateManagerAffiliateStatus(@Body request: UpdateAffiliateStatusRequest): Response<BasicManagerActionResponse>

    @POST("api/v2/manager/affiliates?action=impersonate")
    suspend fun impersonateAffiliate(@Body request: ImpersonateAffiliateRequest): Response<ImpersonateResponse>

    @GET("api/v2/manager/offer-approvals?action=list")
    suspend fun getOfferApprovals(
        @Query("status") status: String,
        @Query("offer_id") offerId: Int?,
        @Query("aff") aff: String?
    ): ManagerOfferApprovalListResponse

    @POST("api/v2/stop_impersonate")
    suspend fun stopImpersonating(): Response<StopImpersonateResponse>

    @GET("api/v2/manager/smartlinks")
    suspend fun getManagerSmartlinks(
        @Query("action") action: String = "list",
        @Query("q") query: String? = null
    ): Response<ManagerSmartlinkResponse>

    @GET("api/v2/manager/smartlinks")
    suspend fun getManagerSmartlinkRequests(
        @Query("action") action: String = "requests",
        @Query("status") status: String = "all"
    ): Response<ManagerSmartlinkRequestsResponse>

    @POST("api/v2/manager/smartlinks?action=review_request")
    suspend fun reviewManagerSmartlinkRequest(@Body request: ReviewSmartlinkRequestAction): Response<ReviewSmartlinkResponse>

    @GET("api/v2/offers")
    suspend fun getOfferDetails(
        @Query("action") action: String = "details",
        @Query("id") offerId: Int
    ): Response<OfferDetailsResponse>

    @GET("api/v2/invoices")
    suspend fun getInvoices(@Query("action") action: String = "list"): Response<InvoiceResponse>

    @GET("api/v2/invoices")
    suspend fun downloadInvoicePdf(
        @Query("action") action: String = "download_pdf",
        @Query("id") invoiceId: Int
    ): Response<PdfDownloadResponse>

    @GET("api/v2/settings")
    suspend fun getSettings(@Query("action") action: String = "load"): Response<SettingsLoadResponse>

    @POST("api/v2/settings?action=update_profile")
    suspend fun updateProfile(@Body request: UpdateProfileRequest): Response<SettingsActionResponse>

    @POST("api/v2/settings?action=update_security")
    suspend fun updateSecurity(@Body request: UpdateSecurityRequest): Response<SettingsActionResponse>

    @POST("api/v2/settings?action=update_payment")
    suspend fun updatePayment(@Body request: UpdatePaymentRequest): Response<SettingsActionResponse>

    @POST("api/v2/settings?action=2fa_start")
    suspend fun start2fa(): Response<SettingsActionResponse>

    @POST("api/v2/settings?action=2fa_verify")
    suspend fun verify2fa(@Body request: TwoFactorVerifyRequest): Response<SettingsActionResponse>

    @POST("api/v2/settings?action=2fa_disable")
    suspend fun disable2fa(@Body request: TwoFactorDisableRequest): Response<SettingsActionResponse>

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
    ): Response<ReportResponse>

    @GET("api/v2/manager/reports?action=filters")
    suspend fun getManagerReportFilters(): Response<ReportFiltersResponse>

    @GET("api/v2/manager/duplicate_conversions")
    suspend fun getManagerDuplicateConversions(
        @Query("from") from: String,
        @Query("to") to: String
    ): Response<DuplicateConversionsResponse>

    @GET("api/v2/manager/fraud-report")
    suspend fun getManagerFraudReport(): Response<ManagerFraudReportResponse>

    @GET("api/v2/fraud-report")
    suspend fun getFraudReport(): Response<FraudReportResponse>

    @GET("api/v2/rewards")
    suspend fun getRewards(): Response<RewardsResponse>

    @GET("api/v2/chat")
    suspend fun getChatMessages(@Query("action") action: String = "messages"): Response<ChatMessagesResponse>

    @POST("api/v2/chat")
    suspend fun sendChatMessage(@Body request: SendChatMessageRequest): Response<SendChatMessageResponse>

    @Multipart
    @POST("api/v2/chat?action=upload")
    suspend fun uploadFile(@Part file: MultipartBody.Part): Response<UploadFileResponse>

    @POST("api/v2/manager/offer-approvals?action=review")
    suspend fun reviewOfferApproval(@Body request: ReviewOfferApprovalRequest): DefaultResponse

    @GET("api/v2/shop")
    suspend fun getShopData(): Response<ShopListResponse>

    @POST("api/v2/shop?action=place_order")
    suspend fun placeShopOrder(@Body request: PlaceOrderRequest): Response<ShopOrderResponse>

    // --- News ---
    @GET("api/v2/news?action=list")
    suspend fun getNews(): Response<NewsListResponse>

    @POST("api/v2/news?action=mark_read")
    suspend fun markNewsAsRead(@Body request: MarkNewsReadRequest): Response<SimpleResponse>

    @POST("api/v2/news?action=mark_all_read")
    suspend fun markAllNewsAsRead(): Response<SimpleResponse>

    @GET("api/v2/notifications?action=list")
    suspend fun getNotifications(): Response<NotificationsResponse>

    @POST("api/v2/notifications?action=mark_read")
    suspend fun markNotificationAsRead(@Body request: MarkNotificationRequest): Response<SimpleResponse>

}
