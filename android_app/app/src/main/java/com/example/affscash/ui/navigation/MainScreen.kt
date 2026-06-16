package com.example.affscash.ui.navigation

import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Assessment
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.LocalOffer
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.navigation.NavDestination.Companion.hierarchy
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.example.affscash.ui.dashboard.DashboardScreen
import com.example.affscash.ui.offers.OfferScreen
import com.example.affscash.ui.reports.ReportScreen

sealed class Screen(val route: String, val title: String, val icon: androidx.compose.ui.graphics.vector.ImageVector) {
    // Affiliate Screens
    object Dashboard : Screen("dashboard", "Dashboard", Icons.Filled.Home)
    object Offers : Screen("offers", "Offers", Icons.Filled.LocalOffer)
    object Reports : Screen("reports", "Reports", Icons.Filled.Assessment)

    // Admin Screens
    object AdminDashboard : Screen("admin_dashboard", "Dashboard", Icons.Filled.Home)
    object AdminOffers : Screen("admin_offers", "Offers", Icons.Filled.LocalOffer)
    object AdminUsers : Screen("admin_users", "Users", Icons.Filled.Assessment)

    // Manager Screens
    object ManagerDashboard : Screen("manager_dashboard", "Dashboard", Icons.Filled.Home)
    object ManagerOffers : Screen("manager_offers", "Offers", Icons.Filled.LocalOffer)
    object ManagerAffiliates : Screen("manager_affiliates", "Affiliates", Icons.Filled.Assessment)
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MainScreen(
    role: String,
    onLogout: () -> Unit
) {
    val navController = rememberNavController()

    val items = when (role) {
        "admin" -> listOf(Screen.AdminDashboard, Screen.AdminOffers, Screen.AdminUsers)
        "affiliate_manager" -> listOf(Screen.ManagerDashboard, Screen.ManagerOffers, Screen.ManagerAffiliates)
        else -> listOf(Screen.Dashboard, Screen.Offers, Screen.Reports)
    }
    
    val startDest = items.first().route

    Scaffold(
        bottomBar = {
            NavigationBar {
                val navBackStackEntry by navController.currentBackStackEntryAsState()
                val currentDestination = navBackStackEntry?.destination
                items.forEach { screen ->
                    NavigationBarItem(
                        icon = { Icon(screen.icon, contentDescription = null) },
                        label = { Text(screen.title) },
                        selected = currentDestination?.hierarchy?.any { it.route == screen.route } == true,
                        onClick = {
                            navController.navigate(screen.route) {
                                // Pop up to the start destination of the graph to
                                // avoid building up a large stack of destinations
                                popUpTo(navController.graph.findStartDestination().id) {
                                    saveState = true
                                }
                                // Avoid multiple copies of the same destination when
                                // reselecting the same item
                                launchSingleTop = true
                                // Restore state when reselecting a previously selected item
                                restoreState = true
                            }
                        }
                    )
                }
            }
        }
    ) { innerPadding ->
        NavHost(
            navController = navController,
            startDestination = startDest,
            modifier = Modifier.padding(innerPadding)
        ) {
            // Affiliate Screens
            composable(Screen.Dashboard.route) { DashboardScreen() }
            composable(Screen.Offers.route) { OfferScreen(onOfferClick = {}) }
            composable(Screen.Reports.route) { ReportScreen() }

            // Admin Screens
            composable(Screen.AdminDashboard.route) { com.example.affscash.ui.admin.AdminDashboardScreen() }
            composable(Screen.AdminOffers.route) { com.example.affscash.ui.admin.AdminOffersScreen() }
            composable(Screen.AdminUsers.route) { com.example.affscash.ui.admin.AdminUsersScreen() }

            // Manager Screens
            composable(Screen.ManagerDashboard.route) { com.example.affscash.ui.manager.ManagerDashboardScreen() }
            composable(Screen.ManagerOffers.route) { com.example.affscash.ui.manager.ManagerOffersScreen() }
            composable(Screen.ManagerAffiliates.route) { com.example.affscash.ui.manager.ManagerAffiliatesScreen() }
        }
    }
}
