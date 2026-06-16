package com.example.affscash.ui.navigation

import androidx.compose.runtime.Composable
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.example.affscash.ui.auth.LoginScreen
import com.example.affscash.ui.dashboard.DashboardScreen
import com.example.affscash.data.local.UserManager

@Composable
fun AffscashNavGraph(
    startDestination: String = "login",
    userManager: UserManager
) {
    val navController = rememberNavController()

    NavHost(navController = navController, startDestination = startDestination) {
        composable("login") {
            LoginScreen(
                onLoginSuccess = { role ->
                    navController.navigate("main_screen") {
                        popUpTo("login") { inclusive = true }
                    }
                }
            )
        }
        
        composable("main_screen") {
            val role = userManager.getRole() ?: "affiliate"
            MainScreen(
                role = role,
                onLogout = {
                    navController.navigate("login") {
                        popUpTo(0) { inclusive = true }
                    }
                }
            )
        }
    }
}
