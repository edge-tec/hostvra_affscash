package com.example.affscash.ui.navigation

import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Assessment
import androidx.compose.material.icons.filled.Article
import androidx.compose.material.icons.filled.Chat
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.LocalOffer
import androidx.compose.material.icons.filled.Link
import androidx.compose.material.icons.filled.PictureAsPdf
import androidx.compose.material.icons.filled.MonetizationOn
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Receipt
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.VpnKey
import androidx.compose.material.icons.filled.HomeRepairService
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.SupervisorAccount
import androidx.compose.material.icons.filled.Shield
import androidx.compose.material.icons.filled.Group
import androidx.compose.material.icons.filled.VisibilityOff
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
import com.example.affscash.ui.admin.AdminAdvertisersScreen
import com.example.affscash.ui.admin.AdminAdvertiserFormScreen

sealed class Screen(val route: String, val title: String, val icon: androidx.compose.ui.graphics.vector.ImageVector) {
    // Affiliate Screens
    object Dashboard : Screen("dashboard", "Dashboard", Icons.Filled.Home)
    object Offers : Screen("offers", "Offers", Icons.Filled.LocalOffer)
    object Smartlinks : Screen("smartlinks", "Smartlinks", Icons.Filled.Link)
    object Reports : Screen("reports", "Reports", Icons.Filled.Assessment)
    object Invoices : Screen("invoices", "Invoices", Icons.Filled.PictureAsPdf)
    object AffiliateSettings : Screen("affiliate_settings", "Settings", Icons.Filled.Settings)
    object FraudReport : Screen("fraud_report", "Fraud Report", Icons.Filled.Assessment)
    object Rewards : Screen("rewards", "Milestones", Icons.Filled.MonetizationOn)
    object Shop : Screen("shop", "Rewards Shop", Icons.Filled.LocalOffer) // Add Shop screen
    object News : Screen("news", "News", Icons.Filled.Article) // Add News screen
    object Notifications : Screen("notifications", "Notifications", Icons.Filled.Notifications)
    object Chat : Screen("chat", "Chat", Icons.Filled.Chat)

    // Admin Screens
    object AdminDashboard : Screen("admin_dashboard", "Dashboard", Icons.Filled.Home)
    object AdminOffers : Screen("admin_offers", "Offers", Icons.Filled.LocalOffer)
    object AdminPrivateOffers : Screen("admin_private_offers", "Private Offers", Icons.Filled.VpnKey)
    object AdminInHouseOffers : Screen("admin_inhouse_offers", "In-House Offers", Icons.Filled.HomeRepairService)
    object AdminOfferApprovals : Screen("admin_offer_approvals", "Approvals", Icons.Filled.CheckCircle)
    object AdminSmartlinks : Screen("admin_smartlinks", "Smartlinks", Icons.Filled.Link)
    object AdminSmartlinkRequests : Screen("admin_smartlink_requests", "SL Requests", Icons.Filled.Assessment)
    object AdminSmartlinkCreate : Screen("admin_smartlink_create", "Create Smartlink", Icons.Filled.Add)
    class AdminSmartlinkEdit(id: Int) : Screen("admin_smartlink_edit/$id", "Edit Smartlink", Icons.Filled.Edit) {
        companion object {
            const val route = "admin_smartlink_edit/{smartlinkId}"
            fun createRoute(smartlinkId: Int) = "admin_smartlink_edit/$smartlinkId"
        }
    }
    object AdminOfferCreate : Screen("admin_offer_create", "Create Offer", Icons.Filled.Add)
    object AdminInHouseOfferCreate : Screen("admin_inhouse_offer_create", "Create In-House Offer", Icons.Filled.Add)
    class AdminOfferEdit(id: Int) : Screen("admin_offer_edit/$id", "Edit Offer", Icons.Filled.Edit) {
        companion object {
            const val route = "admin_offer_edit/{offerId}"
            fun createRoute(offerId: Int) = "admin_offer_edit/$offerId"
        }
    }
    class AdminPrivateOfferDetail(id: Int) : Screen("admin_private_offer_detail/$id", "Manage Private Offer", Icons.Filled.Settings) {
        companion object {
            const val route = "admin_private_offer_detail/{offerId}"
            fun createRoute(offerId: Int) = "admin_private_offer_detail/$offerId"
        }
    }
    object AdminUsers : Screen("admin_users", "Users", Icons.Filled.Assessment)
    object AdminAdvertisers : Screen("admin_advertisers", "Advertisers", Icons.Filled.SupervisorAccount)
    object AdminAdvertiserCreate : Screen("admin_advertiser_create", "Create", Icons.Filled.Add)
    class AdminAdvertiserEdit(id: Int) : Screen("admin_advertiser_edit/$id", "Edit", Icons.Filled.Edit) {
        companion object {
            const val route = "admin_advertiser_edit/{advertiserId}"
            fun createRoute(advertiserId: Int) = "admin_advertiser_edit/$advertiserId"
        }
    }
    object AdminConversions : Screen("admin_conversions", "Conv", Icons.Filled.MonetizationOn)
    object AdminFraudReport : Screen("admin_fraud", "Fraud", Icons.Filled.Shield)
    object AdminReports : Screen("admin_reports", "Reports", Icons.Filled.Assessment)
    object AdminAffiliateReport : Screen("admin_aff_report", "Aff Rpt", Icons.Filled.Group)
    object AdminAutoHide : Screen("admin_autohide", "Hide", Icons.Filled.VisibilityOff)
    object AdminInvoices : Screen("admin_invoices", "Invoices", Icons.Filled.Receipt)
    object AdminSettings : Screen("admin_settings", "Settings", Icons.Filled.Settings)

    // Manager Screens
    object ManagerDashboard : Screen("manager_dashboard", "Dashboard", Icons.Filled.Home)
    object ManagerOffers : Screen("manager_offers", "Offers", Icons.Filled.LocalOffer)
    object ManagerSmartlinks : Screen("manager_smartlinks", "Smartlinks", Icons.Filled.Link)
    object ManagerSmartlinkRequests : Screen("manager_smartlink_requests", "Requests", Icons.Filled.Assessment)
    object ManagerAffiliates : Screen("manager_affiliates", "Affiliates", Icons.Filled.Assessment)
    object ManagerConversions : Screen("manager_conversions", "Conv", Icons.Filled.MonetizationOn)
    object ManagerInvoices : Screen("manager_invoices", "Invoices", Icons.Filled.Receipt)
    object ManagerReports : Screen("manager_reports", "Reports", Icons.Filled.Assessment)
    object ManagerSettings : Screen("manager_settings", "Settings", Icons.Filled.Settings)
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MainScreen(
    role: String,
    onLogout: () -> Unit,
    onRoleChange: (String) -> Unit = {}
) {
    val navController = rememberNavController()

    val items = when (role) {
        "admin" -> listOf(Screen.AdminDashboard, Screen.AdminOffers, Screen.AdminInHouseOffers, Screen.AdminPrivateOffers, Screen.AdminSmartlinks, Screen.AdminOfferApprovals, Screen.AdminUsers, Screen.AdminAdvertisers, Screen.AdminConversions, Screen.AdminReports, Screen.AdminAffiliateReport, Screen.AdminFraudReport, Screen.AdminAutoHide, Screen.AdminInvoices, Screen.AdminSettings)
        "affiliate_manager" -> listOf(Screen.ManagerDashboard, Screen.ManagerOffers, Screen.ManagerSmartlinks, Screen.ManagerAffiliates, Screen.ManagerConversions, Screen.ManagerReports, Screen.ManagerInvoices, Screen.ManagerSettings)
        else -> listOf(Screen.Dashboard, Screen.Offers, Screen.Smartlinks, Screen.Reports, Screen.AffiliateSettings)
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
            composable(Screen.Dashboard.route) { 
                DashboardScreen(
                    onNavigateToInvoices = { navController.navigate(Screen.Invoices.route) },
                    onNavigateToFraudAlerts = { navController.navigate(Screen.FraudReport.route) },
                    onNavigateToChat = { navController.navigate(Screen.Chat.route) },
                    onNavigateToNews = { navController.navigate(Screen.News.route) }
                )
            }
            composable(Screen.AdminAdvertisers.route) {
                AdminAdvertisersScreen(
                    onNavigateToCreate = { navController.navigate(Screen.AdminAdvertiserCreate.route) },
                    onNavigateToEdit = { id -> navController.navigate(Screen.AdminAdvertiserEdit.createRoute(id)) },
                    onNavigateToView = { /* TODO: View screen */ },
                    onLoginToAdvertiser = { role ->
                        if (role == "advertiser") {
                            navController.navigate("advertiser_dashboard") {
                                popUpTo(0) { inclusive = true }
                            }
                        }
                    }
                )
            }
            composable(Screen.AdminAdvertiserCreate.route) {
                AdminAdvertiserFormScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminAdvertiserEdit.route) { backStackEntry ->
                val id = backStackEntry.arguments?.getString("advertiserId")?.toIntOrNull()
                if (id != null) {
                    AdminAdvertiserFormScreen(
                        onNavigateBack = { navController.popBackStack() }
                    )
                }
            }
            composable(Screen.Offers.route) { OfferScreen(onOfferClick = {}) }
            composable(Screen.Smartlinks.route) { com.example.affscash.ui.smartlinks.SmartlinkScreen() }
            composable(Screen.Reports.route) { 
                ReportScreen(
                    onNavigateToFraudReport = { navController.navigate(Screen.FraudReport.route) }
                ) 
            }
            composable(Screen.AffiliateSettings.route) { 
                com.example.affscash.ui.settings.SettingsScreen(
                    role = role, 
                    onLogout = onLogout,
                    onNavigateToInvoices = { navController.navigate(Screen.Invoices.route) },
                    onNavigateToRewards = { navController.navigate(Screen.Rewards.route) },
                    onNavigateToShop = { navController.navigate(Screen.Shop.route) },
                    onRoleChange = onRoleChange
                ) 
            }
            composable(Screen.Invoices.route) {
                com.example.affscash.ui.invoices.InvoiceScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.FraudReport.route) {
                com.example.affscash.ui.reports.FraudReportScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.Rewards.route) {
                com.example.affscash.ui.rewards.RewardsScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.Chat.route) {
                com.example.affscash.ui.chat.ChatScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.Shop.route) {
                com.example.affscash.ui.shop.ShopScreen()
            }
            composable(Screen.News.route) {
                com.example.affscash.ui.news.NewsScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.Notifications.route) {
                com.example.affscash.ui.notifications.NotificationsScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }

            // Admin Screens
            composable(Screen.AdminDashboard.route) { com.example.affscash.ui.admin.AdminDashboardScreen() }
            composable(Screen.AdminOfferApprovals.route) { com.example.affscash.ui.admin.AdminOfferApprovalsScreen() }
            composable(Screen.AdminInHouseOffers.route) {
                com.example.affscash.ui.admin.AdminInHouseOffersScreen(
                    onNavigateToCreateOffer = { navController.navigate(Screen.AdminInHouseOfferCreate.route) },
                    onNavigateToEditOffer = { id -> navController.navigate(Screen.AdminOfferEdit.createRoute(id)) }
                )
            }
            composable(Screen.AdminPrivateOffers.route) {
                com.example.affscash.ui.admin.AdminPrivateOffersScreen(
                    onNavigateToDetail = { id -> navController.navigate(Screen.AdminPrivateOfferDetail.createRoute(id)) }
                )
            }
            composable(
                route = Screen.AdminPrivateOfferDetail.route
            ) { backStackEntry ->
                val id = backStackEntry.arguments?.getString("offerId")?.toIntOrNull()
                if (id != null) {
                    com.example.affscash.ui.admin.AdminPrivateOfferDetailScreen(
                        offerId = id,
                        onNavigateBack = { navController.popBackStack() }
                    )
                }
            }
            
            // Admin Smartlinks
            composable(Screen.AdminSmartlinks.route) {
                val viewModel: com.example.affscash.ui.screens.admin.smartlinks.AdminSmartlinksViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                com.example.affscash.ui.screens.admin.smartlinks.AdminSmartlinksScreen(
                    viewModel = viewModel,
                    navController = navController
                )
            }
            
            composable(Screen.AdminSmartlinkRequests.route) {
                val viewModel: com.example.affscash.ui.screens.admin.smartlinks.AdminSmartlinkRequestsViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                com.example.affscash.ui.screens.admin.smartlinks.AdminSmartlinkRequestsScreen(
                    viewModel = viewModel,
                    navController = navController
                )
            }
            
            composable(Screen.AdminSmartlinkCreate.route) {
                val viewModel: com.example.affscash.ui.screens.admin.smartlinks.AdminSmartlinkFormViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                com.example.affscash.ui.screens.admin.smartlinks.AdminSmartlinkFormScreen(
                    viewModel = viewModel,
                    navController = navController,
                    smartlinkId = null
                )
            }
            
            composable(Screen.AdminSmartlinkEdit.route) { backStackEntry ->
                val id = backStackEntry.arguments?.getString("smartlinkId")?.toIntOrNull()
                val viewModel: com.example.affscash.ui.screens.admin.smartlinks.AdminSmartlinkFormViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                com.example.affscash.ui.screens.admin.smartlinks.AdminSmartlinkFormScreen(
                    viewModel = viewModel,
                    navController = navController,
                    smartlinkId = id
                )
            }

            composable(Screen.AdminOffers.route) { 
                com.example.affscash.ui.admin.AdminOffersScreen(
                    onNavigateToCreateOffer = { navController.navigate(Screen.AdminOfferCreate.route) },
                    onNavigateToEditOffer = { id -> navController.navigate(Screen.AdminOfferEdit.createRoute(id)) }
                ) 
            }
            composable(Screen.AdminOfferCreate.route) {
                com.example.affscash.ui.admin.AdminOfferFormScreen(
                    offerId = null,
                    isInHouse = false,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminInHouseOfferCreate.route) {
                com.example.affscash.ui.admin.AdminOfferFormScreen(
                    offerId = null,
                    isInHouse = true,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(
                route = Screen.AdminOfferEdit.route
            ) { backStackEntry ->
                val id = backStackEntry.arguments?.getString("offerId")?.toIntOrNull()
                if (id != null) {
                    com.example.affscash.ui.admin.AdminOfferFormScreen(
                        offerId = id,
                        isInHouse = false,
                        onNavigateBack = { navController.popBackStack() }
                    )
                }
            }
            composable(Screen.AdminUsers.route) { com.example.affscash.ui.admin.AdminUsersScreen() }
            composable(Screen.AdminConversions.route) { com.example.affscash.ui.admin.AdminConversionsScreen() }
            composable(Screen.AdminReports.route) {
                val viewModel: com.example.affscash.ui.screens.admin.reports.AdminReportsViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                com.example.affscash.ui.screens.admin.reports.AdminReportsScreen(
                    viewModel = viewModel,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminAffiliateReport.route) {
                val viewModel: com.example.affscash.ui.screens.admin.reports.AdminAffiliateReportViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                com.example.affscash.ui.screens.admin.reports.AdminAffiliateReportScreen(
                    viewModel = viewModel,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminAutoHide.route) {
                val viewModel: com.example.affscash.ui.screens.admin.autohide.AdminAutoHideViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                com.example.affscash.ui.screens.admin.autohide.AdminAutoHideScreen(
                    viewModel = viewModel,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminFraudReport.route) {
                com.example.affscash.ui.screens.admin.fraud.AdminFraudReportScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminInvoices.route) {
                val repo = com.example.affscash.data.repository.AdminInvoiceRepository(com.example.affscash.data.network.RetrofitClient.apiService)
                val factory = object : androidx.lifecycle.ViewModelProvider.Factory {
                    override fun <T : androidx.lifecycle.ViewModel> create(modelClass: Class<T>): T {
                        return com.example.affscash.ui.screens.admin.invoices.AdminInvoicesViewModel(repo) as T
                    }
                }
                val viewModel: com.example.affscash.ui.screens.admin.invoices.AdminInvoicesViewModel = androidx.lifecycle.viewmodel.compose.viewModel(factory = factory)
                com.example.affscash.ui.screens.admin.invoices.AdminInvoicesScreen(
                    viewModel = viewModel,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminSettings.route) { com.example.affscash.ui.settings.SettingsScreen(role, onLogout) }

            // Manager Screens
            composable(Screen.ManagerDashboard.route) { 
                val context = androidx.compose.ui.platform.LocalContext.current
                com.example.affscash.ui.manager.ManagerDashboardScreen(
                    onNavigateToInvoices = { navController.navigate(Screen.ManagerInvoices.route) },
                    onNavigateToFraudAlerts = { navController.navigate("manager_fraud_reports") },
                    onNavigateToChat = { android.widget.Toast.makeText(context, "Chat coming soon", android.widget.Toast.LENGTH_SHORT).show() }
                ) 
            }
            composable(Screen.ManagerOffers.route) { 
                com.example.affscash.ui.manager.ManagerOffersScreen(
                    onNavigateToApprovals = { navController.navigate("manager_offer_approvals") }
                ) 
            }
            composable("manager_offer_approvals") {
                com.example.affscash.ui.manager.ManagerOfferApprovalsScreen()
            }
            composable(Screen.ManagerSmartlinks.route) { 
                com.example.affscash.ui.manager.ManagerSmartlinksScreen(
                    onNavigateToRequests = { navController.navigate(Screen.ManagerSmartlinkRequests.route) }
                ) 
            }
            composable(Screen.ManagerSmartlinkRequests.route) { 
                com.example.affscash.ui.manager.ManagerSmartlinkRequestsScreen(
                    onNavigateBack = { navController.popBackStack() }
                ) 
            }
            composable(Screen.ManagerAffiliates.route) { 
                com.example.affscash.ui.manager.ManagerAffiliatesScreen(
                    onLoginToAffiliate = onRoleChange,
                    onNavigateToCreate = { navController.navigate("manager_create_affiliate") },
                    onNavigateToEdit = { affId -> navController.navigate("manager_edit_affiliate/$affId") },
                    onNavigateToView = { affId -> navController.navigate("manager_view_affiliate/$affId") }
                ) 
            }
            composable("manager_create_affiliate") {
                val viewModel: com.example.affscash.ui.manager.ManagerAffiliatesViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                com.example.affscash.ui.manager.ManagerCreateAffiliateScreen(
                    onNavigateBack = { navController.popBackStack() },
                    viewModel = viewModel
                )
            }
            composable("manager_edit_affiliate/{affId}") { backStackEntry ->
                val affId = backStackEntry.arguments?.getString("affId")?.toIntOrNull() ?: 0
                val viewModel: com.example.affscash.ui.manager.ManagerAffiliatesViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                val uiState by viewModel.uiState.collectAsState()
                val affiliate = uiState.affiliates.find { it.affId == affId }
                if (affiliate != null) {
                    com.example.affscash.ui.manager.ManagerEditAffiliateScreen(
                        affiliate = affiliate,
                        onNavigateBack = { navController.popBackStack() },
                        viewModel = viewModel
                    )
                }
            }
            composable("manager_view_affiliate/{affId}") { backStackEntry ->
                val affId = backStackEntry.arguments?.getString("affId")?.toIntOrNull() ?: 0
                val viewModel: com.example.affscash.ui.manager.ManagerAffiliatesViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                com.example.affscash.ui.manager.ManagerAffiliateDetailsScreen(
                    affId = affId,
                    onNavigateBack = { navController.popBackStack() },
                    viewModel = viewModel
                )
            }
            composable(Screen.ManagerConversions.route) { 
                com.example.affscash.ui.manager.ManagerConversionsScreen(
                    onNavigateToDuplicates = { navController.navigate("manager_duplicate_conversions") },
                    onNavigateToFraud = { navController.navigate("manager_fraud_reports") }
                ) 
            }
            composable("manager_duplicate_conversions") { 
                com.example.affscash.ui.manager.ManagerDuplicateConversionsScreen(
                    onNavigateBack = { navController.popBackStack() }
                ) 
            }
            composable("manager_fraud_reports") { 
                com.example.affscash.ui.manager.ManagerFraudReportsScreen(
                    onNavigateBack = { navController.popBackStack() }
                ) 
            }
            composable(Screen.ManagerReports.route) { com.example.affscash.ui.manager.ManagerReportsScreen() }
            composable(Screen.ManagerInvoices.route) { com.example.affscash.ui.manager.ManagerInvoicesScreen() }
            composable(Screen.ManagerSettings.route) { com.example.affscash.ui.manager.ManagerProfileScreen(onLogout = onLogout) }
        }
    }
}
