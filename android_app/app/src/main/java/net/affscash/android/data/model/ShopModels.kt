package net.affscash.android.data.model

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class ShopListResponse(
    val success: Boolean,
    val balance: PointsBalance? = null,
    val products: List<ShopProduct> = emptyList(),
    val orders: List<ShopOrder> = emptyList(),
    val error: String? = null
)

@Serializable
data class PointsBalance(
    val balance: Int,
    @SerialName("lifetime_earned") val lifetimeEarned: Int,
    @SerialName("lifetime_spent") val lifetimeSpent: Int
)

@Serializable
data class ShopProduct(
    val id: Int,
    val name: String,
    val description: String?,
    @SerialName("image_path") val imagePath: String?,
    @SerialName("price_points") val pricePoints: Int,
    val stock: Int,
    val status: String
)

@Serializable
data class ShopOrder(
    val id: Int,
    @SerialName("product_name") val productName: String,
    @SerialName("price_points") val pricePoints: Int,
    @SerialName("recipient_name") val recipientName: String?,
    val status: String,
    @SerialName("created_at") val createdAt: String
)

@Serializable
data class PlaceOrderRequest(
    @SerialName("product_id") val productId: Int,
    @SerialName("idempotency_key") val idempotencyKey: String,
    @SerialName("recipient_name") val recipientName: String,
    val phone: String,
    val email: String,
    val address: String,
    val notes: String
)

@Serializable
data class ShopOrderResponse(
    val success: Boolean,
    val message: String? = null,
    @SerialName("order_id") val orderId: Int? = null,
    val error: String? = null
)
