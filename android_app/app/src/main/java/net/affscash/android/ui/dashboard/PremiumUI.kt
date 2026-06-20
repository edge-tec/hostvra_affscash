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
    val GlassPurple = Brush.horizontalGradient(
        colors = listOf(
            Color(0xFF3B82F6), // Blue 500
            Color(0xFF8B5CF6), // Violet 500
            Color(0xFFE879F9)  // Fuchsia 400
        )
    )
}
