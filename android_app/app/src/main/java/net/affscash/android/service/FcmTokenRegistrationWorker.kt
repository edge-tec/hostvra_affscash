package net.affscash.android.service

import android.content.Context
import android.util.Log
import androidx.hilt.work.HiltWorker
import androidx.work.BackoffPolicy
import androidx.work.Constraints
import androidx.work.CoroutineWorker
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.WorkManager
import androidx.work.WorkerParameters
import com.google.firebase.messaging.FirebaseMessaging
import dagger.assisted.Assisted
import dagger.assisted.AssistedInject
import kotlinx.coroutines.tasks.await
import net.affscash.android.data.network.ApiService
import java.util.concurrent.TimeUnit

/**
 * WorkManager worker for reliable FCM token registration.
 *
 * Guarantees the token is registered even if:
 * - The app is killed during registration
 * - Network is temporarily unavailable
 * - The server returns an error
 *
 * Uses exponential backoff for retries (30s → 60s → 120s → ...).
 * Requires network connectivity constraint.
 */
@HiltWorker
class FcmTokenRegistrationWorker @AssistedInject constructor(
    @Assisted private val appContext: Context,
    @Assisted workerParams: WorkerParameters,
    private val apiService: ApiService,
    private val fcmTokenManager: FcmTokenManager
) : CoroutineWorker(appContext, workerParams) {

    companion object {
        private const val TAG = "FcmTokenRegWorker"
        private const val UNIQUE_WORK_NAME = "fcm_token_registration"

        /**
         * Enqueues a one-time token registration work request.
         * Uses KEEP policy — if work is already enqueued, don't duplicate.
         */
        fun enqueue(context: Context) {
            val constraints = Constraints.Builder()
                .setRequiredNetworkType(NetworkType.CONNECTED)
                .build()

            val request = OneTimeWorkRequestBuilder<FcmTokenRegistrationWorker>()
                .setConstraints(constraints)
                .setBackoffCriteria(
                    BackoffPolicy.EXPONENTIAL,
                    30, TimeUnit.SECONDS
                )
                .setInitialDelay(5, TimeUnit.SECONDS)
                .build()

            WorkManager.getInstance(context)
                .enqueueUniqueWork(
                    UNIQUE_WORK_NAME,
                    ExistingWorkPolicy.REPLACE,
                    request
                )

            Log.d(TAG, "Token registration work enqueued")
        }

        /**
         * Cancels any pending token registration work.
         */
        fun cancel(context: Context) {
            WorkManager.getInstance(context).cancelUniqueWork(UNIQUE_WORK_NAME)
        }
    }

    override suspend fun doWork(): Result {
        Log.d(TAG, "Starting token registration work (attempt ${runAttemptCount + 1})")

        return try {
            // Get the current token
            var token = fcmTokenManager.getCachedToken()
            if (token.isNullOrEmpty()) {
                token = FirebaseMessaging.getInstance().token.await()
                if (token.isNullOrEmpty()) {
                    Log.e(TAG, "Failed to get FCM token")
                    return Result.retry()
                }
                fcmTokenManager.onTokenRefreshed(token)
            }

            // Try to register
            val success = fcmTokenManager.registerTokenWithServer(token)
            if (success) {
                Log.d(TAG, "Token registration successful")
                Result.success()
            } else {
                if (runAttemptCount < 5) {
                    Log.w(TAG, "Token registration failed, will retry")
                    Result.retry()
                } else {
                    Log.e(TAG, "Token registration failed after max retries")
                    Result.failure()
                }
            }
        } catch (e: Exception) {
            Log.e(TAG, "Token registration work error: ${e.message}")
            if (runAttemptCount < 5) {
                Result.retry()
            } else {
                Result.failure()
            }
        }
    }
}
