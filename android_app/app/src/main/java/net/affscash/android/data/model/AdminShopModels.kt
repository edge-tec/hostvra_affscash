package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class AdminShopProduct(
    val id: Int,
    val name: String,
    val description: String? = null,
    @SerialName("price_points") val pricePoints: Int,
    val stock: String? = null,
    @SerialName("image_path") val imagePath: String? = null,
    val status: String,
    @SerialName("created_at") val createdAt: String? = null
)

@Serializable
data class AdminShopOrder(
    val id: Int,
    @SerialName("product_name") val productName: String? = null,
    @SerialName("points_spent") val pointsSpent: Int,
    @SerialName("affiliate_id") val affiliateId: Int,
    @SerialName("first_name") val firstName: String? = null,
    @SerialName("last_name") val lastName: String? = null,
    val status: String,
    @SerialName("tracking_code") val trackingCode: String? = null,
    @SerialName("admin_note") val adminNote: String? = null,
    @SerialName("created_at") val createdAt: String? = null
) {
    val affiliateName: String
        get() = "${firstName ?: ""} ${lastName ?: ""}".trim().takeIf { it.isNotEmpty() } ?: "Unknown"
}

@Serializable
data class AdminShopDashboardResponse(
    val success: Boolean,
    val data: AdminShopDashboardData? = null,
    val error: String? = null
)

@Serializable
data class AdminShopDashboardData(
    val products: List<AdminShopProduct> = emptyList(),
    @SerialName("recent_orders") val recentOrders: List<AdminShopOrder> = emptyList()
)

@Serializable
data class AdminShopOrdersResponse(
    val success: Boolean,
    val data: AdminShopOrdersData? = null,
    val error: String? = null
)

@Serializable
data class AdminShopOrdersData(
    val orders: List<AdminShopOrder> = emptyList()
)

@Serializable
data class AdminShopProductRequest(
    val id: Int? = null,
    val name: String,
    val description: String = "",
    @SerialName("price_points") val pricePoints: Int,
    val stock: String = "",
    val status: String = "active",
    @SerialName("image_base64") val imageBase64: String? = null,
    @SerialName("image_path") val imagePath: String? = null
)

@Serializable
data class AdminShopOrderUpdateRequest(
    @SerialName("order_id") val orderId: Int,
    val status: String,
    @SerialName("tracking_code") val trackingCode: String? = null,
    @SerialName("admin_note") val adminNote: String? = null
)

@Serializable
data class AdminShopActionResponse(
    val success: Boolean,
    val message: String? = null,
    val error: String? = null
)
