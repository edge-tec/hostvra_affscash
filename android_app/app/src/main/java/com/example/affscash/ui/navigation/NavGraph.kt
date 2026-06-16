package com.example.affscash.ui.navigation

import androidx.compose.runtime.Composable
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.example.affscash.ui.auth.LoginScreen
import com.example.affscash.ui.dashboard.DashboardScreen

@Composable
fun AffscashNavGraph(startDestination: String = "login") {
    val navController = rememberNavController()

    NavHost(navController = navController, startDestination = startDestination) {
        composable("login") {
            LoginScreen(
                onLoginSuccess = { role ->
                    navController.navigate("main_screen/$role") {
                        popUpTo("login") { inclusive = true }
                    }
                }
            )
        }
        
        composable("main_screen/{role}") { backStackEntry ->
            val role = backStackEntry.arguments?.getString("role") ?: "affiliate"
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
