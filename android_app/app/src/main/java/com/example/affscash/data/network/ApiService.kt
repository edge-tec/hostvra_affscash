package com.example.affscash.data.network

import com.example.affscash.data.model.AuthRequest
import com.example.affscash.data.model.AuthResponse
import com.example.affscash.data.model.DashboardResponse
import com.example.affscash.data.model.OfferResponse
import com.example.affscash.data.model.OfferDetailsResponse
import com.example.affscash.data.model.ReportResponse
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
