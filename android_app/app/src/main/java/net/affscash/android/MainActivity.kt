package net.affscash.android

import android.content.Intent
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.systemBarsPadding
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.mutableStateOf
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import androidx.activity.result.contract.ActivityResultContracts
import android.Manifest
import android.os.Build
import android.util.Log
import net.affscash.android.ui.navigation.AffscashNavGraph
import dagger.hilt.android.AndroidEntryPoint

import javax.inject.Inject
import net.affscash.android.data.local.UserManager

@AndroidEntryPoint
class MainActivity : ComponentActivity() {

    @Inject
    lateinit var userManager: UserManager

    // Deep link route from notification tap
    private val pendingDeepLink = mutableStateOf<String?>(null)

    private val requestPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { isGranted: Boolean ->
        // Handle the result if needed
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

        // Handle notification deep link from cold start
        handleNotificationIntent(intent)

        enableEdgeToEdge()
        setContent {
            LaunchedEffect(Unit) {
                userManager.unauthFlow.collect {
                    userManager.clearUser()
                    // Restart Activity
                    val restartIntent = Intent(this@MainActivity, MainActivity::class.java)
                    restartIntent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK)
                    startActivity(restartIntent)
                    finish()
                }
            }
            MaterialTheme {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    val role = userManager.getRole()
                    val startDest = if (role != null) "main_screen" else "login"
                    val deepLink = pendingDeepLink.value
                    AffscashNavGraph(
                        startDestination = startDest,
                        userManager = userManager,
                        initialDeepLink = deepLink
                    )
                    // Consume deep link after passing it
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
        if (intent?.getBooleanExtra("from_notification", false) == true) {
            val deepLinkRoute = intent.getStringExtra("deep_link_route")
            val notificationType = intent.getStringExtra("notification_type")
            Log.d("MainActivity", "Notification deep link: route=$deepLinkRoute type=$notificationType")

            if (!deepLinkRoute.isNullOrEmpty()) {
                pendingDeepLink.value = deepLinkRoute
            } else if (!notificationType.isNullOrEmpty()) {
                // Fallback: navigate to notifications screen
                pendingDeepLink.value = "notifications"
            }
        }
    }
}
