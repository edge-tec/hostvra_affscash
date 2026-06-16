package com.example.affscash

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import com.example.affscash.ui.navigation.AffscashNavGraph
import dagger.hilt.android.AndroidEntryPoint

import javax.inject.Inject
import com.example.affscash.data.local.UserManager

@AndroidEntryPoint
class MainActivity : ComponentActivity() {

    @Inject
    lateinit var userManager: UserManager
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        setContent {
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
