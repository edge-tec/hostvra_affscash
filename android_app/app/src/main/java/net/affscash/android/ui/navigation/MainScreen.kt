package net.affscash.android.ui.navigation

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.ui.Alignment
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.navigation.NavDestination.Companion.hierarchy
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import net.affscash.android.ui.dashboard.DashboardScreen
import net.affscash.android.ui.offers.OfferScreen
import net.affscash.android.ui.reports.ReportScreen
import net.affscash.android.ui.admin.AdminAdvertisersScreen
import net.affscash.android.ui.admin.AdminAdvertiserFormScreen
import net.affscash.android.ui.admin.AdminUsersScreen
import net.affscash.android.ui.admin.AdminAffiliateDetailsScreen
import net.affscash.android.ui.admin.AdminEditAffiliateScreen
import net.affscash.android.ui.admin.AdminAdvertiserDetailsScreen
import androidx.navigation.navArgument
import androidx.navigation.NavType

sealed class Screen(val route: String, val title: String, val icon: androidx.compose.ui.graphics.vector.ImageVector) {
    // Affiliate Screens
    object Dashboard : Screen("dashboard", "Dashboard", Icons.Filled.Home)
    object Offers : Screen("offers", "Offers", Icons.Filled.LocalOffer)
    object Smartlinks : Screen("smartlinks", "Smartlinks", Icons.Filled.Link)
    object Reports : Screen("reports", "Reports", Icons.Filled.Assessment)
    object AffiliateReports : Screen("affiliate_reports", "Reports", Icons.Filled.BarChart)
    object AffiliateDuplicateConversions : Screen("affiliate_duplicate_conversions", "Duplicate Conversions", Icons.Filled.Warning)
    object AffiliateReferral : Screen("affiliate_referral", "Referral Program", Icons.Filled.PersonAdd)
    object AffiliateInvoices : Screen("affiliate_invoices", "Invoices", Icons.Filled.ShoppingCart)
    object AdminInvoices : Screen("admin_invoices", "Invoices", Icons.Default.Receipt)
    object AdminSettings : Screen("admin_settings", "Settings", Icons.Default.Settings)
    object AdminReferral : Screen("admin_referral", "Referral System", Icons.Default.Share)
    object FraudReport : Screen("fraud_report", "Fraud Report", Icons.Filled.Assessment)
    object Rewards : Screen("rewards", "Milestones", Icons.Filled.MonetizationOn)
    object Shop : Screen("shop", "Rewards Shop", Icons.Filled.LocalOffer)
    object News : Screen("news", "News", Icons.Filled.Article)
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
    object AdminAdvertiserCreate : Screen("admin_create", "Create", Icons.Filled.Add)
    class AdminAdvertiserEdit(id: Int) : Screen("admin_edit/$id", "Edit", Icons.Filled.Edit) {
        companion object {
            const val route = "admin_edit/{advertiserId}"
            fun createRoute(advertiserId: Int) = "admin_edit/$advertiserId"
        }
    }
    class AdminAdvertiserDetails(id: Int) : Screen("admin_details/$id", "Advertiser Details", Icons.Filled.Info) {
        companion object {
            const val route = "admin_details/{advId}"
            fun createRoute(advId: Int) = "admin_details/$advId"
        }
    }
    object AdminConversions : Screen("admin_conversions", "Conv", Icons.Filled.MonetizationOn)
    object AdminFraudReport : Screen("admin_fraud", "Fraud", Icons.Filled.Shield)
    object AdminReports : Screen("admin_reports", "Reports", Icons.Filled.Assessment)
    object AdminAccountDeleteRequests : Screen("admin_account_delete_requests", "Delete Requests", Icons.Filled.DeleteOutline)
    object AdminPoints : Screen("admin_points", "Points Module", Icons.Filled.Stars)
    object AdminAffiliateReport : Screen("admin_aff_report", "Aff Rpt", Icons.Filled.Group)
    object AdminAutoHide : Screen("admin_autohide", "Hide", Icons.Filled.VisibilityOff)
    object AdminCreateInvoice : Screen("admin_create_invoice", "Create Invoice", Icons.Filled.Add)
    object AdminAffiliateManagers : Screen("admin_managers", "Managers", Icons.Filled.SupervisorAccount)
    object AdminSupport : Screen("admin_support", "Live Support", Icons.Filled.SupportAgent)
    class AdminChat(id: Int, affId: Int, name: String) : Screen("admin_chat/$id/$affId/$name", "Chat", Icons.Filled.Chat) {
        companion object {
            const val route = "admin_chat/{convId}/{affId}/{name}"
            fun createRoute(convId: Int, affId: Int, name: String) = "admin_chat/$convId/$affId/$name"
        }
    }
    object AdminPlatformSettings : Screen("admin_platform_settings", "Platform Settings", Icons.Filled.Settings)
    object AdminShop : Screen("admin_shop", "Affiliate Shop", Icons.Filled.ShoppingCart)
    class AdminShopProductForm(id: Int? = null) : Screen(
        if (id == null) "admin_shop_product_form" else "admin_shop_product_form?id=$id",
        if (id == null) "Create Product" else "Edit Product",
        Icons.Filled.Edit
    ) {
        companion object {
            const val routePattern = "admin_shop_product_form?id={id}"
            fun createRoute(id: Int?) = if (id == null) "admin_shop_product_form" else "admin_shop_product_form?id=$id"
        }
    }
    object AdminShopOrders : Screen("admin_shop_orders", "Shop Orders", Icons.Filled.List)

    // Manager Screens
    object ManagerDashboard : Screen("manager_dashboard", "Dashboard", Icons.Filled.Home)
    object ManagerOffers : Screen("manager_offers", "Offers", Icons.Filled.LocalOffer)
    object ManagerSmartlinks : Screen("manager_smartlinks", "Smartlinks", Icons.Filled.Link)
    object ManagerSmartlinkRequests : Screen("manager_smartlink_requests", "Requests", Icons.Filled.Assessment)
    object ManagerAffiliates : Screen("manager_affiliates", "Affiliates", Icons.Filled.People)
    object ManagerConversions : Screen("manager_conversions", "Conversions", Icons.Filled.TrendingUp)
    object ManagerReports : Screen("manager_reports", "Reports", Icons.Filled.BarChart)
    object ManagerReferral : Screen("manager_referral", "Referral Link", Icons.Filled.PersonAdd)
    object ManagerInvoices : Screen("manager_invoices", "Invoices", Icons.Filled.Receipt)
    object ManagerSupport : Screen("manager_support", "Support", Icons.Filled.SupportAgent)
    object ManagerChat : Screen("manager_chat", "Chat", Icons.Filled.Chat)
    object ManagerSettings : Screen("manager_settings", "Settings", Icons.Filled.Settings)
    
    // Affiliate Screens
    object Invoices : Screen("invoices", "Invoices", Icons.Filled.PictureAsPdf)
    object AffiliateSettings : Screen("affiliate_settings", "Settings", Icons.Filled.Settings)
    object AffiliateInHouseOffers : Screen("affiliate_inhouse_offers", "In-House Offers", Icons.Filled.HomeRepairService)
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MainScreen(
    role: String,
    onLogout: () -> Unit,
    onRoleChange: (String) -> Unit = {},
    viewModel: MainViewModel = androidx.hilt.navigation.compose.hiltViewModel()
) {
    val navController = rememberNavController()
    val isImpersonating by viewModel.isImpersonating.collectAsState()
    val context = androidx.compose.ui.platform.LocalContext.current

    LaunchedEffect(role) {
        viewModel.refreshImpersonatingState()
    }

    val allItems = when (role) {
        "admin" -> listOf(Screen.AdminDashboard, Screen.AdminOffers, Screen.AdminInHouseOffers, Screen.AdminPrivateOffers, Screen.AdminSmartlinks, Screen.AdminOfferApprovals, Screen.AdminUsers, Screen.AdminAdvertisers, Screen.AdminShop, Screen.AdminAffiliateManagers, Screen.AdminSupport, Screen.AdminConversions, Screen.AdminReports, Screen.AdminAffiliateReport, Screen.AdminFraudReport, Screen.AdminAccountDeleteRequests, Screen.AdminPoints, Screen.AdminAutoHide, Screen.AdminInvoices, Screen.AdminPlatformSettings, Screen.AdminSettings, Screen.AdminReferral)
        "affiliate_manager" -> listOf(Screen.ManagerDashboard, Screen.ManagerOffers, Screen.ManagerSupport, Screen.ManagerSmartlinks, Screen.ManagerAffiliates, Screen.ManagerConversions, Screen.ManagerReports, Screen.ManagerReferral, Screen.ManagerInvoices, Screen.ManagerSettings)
        else -> listOf(Screen.Dashboard, Screen.Offers, Screen.AffiliateInHouseOffers, Screen.Smartlinks, Screen.Reports, Screen.AffiliateDuplicateConversions, Screen.AffiliateReferral, Screen.AffiliateSettings)
    }
    
    val startDest = allItems.first().route

    val mainItems = allItems.take(4)
    val moreItems = allItems.drop(4)

    var showMoreSheet by remember { mutableStateOf(false) }
    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)

    if (showMoreSheet) {
        ModalBottomSheet(
            onDismissRequest = { showMoreSheet = false },
            sheetState = sheetState,
            containerColor = MaterialTheme.colorScheme.surface
        ) {
            LazyVerticalGrid(
                columns = GridCells.Fixed(3),
                contentPadding = PaddingValues(16.dp),
                horizontalArrangement = Arrangement.spacedBy(16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp),
                modifier = Modifier.padding(bottom = 32.dp)
            ) {
                items(moreItems) { screen ->
                    Card(
                        onClick = {
                            showMoreSheet = false
                            navController.navigate(screen.route) {
                                popUpTo(navController.graph.findStartDestination().id) {
                                    saveState = true
                                }
                                launchSingleTop = true
                                restoreState = true
                            }
                        },
                        modifier = Modifier.fillMaxWidth().aspectRatio(1f),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
                    ) {
                        Column(
                            modifier = Modifier.fillMaxSize().padding(8.dp),
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.Center
                        ) {
                            Icon(screen.icon, contentDescription = null, modifier = Modifier.size(28.dp), tint = MaterialTheme.colorScheme.primary)
                            Spacer(modifier = Modifier.height(8.dp))
                            Text(screen.title, style = MaterialTheme.typography.labelMedium, textAlign = TextAlign.Center, maxLines = 2)
                        }
                    }
                }
                
                // Role Back (Stop Impersonating) Button
                if (isImpersonating) {
                    item {
                        Card(
                            onClick = {
                                viewModel.stopImpersonating(
                                    onSuccess = { newRole -> 
                                        showMoreSheet = false
                                        if (newRole != null) onRoleChange(newRole) else onLogout() 
                                    },
                                    onError = { android.widget.Toast.makeText(context, it, android.widget.Toast.LENGTH_SHORT).show() }
                                )
                            },
                            modifier = Modifier.fillMaxWidth().aspectRatio(1f),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.secondaryContainer)
                        ) {
                            Column(
                                modifier = Modifier.fillMaxSize().padding(8.dp),
                                horizontalAlignment = Alignment.CenterHorizontally,
                                verticalArrangement = Arrangement.Center
                            ) {
                                Icon(Icons.Filled.ExitToApp, contentDescription = "Return", modifier = Modifier.size(28.dp), tint = MaterialTheme.colorScheme.onSecondaryContainer)
                                Spacer(modifier = Modifier.height(8.dp))
                                Text("Return to Admin", style = MaterialTheme.typography.labelMedium, textAlign = TextAlign.Center, maxLines = 2, color = MaterialTheme.colorScheme.onSecondaryContainer)
                            }
                        }
                    }
                }
                
                // Explicit Logout Button
                item {
                    Card(
                        onClick = {
                            showMoreSheet = false
                            onLogout()
                        },
                        modifier = Modifier.fillMaxWidth().aspectRatio(1f),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer)
                    ) {
                        Column(
                            modifier = Modifier.fillMaxSize().padding(8.dp),
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.Center
                        ) {
                            Icon(Icons.Filled.ExitToApp, contentDescription = "Logout", modifier = Modifier.size(28.dp), tint = MaterialTheme.colorScheme.error)
                            Spacer(modifier = Modifier.height(8.dp))
                            Text("Logout", style = MaterialTheme.typography.labelMedium, textAlign = TextAlign.Center, maxLines = 2, color = MaterialTheme.colorScheme.onErrorContainer)
                        }
                    }
                }
            }
        }
    }

    Scaffold(
        contentWindowInsets = WindowInsets(0.dp),
        topBar = {
            if (isImpersonating) {
                Surface(
                    color = androidx.compose.ui.graphics.Color(0xFFF59E0B),
                    contentColor = androidx.compose.ui.graphics.Color(0xFF1F2937),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Filled.Visibility, contentDescription = "Impersonating", modifier = Modifier.size(20.dp))
                            Spacer(Modifier.width(8.dp))
                            Text("Viewing as Affiliate", style = MaterialTheme.typography.labelMedium, fontWeight = androidx.compose.ui.text.font.FontWeight.Bold)
                        }
                        Button(
                            onClick = {
                                viewModel.stopImpersonating(
                                    onSuccess = { newRole -> 
                                        if (newRole != null) onRoleChange(newRole) else onLogout() 
                                    },
                                    onError = { android.widget.Toast.makeText(context, it, android.widget.Toast.LENGTH_SHORT).show() }
                                )
                            },
                            colors = ButtonDefaults.buttonColors(containerColor = androidx.compose.ui.graphics.Color(0xFF1F2937), contentColor = androidx.compose.ui.graphics.Color.White),
                            contentPadding = PaddingValues(horizontal = 12.dp, vertical = 4.dp),
                            modifier = Modifier.height(32.dp)
                        ) {
                            Text("Return", fontSize = androidx.compose.ui.unit.TextUnit(12f, androidx.compose.ui.unit.TextUnitType.Sp), fontWeight = androidx.compose.ui.text.font.FontWeight.Bold)
                        }
                    }
                }
            }
        },
        bottomBar = {
            NavigationBar {
                val navBackStackEntry by navController.currentBackStackEntryAsState()
                val currentDestination = navBackStackEntry?.destination
                mainItems.forEach { screen ->
                    NavigationBarItem(
                        icon = { Icon(screen.icon, contentDescription = null) },
                        label = { Text(screen.title, maxLines = 1, overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis) },
                        selected = currentDestination?.hierarchy?.any { it.route == screen.route } == true,
                        onClick = {
                            navController.navigate(screen.route) {
                                popUpTo(navController.graph.findStartDestination().id) {
                                    saveState = true
                                }
                                launchSingleTop = true
                                restoreState = true
                            }
                        }
                    )
                }
                if (moreItems.isNotEmpty()) {
                    NavigationBarItem(
                        icon = { Icon(Icons.Filled.Menu, contentDescription = "Menu") },
                        label = { Text("Menu") },
                        selected = currentDestination?.route?.let { route -> moreItems.any { it.route == route } } == true,
                        onClick = { showMoreSheet = true }
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
                    onNavigateToNews = { navController.navigate(Screen.News.route) },
                    onNavigateToNotifications = { navController.navigate(Screen.Notifications.route) }
                )
            }
            composable(Screen.AdminAdvertisers.route) {
                AdminAdvertisersScreen(
                    onNavigateToCreate = { navController.navigate(Screen.AdminAdvertiserCreate.route) },
                    onNavigateToEdit = { id -> navController.navigate(Screen.AdminAdvertiserEdit.createRoute(id)) },
                    onNavigateToView = { id -> navController.navigate(Screen.AdminAdvertiserDetails.createRoute(id)) },
                    onLoginToAdvertiser = onRoleChange
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
            composable(
                route = Screen.AdminAdvertiserDetails.route,
                arguments = listOf(navArgument("advId") { type = NavType.IntType })
            ) { backStackEntry ->
                val advId = backStackEntry.arguments?.getInt("advId") ?: 0
                AdminAdvertiserDetailsScreen(
                    advId = advId,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.Offers.route) { OfferScreen(onOfferClick = {}) }
            composable(Screen.AffiliateInHouseOffers.route) { net.affscash.android.ui.offers.AffiliateInHouseOffersScreen(onOfferClick = {}) }
            composable(Screen.Smartlinks.route) { net.affscash.android.ui.smartlinks.SmartlinkScreen() }
            composable(Screen.Reports.route) { 
                ReportScreen(
                    onNavigateToFraudReport = { navController.navigate(Screen.FraudReport.route) }
                ) 
            }
            composable(Screen.AffiliateSettings.route) { 
                net.affscash.android.ui.settings.SettingsScreen(
                    role = role, 
                    onLogout = onLogout,
                    onNavigateToInvoices = { navController.navigate(Screen.Invoices.route) },
                    onNavigateToRewards = { navController.navigate(Screen.Rewards.route) },
                    onNavigateToShop = { navController.navigate(Screen.Shop.route) },
                    onRoleChange = onRoleChange
                ) 
            }
            composable(Screen.Invoices.route) {
                net.affscash.android.ui.invoices.InvoiceScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AffiliateDuplicateConversions.route) {
                net.affscash.android.ui.affiliate.duplicate_conversions.DuplicateConversionsScreen()
            }
            composable(Screen.AffiliateReferral.route) {
                net.affscash.android.ui.affiliate.referral.AffiliateReferralScreen()
            }
            composable(Screen.FraudReport.route) {
                net.affscash.android.ui.reports.FraudReportScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.Rewards.route) {
                net.affscash.android.ui.rewards.RewardsScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.Chat.route) {
                net.affscash.android.ui.chat.ChatScreen(
                    role = role,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.Shop.route) {
                net.affscash.android.ui.shop.ShopScreen()
            }
            composable(Screen.News.route) {
                net.affscash.android.ui.news.NewsScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.Notifications.route) {
                net.affscash.android.ui.notifications.NotificationsScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }

            // Admin Screens
            composable(Screen.AdminDashboard.route) { net.affscash.android.ui.admin.AdminDashboardScreen() }
            composable(Screen.AdminOfferApprovals.route) { net.affscash.android.ui.admin.AdminOfferApprovalsScreen() }
            composable(Screen.AdminInHouseOffers.route) {
                net.affscash.android.ui.admin.AdminInHouseOffersScreen(
                    onNavigateToCreateOffer = { navController.navigate(Screen.AdminInHouseOfferCreate.route) },
                    onNavigateToEditOffer = { id -> navController.navigate(Screen.AdminOfferEdit.createRoute(id)) }
                )
            }
            composable(Screen.AdminPrivateOffers.route) {
                net.affscash.android.ui.admin.AdminPrivateOffersScreen(
                    onNavigateToDetail = { id -> navController.navigate(Screen.AdminPrivateOfferDetail.createRoute(id)) }
                )
            }
            composable(
                route = Screen.AdminPrivateOfferDetail.route
            ) { backStackEntry ->
                val id = backStackEntry.arguments?.getString("offerId")?.toIntOrNull()
                if (id != null) {
                    net.affscash.android.ui.admin.AdminPrivateOfferDetailScreen(
                        offerId = id,
                        onNavigateBack = { navController.popBackStack() }
                    )
                }
            }
            
            composable(Screen.AdminSmartlinks.route) {
                val viewModel: net.affscash.android.ui.screens.admin.smartlinks.AdminSmartlinksViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                net.affscash.android.ui.screens.admin.smartlinks.AdminSmartlinksScreen(
                    viewModel = viewModel,
                    navController = navController
                )
            }
            
            composable(Screen.AdminSmartlinkRequests.route) {
                val viewModel: net.affscash.android.ui.screens.admin.smartlinks.AdminSmartlinkRequestsViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                net.affscash.android.ui.screens.admin.smartlinks.AdminSmartlinkRequestsScreen(
                    viewModel = viewModel,
                    navController = navController
                )
            }
            
            composable(Screen.AdminSmartlinkCreate.route) {
                val viewModel: net.affscash.android.ui.screens.admin.smartlinks.AdminSmartlinkFormViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                net.affscash.android.ui.screens.admin.smartlinks.AdminSmartlinkFormScreen(
                    viewModel = viewModel,
                    navController = navController,
                    smartlinkId = null
                )
            }
            
            composable(Screen.AdminSmartlinkEdit.route) { backStackEntry ->
                val id = backStackEntry.arguments?.getString("smartlinkId")?.toIntOrNull()
                val viewModel: net.affscash.android.ui.screens.admin.smartlinks.AdminSmartlinkFormViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                net.affscash.android.ui.screens.admin.smartlinks.AdminSmartlinkFormScreen(
                    viewModel = viewModel,
                    navController = navController,
                    smartlinkId = id
                )
            }

            composable(Screen.AdminOffers.route) { 
                net.affscash.android.ui.admin.AdminOffersScreen(
                    onNavigateToCreateOffer = { navController.navigate(Screen.AdminOfferCreate.route) },
                    onNavigateToEditOffer = { id -> navController.navigate(Screen.AdminOfferEdit.createRoute(id)) }
                ) 
            }
            
            composable(Screen.AdminSupport.route) {
                net.affscash.android.ui.screens.admin.support.AdminSupportScreen(
                    onNavigateBack = { navController.popBackStack() },
                    onNavigateToChat = { convId, affId, name ->
                        navController.navigate(Screen.AdminChat.createRoute(convId, affId, java.net.URLEncoder.encode(name, "UTF-8")))
                    }
                )
            }
            
            composable(Screen.AdminChat.route) { backStackEntry ->
                val convId = backStackEntry.arguments?.getString("convId")?.toIntOrNull() ?: 0
                val affId = backStackEntry.arguments?.getString("affId")?.toIntOrNull() ?: 0
                val name = backStackEntry.arguments?.getString("name")?.let { java.net.URLDecoder.decode(it, "UTF-8") } ?: "Unknown"
                // For Chat, we can pass the ViewModel if needed, but since it's scoped to the activity/navGraph,
                // we can just retrieve the same instance using hiltViewModel() inside the ChatScreen, OR
                // since the ViewModel is already shared via NavGraph/Activity, we can just fetch it here.
                val parentEntry = remember(backStackEntry) {
                    navController.getBackStackEntry(Screen.AdminSupport.route)
                }
                val viewModel: net.affscash.android.ui.screens.admin.support.AdminSupportViewModel = androidx.hilt.navigation.compose.hiltViewModel(parentEntry)
                
                net.affscash.android.ui.screens.admin.support.AdminSupportChatScreen(
                    onNavigateBack = { navController.popBackStack() },
                    viewModel = viewModel
                )
            }
            composable(Screen.AdminOfferCreate.route) {
                net.affscash.android.ui.admin.AdminOfferFormScreen(
                    offerId = null,
                    isInHouse = false,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminInHouseOfferCreate.route) {
                net.affscash.android.ui.admin.AdminOfferFormScreen(
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
                    net.affscash.android.ui.admin.AdminOfferFormScreen(
                        offerId = id,
                        isInHouse = false,
                        onNavigateBack = { navController.popBackStack() }
                    )
                }
            }
            composable("admin_users") { AdminUsersScreen(
                onNavigateToEdit = { affId -> navController.navigate("admin_edit_affiliate/$affId") },
                onNavigateToView = { affId -> navController.navigate("admin_affiliate_details/$affId") }
            ) }
            
            composable(
                route = "admin_affiliate_details/{affId}",
                arguments = listOf(navArgument("affId") { type = NavType.IntType })
            ) { backStackEntry ->
                val affId = backStackEntry.arguments?.getInt("affId") ?: return@composable
                AdminAffiliateDetailsScreen(
                    affId = affId,
                    onNavigateBack = { navController.popBackStack() }
                )
            }

            composable(
                route = "admin_edit_affiliate/{affId}",
                arguments = listOf(navArgument("affId") { type = NavType.IntType })
            ) { backStackEntry ->
                val affId = backStackEntry.arguments?.getInt("affId") ?: return@composable
                AdminEditAffiliateScreen(
                    affId = affId,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminConversions.route) { net.affscash.android.ui.admin.AdminConversionsScreen() }
            composable(Screen.AdminReports.route) {
                val viewModel: net.affscash.android.ui.screens.admin.reports.AdminReportsViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                net.affscash.android.ui.screens.admin.reports.AdminReportsScreen(
                    viewModel = viewModel,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminAffiliateReport.route) {
                val viewModel: net.affscash.android.ui.screens.admin.reports.AdminAffiliateReportViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                net.affscash.android.ui.screens.admin.reports.AdminAffiliateReportScreen(
                    viewModel = viewModel,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminAutoHide.route) {
                val viewModel: net.affscash.android.ui.screens.admin.autohide.AdminAutoHideViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                net.affscash.android.ui.screens.admin.autohide.AdminAutoHideScreen(
                    viewModel = viewModel,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminFraudReport.route) {
                net.affscash.android.ui.screens.admin.fraud.AdminFraudReportScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminAccountDeleteRequests.route) {
                val viewModel: net.affscash.android.ui.screens.admin.account_delete.AdminAccountDeleteRequestsViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                net.affscash.android.ui.screens.admin.account_delete.AdminAccountDeleteRequestsScreen(
                    viewModel = viewModel,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminPoints.route) {
                val viewModel: net.affscash.android.ui.screens.admin.points.AdminPointsViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                net.affscash.android.ui.screens.admin.points.AdminPointsScreen(
                    viewModel = viewModel,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminInvoices.route) {
                val viewModel: net.affscash.android.ui.screens.admin.invoices.AdminInvoicesViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                net.affscash.android.ui.screens.admin.invoices.AdminInvoicesScreen(
                    viewModel = viewModel,
                    onNavigateBack = { navController.popBackStack() },
                    onCreateInvoice = { navController.navigate(Screen.AdminCreateInvoice.route) }
                )
            }
            composable(Screen.AdminCreateInvoice.route) {
                net.affscash.android.ui.screens.admin.invoices.AdminCreateInvoiceScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminAffiliateManagers.route) {
                val viewModel: net.affscash.android.ui.screens.admin.managers.AdminAffiliateManagersViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                net.affscash.android.ui.screens.admin.managers.AdminAffiliateManagersScreen(
                    viewModel = viewModel,
                    onNavigateBack = { navController.popBackStack() },
                    onLoginSuccess = onRoleChange
                )
            }
            composable(Screen.AdminSettings.route) { net.affscash.android.ui.settings.SettingsScreen(role, onLogout) }
            composable(Screen.AdminPlatformSettings.route) { 
                net.affscash.android.ui.screens.admin.settings.AdminPlatformSettingsScreen(navController = navController) 
            }
            composable(Screen.AdminReferral.route) {
                net.affscash.android.ui.admin.referral.AdminReferralScreen(
                    navController = navController
                )
            }

            // Manager Screens
            composable(Screen.ManagerDashboard.route) { 
                net.affscash.android.ui.manager.ManagerDashboardScreen(
                    onNavigateToInvoices = { navController.navigate(Screen.ManagerInvoices.route) },
                    onNavigateToFraudAlerts = { navController.navigate("manager_fraud_reports") },
                    onNavigateToChat = { navController.navigate(Screen.Chat.route) },
                    onNavigateToNotifications = { navController.navigate(Screen.Notifications.route) }
                ) 
            }
            composable(Screen.ManagerOffers.route) { 
                net.affscash.android.ui.manager.ManagerOffersScreen(
                    onNavigateToApprovals = { navController.navigate("manager_offer_approvals") }
                ) 
            }
            composable("manager_offer_approvals") {
                net.affscash.android.ui.manager.ManagerOfferApprovalsScreen()
            }
            composable(Screen.ManagerSmartlinks.route) { 
                net.affscash.android.ui.manager.ManagerSmartlinksScreen(
                    onNavigateToRequests = { navController.navigate(Screen.ManagerSmartlinkRequests.route) }
                ) 
            }
            composable(Screen.ManagerSmartlinkRequests.route) { 
                net.affscash.android.ui.manager.ManagerSmartlinkRequestsScreen(
                    onNavigateBack = { navController.popBackStack() }
                ) 
            }
            composable(Screen.ManagerAffiliates.route) { 
                net.affscash.android.ui.manager.ManagerAffiliatesScreen(
                    onLoginToAffiliate = onRoleChange,
                    onNavigateToCreate = { navController.navigate("manager_create_affiliate") },
                    onNavigateToEdit = { affId -> navController.navigate("manager_edit_affiliate/$affId") },
                    onNavigateToView = { affId -> navController.navigate("manager_view_affiliate/$affId") }
                ) 
            }
            composable("manager_create_affiliate") {
                val viewModel: net.affscash.android.ui.manager.ManagerAffiliatesViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                net.affscash.android.ui.manager.ManagerCreateAffiliateScreen(
                    onNavigateBack = { navController.popBackStack() },
                    viewModel = viewModel
                )
            }
            composable("manager_edit_affiliate/{affId}") { backStackEntry ->
                val affId = backStackEntry.arguments?.getString("affId")?.toIntOrNull() ?: 0
                val viewModel: net.affscash.android.ui.manager.ManagerAffiliatesViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                val uiState by viewModel.uiState.collectAsState()
                val affiliate = uiState.affiliates.find { it.affId == affId }
                if (affiliate != null) {
                    net.affscash.android.ui.manager.ManagerEditAffiliateScreen(
                        affiliate = affiliate,
                        onNavigateBack = { navController.popBackStack() },
                        viewModel = viewModel
                    )
                }
            }
            composable("manager_view_affiliate/{affId}") { backStackEntry ->
                val affId = backStackEntry.arguments?.getString("affId")?.toIntOrNull() ?: 0
                val viewModel: net.affscash.android.ui.manager.ManagerAffiliatesViewModel = androidx.hilt.navigation.compose.hiltViewModel()
                net.affscash.android.ui.manager.ManagerAffiliateDetailsScreen(
                    affId = affId,
                    onNavigateBack = { navController.popBackStack() },
                    viewModel = viewModel
                )
            }
            composable(Screen.ManagerConversions.route) { 
                net.affscash.android.ui.manager.ManagerConversionsScreen(
                    onNavigateToDuplicates = { navController.navigate("manager_duplicate_conversions") },
                    onNavigateToFraud = { navController.navigate("manager_fraud_reports") }
                ) 
            }
            composable("manager_duplicate_conversions") { 
                net.affscash.android.ui.manager.ManagerDuplicateConversionsScreen(
                    onNavigateBack = { navController.popBackStack() }
                ) 
            }
            composable("manager_fraud_reports") { 
                net.affscash.android.ui.manager.ManagerFraudReportsScreen(
                    onNavigateBack = { navController.popBackStack() }
                ) 
            }
            composable(Screen.ManagerReports.route) { 
                net.affscash.android.ui.manager.ManagerReportsScreen(
                    onNavigateBack = { navController.popBackStack() }
                ) 
            }
            composable(Screen.ManagerReferral.route) { 
                net.affscash.android.ui.manager.referral.ManagerReferralScreen() 
            }
            composable(Screen.ManagerSupport.route) {
                net.affscash.android.ui.manager.support.ManagerSupportScreen(
                    navController = navController
                )
            }
            composable(Screen.ManagerChat.route) {
                val viewModel: net.affscash.android.ui.manager.support.ManagerSupportViewModel = androidx.hilt.navigation.compose.hiltViewModel(
                    navController.getBackStackEntry(Screen.ManagerSupport.route)
                )
                net.affscash.android.ui.manager.support.ManagerChatScreen(
                    navController = navController,
                    viewModel = viewModel
                )
            }
            composable(Screen.ManagerInvoices.route) { 
                net.affscash.android.ui.manager.ManagerInvoicesScreen() 
            }
            composable(Screen.ManagerSettings.route) { 
                net.affscash.android.ui.manager.ManagerProfileScreen(
                    onLogout = onLogout
                ) 
            }
            
            // Admin Shop Screens
            composable(Screen.AdminShop.route) {
                net.affscash.android.ui.admin.shop.AdminShopScreen(
                    onNavigateToCreateProduct = { navController.navigate(Screen.AdminShopProductForm.createRoute(null)) },
                    onNavigateToEditProduct = { id -> navController.navigate(Screen.AdminShopProductForm.createRoute(id)) },
                    onNavigateToOrders = { navController.navigate(Screen.AdminShopOrders.route) }
                )
            }
            composable(
                route = Screen.AdminShopProductForm.routePattern,
                arguments = listOf(androidx.navigation.navArgument("id") {
                    type = androidx.navigation.NavType.StringType
                    nullable = true
                })
            ) { backStackEntry ->
                val idStr = backStackEntry.arguments?.getString("id")
                val id = if (idStr == "{id}") null else idStr?.toIntOrNull()
                net.affscash.android.ui.admin.shop.AdminShopProductFormScreen(
                    productId = id,
                    onNavigateBack = { navController.popBackStack() }
                )
            }
            composable(Screen.AdminShopOrders.route) {
                net.affscash.android.ui.admin.shop.AdminShopOrdersScreen(
                    onNavigateBack = { navController.popBackStack() }
                )
            }
        }
    }
}
