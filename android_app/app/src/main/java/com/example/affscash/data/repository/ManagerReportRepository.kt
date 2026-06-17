package com.example.affscash.data.repository

import com.example.affscash.data.model.ReportFiltersResponse
import com.example.affscash.data.model.ReportResponse
import com.example.affscash.data.network.ApiService
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import retrofit2.HttpException
import java.io.IOException
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ManagerReportRepository @Inject constructor(private val apiService: ApiService) {

    fun getManagerReports(
        tab: String,
        from: String,
        to: String,
        offerId: Int? = null,
        affiliateId: Int? = null,
        country: String? = null,
        sub1: String? = null
    ): Flow<Result<ReportResponse>> = flow {
        try {
            val response = apiService.getManagerReports(tab, from, to, offerId, affiliateId, country, sub1)
            if (response.isSuccessful && response.body() != null) {
                val body = response.body()!!
                if (body.success) {
                    emit(Result.success(body))
                } else {
                    emit(Result.failure(Exception(body.error ?: "Failed to load reports.")))
                }
            } else {
                emit(Result.failure(Exception("API Error: ${response.code()}")))
            }
        } catch (e: HttpException) {
            emit(Result.failure(Exception("Network error occurred.")))
        } catch (e: IOException) {
            emit(Result.failure(Exception("No internet connection.")))
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    fun getManagerReportFilters(): Flow<Result<ReportFiltersResponse>> = flow {
        try {
            val response = apiService.getManagerReportFilters()
            if (response.isSuccessful && response.body() != null) {
                val body = response.body()!!
                if (body.success) {
                    emit(Result.success(body))
                } else {
                    emit(Result.failure(Exception(body.error ?: "Failed to load filters.")))
                }
            } else {
                emit(Result.failure(Exception("API Error: ${response.code()}")))
            }
        } catch (e: HttpException) {
            emit(Result.failure(Exception("Network error occurred.")))
        } catch (e: IOException) {
            emit(Result.failure(Exception("No internet connection.")))
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }
}
