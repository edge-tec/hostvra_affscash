package net.affscash.android.data.model

import com.google.gson.annotations.SerializedName

data class AdminShopProduct(
    val id: Int,
    val name: String,
    val description: String?,
    @SerializedName("price_points") val pricePoints: Int,
    val stock: String?,
    @SerializedName("image_path") val imagePath: String?,
    val status: String,
    @SerializedName("created_at") val createdAt: String?
)

data class AdminShopOrder(
    val id: Int,
    @SerializedName("product_name") val productName: String?,
    @SerializedName("points_spent") val pointsSpent: Int,
    @SerializedName("affiliate_id") val affiliateId: Int,
    @SerializedName("first_name") val firstName: String?,
    @SerializedName("last_name") val lastName: String?,
    val status: String,
    @SerializedName("tracking_code") val trackingCode: String?,
    @SerializedName("admin_note") val adminNote: String?,
    @SerializedName("created_at") val createdAt: String?
) {
    val affiliateName: String
        get() = "${firstName ?: ""} ${lastName ?: ""}".trim().takeIf { it.isNotEmpty() } ?: "Unknown"
}

data class AdminShopDashboardResponse(
    val success: Boolean,
    val data: AdminShopDashboardData?,
    val error: String?
)

data class AdminShopDashboardData(
    val products: List<AdminShopProduct>,
    @SerializedName("recent_orders") val recentOrders: List<AdminShopOrder>
)

data class AdminShopOrdersResponse(
    val success: Boolean,
    val data: AdminShopOrdersData?,
    val error: String?
)

data class AdminShopOrdersData(
    val orders: List<AdminShopOrder>
)

data class AdminShopProductRequest(
    val id: Int? = null,
    val name: String,
    val description: String = "",
    @SerializedName("price_points") val pricePoints: Int,
    val stock: String = "",
    val status: String = "active",
    @SerializedName("image_base64") val imageBase64: String? = null,
    @SerializedName("image_path") val imagePath: String? = null
)

data class AdminShopOrderUpdateRequest(
    @SerializedName("order_id") val orderId: Int,
    val status: String,
    @SerializedName("tracking_code") val trackingCode: String?,
    @SerializedName("admin_note") val adminNote: String?
)

data class AdminShopActionResponse(
    val success: Boolean,
    val message: String?,
    val error: String?
)
