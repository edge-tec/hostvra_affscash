package net.affscash.android

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import net.affscash.android.ui.navigation.AffscashNavGraph
import dagger.hilt.android.AndroidEntryPoint

import javax.inject.Inject
import net.affscash.android.data.local.UserManager

@AndroidEntryPoint
class MainActivity : ComponentActivity() {

    @Inject
    lateinit var userManager: UserManager
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            androidx.compose.runtime.LaunchedEffect(Unit) {
                userManager.unauthFlow.collect {
                    userManager.clearUser()
                    // Restart Activity
                    val intent = android.content.Intent(this@MainActivity, MainActivity::class.java)
                    intent.addFlags(android.content.Intent.FLAG_ACTIVITY_NEW_TASK or android.content.Intent.FLAG_ACTIVITY_CLEAR_TASK)
                    startActivity(intent)
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
                    AffscashNavGraph(startDestination = startDest, userManager = userManager)
                }
            }
        }
    }
}
