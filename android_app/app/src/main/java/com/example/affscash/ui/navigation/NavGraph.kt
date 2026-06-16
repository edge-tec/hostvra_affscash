package com.example.affscash.ui.navigation

import androidx.compose.runtime.Composable
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.example.affscash.ui.auth.LoginScreen
import com.example.affscash.ui.dashboard.DashboardScreen

@Composable
fun AffscashNavGraph() {
    val navController = rememberNavController()

    NavHost(navController = navController, startDestination = "login") {
        composable("login") {
            LoginScreen(
                onLoginSuccess = {
                    navController.navigate("main_screen") {
                        popUpTo("login") { inclusive = true }
                    }
                }
            )
        }
        
        composable("main_screen") {
            MainScreen(
                onLogout = {
                    navController.navigate("login") {
                        popUpTo("main_screen") { inclusive = true }
                    }
                }
            )
        }
    }
}
