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
    var currentRole by androidx.compose.runtime.remember { 
        androidx.compose.runtime.mutableStateOf(userManager.getRole()) 
    }

    NavHost(navController = navController, startDestination = startDestination) {
        composable("login") {
            LoginScreen(
                onLoginSuccess = { role ->
                    currentRole = role
                    navController.navigate("main_screen") {
                        popUpTo("login") { inclusive = true }
                    }
                }
            )
        }
        
        composable("main_screen") {
            val role = currentRole ?: "affiliate"
            MainScreen(
                role = role,
                onLogout = {
                    currentRole = null
                    navController.navigate("login") {
                        popUpTo(0) { inclusive = true }
                    }
                },
                onRoleChange = { newRole ->
                    currentRole = newRole
                    navController.navigate("main_screen") {
                        popUpTo(0) { inclusive = true }
                    }
                }
            )
        }
    }
}
