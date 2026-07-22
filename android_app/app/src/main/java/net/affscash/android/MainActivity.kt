package net.affscash.android

import android.Manifest
import android.content.Intent
import android.os.Build
import android.os.Bundle
import android.util.Log
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.mutableStateOf
import androidx.compose.ui.Modifier
import androidx.fragment.app.FragmentActivity
import dagger.hilt.android.AndroidEntryPoint
import net.affscash.android.data.local.SecureStorageManager
import net.affscash.android.data.local.UserManager
import net.affscash.android.service.FcmTokenManager
import net.affscash.android.ui.navigation.AffscashNavGraph
import net.affscash.android.util.BiometricAuthManager
import javax.inject.Inject

@AndroidEntryPoint
class MainActivity : FragmentActivity() {

    @Inject
    lateinit var userManager: UserManager

    @Inject
    lateinit var fcmTokenManager: FcmTokenManager

    @Inject
    lateinit var secureStorageManager: SecureStorageManager

    @Inject
    lateinit var biometricAuthManager: BiometricAuthManager

    private val pendingDeepLink = mutableStateOf<String?>(null)

    private val requestPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { isGranted: Boolean ->
        // Handle post notification permission result
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (androidx.core.content.ContextCompat.checkSelfPermission(
                    this,
                    Manifest.permission.POST_NOTIFICATIONS
                ) != android.content.pm.PackageManager.PERMISSION_GRANTED
            ) {
                requestPermissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
            }
        }

        handleNotificationIntent(intent)

        val isSessionActive = userManager.getRole() != null || secureStorageManager.hasValidSession()

        if (isSessionActive) {
            fcmTokenManager.ensureTokenRegistered()
        }

        enableEdgeToEdge()
        setContent {
            LaunchedEffect(Unit) {
                userManager.unauthFlow.collect {
                    userManager.clearUser()
                    secureStorageManager.clearSession()
                    val restartIntent = Intent(this@MainActivity, MainActivity::class.java)
                    restartIntent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK)
                    startActivity(restartIntent)
                    finish()
                }
            }
            MaterialTheme {
                Surface(
                    modifier = Modifier.fillMaxSize().safeDrawingPadding(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    val role = userManager.getRole() ?: secureStorageManager.getUserRole()
                    val startDest = if (role != null && (secureStorageManager.isAutoLoginEnabled || !secureStorageManager.isRememberMeEnabled)) "main_screen" else "login"
                    val deepLink = pendingDeepLink.value
                    
                    AffscashNavGraph(
                        startDestination = startDest,
                        userManager = userManager,
                        initialDeepLink = deepLink
                    )
                    
                    LaunchedEffect(deepLink) {
                        if (deepLink != null) {
                            pendingDeepLink.value = null
                        }
                    }
                }
            }
        }
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        handleNotificationIntent(intent)
    }

    private fun handleNotificationIntent(intent: Intent?) {
        val fromNotificationStr = intent?.getStringExtra("from_notification")
        val notificationTypeStr = intent?.getStringExtra("notification_type")
        val fromNotification = intent?.getBooleanExtra("from_notification", false) == true || 
                               fromNotificationStr == "true" ||
                               !notificationTypeStr.isNullOrEmpty()

        if (fromNotification) {
            val deepLinkRoute = intent?.getStringExtra("deep_link_route")
            val notificationType = intent?.getStringExtra("notification_type")
            Log.d("MainActivity", "Notification deep link: route=$deepLinkRoute type=$notificationType")

            if (!deepLinkRoute.isNullOrEmpty()) {
                pendingDeepLink.value = deepLinkRoute
            } else if (!notificationType.isNullOrEmpty()) {
                pendingDeepLink.value = "notifications"
            }
        }
    }
}
