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
    suspend fun getAdminAffiliates(): Response<AdminAffiliateResponse>

    @GET("api/v2/manager/affiliates")
    suspend fun getManagerAffiliates(): Response<ManagerAffiliateResponse>

    @GET("api/v2/admin/conversions")
    suspend fun getAdminConversions(): Response<ConversionResponse>

    @GET("api/v2/manager/conversions")
    suspend fun getManagerConversions(): Response<ConversionResponse>

    @GET("api/v2/admin/invoices")
    suspend fun getAdminInvoices(): Response<InvoiceResponse>

    @GET("api/v2/manager/invoices")
    suspend fun getManagerInvoices(): Response<InvoiceResponse>

    @GET("api/v2/offers")
    suspend fun getOffers(@Query("action") action: String = "list"): Response<OfferResponse>

    @GET("api/v2/offers")
    suspend fun getOfferDetails(
        @Query("action") action: String = "details",
        @Query("id") offerId: Int
    ): Response<OfferDetailsResponse>

    @GET("api/v2/reports")
    suspend fun getReports(
        @Query("tab") tab: String,
        @Query("from") fromDate: String,
        @Query("to") toDate: String
    ): Response<ReportResponse>
}
