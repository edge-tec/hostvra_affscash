package net.affscash.android.ui.dashboard

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.sp

import androidx.compose.foundation.layout.padding
import androidx.compose.ui.unit.dp

object PremiumUI {
    val BackgroundGradient = Brush.verticalGradient(
        colors = listOf(Color(0xFFF8FAFC), Color(0xFFEEF2FF), Color(0xFFE0E7FF))
    )
    val CardGradient = Brush.linearGradient(
        colors = listOf(Color(0xFFFFFFFF), Color(0xFAFFFFFF), Color(0xF2FFFFFF))
    )
    val PrimaryGradient = Brush.linearGradient(
        colors = listOf(Color(0xFF4F46E5), Color(0xFF7C3AED))
    )
    val PastelHeader = Brush.horizontalGradient(
        colors = listOf(
            Color(0xFFAED3E8), // Soft pastel sky blue
            Color(0xFFC9C7DB), // Muted lavender-gray
            Color(0xFFD5B4B8)  // Pale blush rose
        )
    )

    // Page background
    val PageBackground = Color(0xFFF8FAFC)

    // Standard card shape
    val CardShape = androidx.compose.foundation.shape.RoundedCornerShape(10.dp)

    // Status Colors
    val StatusApproved = Color(0xFF10B981)
    val StatusApprovedBg = Color(0xFFD1FAE5)
    val StatusRejected = Color(0xFFEF4444)
    val StatusRejectedBg = Color(0xFFFEE2E2)
    val StatusPending = Color(0xFFF59E0B)
    val StatusPendingBg = Color(0xFFFEF3C7)

    // Typography Tokens
    val HeaderStyle = TextStyle(
        fontWeight = FontWeight.SemiBold,
        fontSize = 18.sp,
        letterSpacing = 0.sp
    )
    val TitleMedium = TextStyle(
        fontWeight = FontWeight.SemiBold,
        fontSize = 14.sp,
        letterSpacing = 0.1.sp
    )
    val DataBold = TextStyle(
        fontWeight = FontWeight.Bold,
        fontSize = 13.sp
    )
    val SecondaryText = TextStyle(
        fontWeight = FontWeight.Normal,
        fontSize = 12.sp,
        color = Color.Gray
    )
    val LabelSmall = TextStyle(
        fontWeight = FontWeight.Medium,
        fontSize = 11.sp,
        color = Color(0xFF9CA3AF)
    )
}

@Composable
fun StatusBadge(status: String, modifier: Modifier = Modifier) {
    val lowerStatus = status.lowercase()
    val (textColor, bgColor) = when {
        lowerStatus.contains("approve") -> PremiumUI.StatusApproved to PremiumUI.StatusApprovedBg
        lowerStatus.contains("reject") || lowerStatus.contains("chargeback") -> PremiumUI.StatusRejected to PremiumUI.StatusRejectedBg
        else -> PremiumUI.StatusPending to PremiumUI.StatusPendingBg
    }

    androidx.compose.material3.Surface(
        color = bgColor,
        shape = androidx.compose.foundation.shape.RoundedCornerShape(6.dp),
        modifier = modifier
    ) {
        androidx.compose.material3.Text(
            text = status.uppercase(),
            color = textColor,
            fontSize = 10.sp,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
        )
    }
}
