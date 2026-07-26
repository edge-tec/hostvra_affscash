package net.affscash.android.ui.dashboard

import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.tween
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowDownward
import androidx.compose.material.icons.filled.ArrowUpward
import androidx.compose.material.icons.filled.TrendingUp
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.scale
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.patrykandpatrick.vico.compose.axis.horizontal.rememberBottomAxis
import com.patrykandpatrick.vico.compose.axis.vertical.rememberStartAxis
import com.patrykandpatrick.vico.compose.chart.Chart
import com.patrykandpatrick.vico.compose.chart.column.columnChart
import com.patrykandpatrick.vico.core.entry.FloatEntry
import com.patrykandpatrick.vico.core.entry.entryModelOf

object PremiumUI {
    // 3D Soft Slate & Ambient Gradient Tokens
    val PageBackground = Color(0xFFF1F5F9)
    val GlassCardBg = Color(0xFFFFFFFF)
    
    // 3D Bevel Border (Light top highlight to subtle slate bottom shadow)
    val GlassBorder = BorderStroke(
        1.dp, 
        Brush.verticalGradient(
            colors = listOf(Color(0xFFFFFFFF), Color(0xFFCBD5E1))
        )
    )

    val Card3DBorder = BorderStroke(
        1.2.dp,
        Brush.linearGradient(
            colors = listOf(Color(0xFFFFFFFF), Color(0xFFE2E8F0), Color(0xFF94A3B8).copy(alpha = 0.3f))
        )
    )
    
    val BackgroundGradient = Brush.verticalGradient(
        colors = listOf(Color(0xFFF8FAFC), Color(0xFFF1F5F9), Color(0xFFE2E8F0))
    )

    val CardGradient = Brush.verticalGradient(
        colors = listOf(Color(0xFFFFFFFF), Color(0xFFF8FAFC))
    )

    val PastelHeader = Brush.horizontalGradient(
        colors = listOf(Color(0xFF4F46E5), Color(0xFF6366F1), Color(0xFF818CF8))
    )

    val HeaderGradient = Brush.horizontalGradient(
        colors = listOf(
            Color(0xFF4338CA), // Deep Indigo
            Color(0xFF6D28D9), // Vibrant Purple
            Color(0xFFDB2777)  // Deep Pink
        )
    )

    val PrimaryGradient = Brush.verticalGradient(
        colors = listOf(Color(0xFF6366F1), Color(0xFF4F46E5))
    )

    val EmeraldGradient = Brush.verticalGradient(
        colors = listOf(Color(0xFF10B981), Color(0xFF047857))
    )

    val CyanGradient = Brush.verticalGradient(
        colors = listOf(Color(0xFF06B6D4), Color(0xFF0369A1))
    )

    val PurpleGradient = Brush.verticalGradient(
        colors = listOf(Color(0xFF8B5CF6), Color(0xFF5B21B6))
    )

    val AmberGradient = Brush.verticalGradient(
        colors = listOf(Color(0xFFF59E0B), Color(0xFFB45309))
    )

    val RoseGradient = Brush.verticalGradient(
        colors = listOf(Color(0xFFF43F5E), Color(0xFFBE123C))
    )

    // Ultra-Modern 3D Corner Shapes
    val CardShape = RoundedCornerShape(20.dp)
    val PillShape = RoundedCornerShape(50.dp)
    val ButtonShape = RoundedCornerShape(16.dp)

    // Status Colors
    val StatusApproved = Color(0xFF059669)
    val StatusApprovedBg = Color(0xFFD1FAE5)
    val StatusRejected = Color(0xFFDC2626)
    val StatusRejectedBg = Color(0xFFFEE2E2)
    val StatusPending = Color(0xFFD97706)
    val StatusPendingBg = Color(0xFFFEF3C7)

    // Typography
    val HeaderStyle = TextStyle(
        fontWeight = FontWeight.Bold,
        fontSize = 18.sp,
        letterSpacing = (-0.2).sp
    )
    val TitleMedium = TextStyle(
        fontWeight = FontWeight.SemiBold,
        fontSize = 14.sp,
        letterSpacing = 0.1.sp
    )
    val DataBold = TextStyle(
        fontWeight = FontWeight.Bold,
        fontSize = 15.sp
    )
    val SecondaryText = TextStyle(
        fontWeight = FontWeight.Normal,
        fontSize = 12.sp,
        color = Color(0xFF64748B)
    )
    val LabelSmall = TextStyle(
        fontWeight = FontWeight.Medium,
        fontSize = 11.sp,
        color = Color(0xFF94A3B8)
    )
}

/**
 * 3D Glass Container with press scale dynamics and multi-level ambient depth
 */
@Composable
fun GlassCard(
    modifier: Modifier = Modifier,
    onClick: (() -> Unit)? = null,
    elevation: Dp = 4.dp,
    containerColor: Color = Color.White,
    content: @Composable ColumnScope.() -> Unit
) {
    var isPressed by remember { mutableStateOf(false) }
    val scale by animateFloatAsState(
        targetValue = if (isPressed) 0.98f else 1f,
        animationSpec = tween(durationMillis = 100),
        label = "cardScale"
    )

    Card(
        modifier = modifier
            .fillMaxWidth()
            .scale(scale)
            .then(
                if (onClick != null) {
                    Modifier.clickable(
                        interactionSource = remember { MutableInteractionSource() },
                        indication = null,
                        onClick = { onClick() }
                    )
                } else Modifier
            ),
        shape = PremiumUI.CardShape,
        colors = CardDefaults.cardColors(containerColor = containerColor),
        elevation = CardDefaults.cardElevation(
            defaultElevation = elevation,
            pressedElevation = 2.dp
        ),
        border = PremiumUI.Card3DBorder
    ) {
        Column(
            modifier = Modifier
                .background(PremiumUI.CardGradient)
                .padding(16.dp),
            content = content
        )
    }
}

/**
 * Advanced 3D KPI Metric Card with bevel styling and high-contrast numerical metrics
 */
@Composable
fun KPICard3D(
    title: String,
    value: String,
    subtitle: String? = null,
    icon: ImageVector,
    iconGradient: Brush = PremiumUI.PrimaryGradient,
    trendPercent: Double? = null,
    modifier: Modifier = Modifier,
    onClick: (() -> Unit)? = null
) {
    Card(
        modifier = modifier
            .then(if (onClick != null) Modifier.clickable { onClick() } else Modifier),
        shape = PremiumUI.CardShape,
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp, pressedElevation = 2.dp),
        border = PremiumUI.Card3DBorder
    ) {
        Column(
            modifier = Modifier
                .background(PremiumUI.CardGradient)
                .padding(14.dp),
            verticalArrangement = Arrangement.SpaceBetween
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                // 3D Bevel Gradient Icon Box
                Box(
                    modifier = Modifier
                        .size(40.dp)
                        .clip(RoundedCornerShape(14.dp))
                        .background(iconGradient)
                        .border(1.dp, Color.White.copy(alpha = 0.4f), RoundedCornerShape(14.dp)),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = icon,
                        contentDescription = title,
                        tint = Color.White,
                        modifier = Modifier.size(20.dp)
                    )
                }

                // 3D Trend Pill (if available)
                if (trendPercent != null) {
                    val isPositive = trendPercent >= 0
                    val badgeBg = if (isPositive) Color(0xFFD1FAE5) else Color(0xFFFEE2E2)
                    val badgeColor = if (isPositive) Color(0xFF047857) else Color(0xFFB91C1C)

                    Surface(
                        color = badgeBg,
                        shape = PremiumUI.PillShape,
                        shadowElevation = 1.dp,
                        border = BorderStroke(0.5.dp, badgeColor.copy(alpha = 0.3f))
                    ) {
                        Row(
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(
                                imageVector = if (isPositive) Icons.Default.ArrowUpward else Icons.Default.ArrowDownward,
                                contentDescription = null,
                                tint = badgeColor,
                                modifier = Modifier.size(12.dp)
                            )
                            Spacer(modifier = Modifier.width(2.dp))
                            Text(
                                text = String.format("%.1f%%", Math.abs(trendPercent)),
                                fontSize = 10.sp,
                                fontWeight = FontWeight.Bold,
                                color = badgeColor
                            )
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(10.dp))

            Text(
                text = title.uppercase(),
                style = PremiumUI.LabelSmall,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )

            Spacer(modifier = Modifier.height(2.dp))

            Text(
                text = value,
                fontSize = 20.sp,
                fontWeight = FontWeight.Black,
                color = Color(0xFF0F172A),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )

            if (!subtitle.isNullOrBlank()) {
                Spacer(modifier = Modifier.height(2.dp))
                Text(
                    text = subtitle,
                    style = PremiumUI.SecondaryText,
                    fontSize = 11.sp,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }
        }
    }
}

/**
 * 3D Segmented Control Row for Date Filters
 */
@Composable
fun Segmented3DDateFilter(
    options: List<DateFilter>,
    selectedOption: DateFilter,
    onOptionSelected: (DateFilter) -> Unit,
    modifier: Modifier = Modifier
) {
    val scrollState = rememberScrollState()

    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = PremiumUI.CardShape,
        color = Color(0xFFF1F5F9),
        shadowElevation = 3.dp,
        border = PremiumUI.Card3DBorder
    ) {
        Row(
            modifier = Modifier
                .horizontalScroll(scrollState)
                .padding(5.dp),
            horizontalArrangement = Arrangement.spacedBy(4.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            options.forEach { option ->
                val isSelected = option == selectedOption

                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(14.dp))
                        .background(
                            if (isSelected) PremiumUI.PrimaryGradient
                            else Brush.verticalGradient(listOf(Color.White, Color(0xFFF8FAFC)))
                        )
                        .border(
                            1.dp,
                            if (isSelected) Color.White.copy(alpha = 0.4f) else Color(0xFFCBD5E1),
                            RoundedCornerShape(14.dp)
                        )
                        .clickable { onOptionSelected(option) }
                        .padding(horizontal = 14.dp, vertical = 9.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = option.label,
                        fontSize = 12.sp,
                        fontWeight = if (isSelected) FontWeight.Bold else FontWeight.SemiBold,
                        color = if (isSelected) Color.White else Color(0xFF475569)
                    )
                }
            }
        }
    }
}

@Composable
fun StatusBadge(status: String, modifier: Modifier = Modifier) {
    val lowerStatus = status.lowercase()
    val (textColor, bgColor) = when {
        lowerStatus.contains("approve") -> PremiumUI.StatusApproved to PremiumUI.StatusApprovedBg
        lowerStatus.contains("reject") || lowerStatus.contains("chargeback") -> PremiumUI.StatusRejected to PremiumUI.StatusRejectedBg
        else -> PremiumUI.StatusPending to PremiumUI.StatusPendingBg
    }

    Surface(
        color = bgColor,
        shape = RoundedCornerShape(8.dp),
        modifier = modifier
    ) {
        Text(
            text = status.uppercase(),
            color = textColor,
            fontSize = 10.sp,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
        )
    }
}

@Composable
fun BarChartCard3D(labels: List<String>, data: List<Float>) {
    if (labels.isEmpty() || data.isEmpty()) {
        GlassCard {
            Box(contentAlignment = Alignment.Center, modifier = Modifier.fillMaxSize().height(120.dp)) {
                Text("No data available", color = Color.Gray)
            }
        }
        return
    }

    val entries = data.mapIndexed { index, value ->
        FloatEntry(x = index.toFloat(), y = value)
    }
    val model = entryModelOf(entries)

    GlassCard(elevation = 2.dp) {
        Box(modifier = Modifier.fillMaxWidth().height(150.dp)) {
            Chart(
                chart = columnChart(),
                model = model,
                startAxis = rememberStartAxis(),
                bottomAxis = rememberBottomAxis(
                    valueFormatter = { value, _ -> 
                        val index = value.toInt()
                        if (index >= 0 && index < labels.size) labels[index] else ""
                    }
                ),
                modifier = Modifier.fillMaxSize()
            )
        }
    }
}

@Composable
fun PieChartCard3D(labels: List<String>, data: List<Int>, customColors: List<Color>? = null) {
    GlassCard(elevation = 2.dp) {
        if (labels.isEmpty() || data.isEmpty() || data.sum() == 0) {
            Box(contentAlignment = Alignment.Center, modifier = Modifier.fillMaxSize().height(140.dp)) {
                Text("No data available", color = Color(0xFF64748B), fontSize = 13.sp)
            }
        } else {
            val colors = customColors ?: listOf(Color(0xFF4F46E5), Color(0xFF10B981), Color(0xFFF59E0B), Color(0xFFEF4444), Color(0xFF8B5CF6))
            val totalSum = data.sum()
            val total = totalSum.toFloat()
            
            Column(
                modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.Center
            ) {
                Box(
                    modifier = Modifier.size(96.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Canvas(modifier = Modifier.fillMaxSize()) {
                        var startAngle = -90f
                        data.forEachIndexed { index, value ->
                            val sweepAngle = (value / total) * 360f
                            drawArc(
                                color = colors[index % colors.size],
                                startAngle = startAngle,
                                sweepAngle = sweepAngle,
                                useCenter = false,
                                style = Stroke(width = 20f, cap = StrokeCap.Round),
                                size = Size(size.width, size.height)
                            )
                            startAngle += sweepAngle
                        }
                    }
                    
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text(
                            text = "$totalSum",
                            fontSize = 16.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = Color(0xFF0F172A)
                        )
                        Text(
                            text = "TOTAL",
                            fontSize = 9.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF94A3B8)
                        )
                    }
                }
                
                Spacer(modifier = Modifier.height(10.dp))
                
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.Center,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    labels.take(4).forEachIndexed { index, label ->
                        val itemVal = if (index < data.size) data[index] else 0
                        val pct = if (totalSum > 0) (itemVal.toFloat() / totalSum * 100).toInt() else 0
                        
                        Surface(
                            color = Color(0xFFF8FAFC),
                            shape = RoundedCornerShape(8.dp),
                            modifier = Modifier.padding(horizontal = 4.dp),
                            border = BorderStroke(1.dp, Color(0xFFE2E8F0))
                        ) {
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                            ) {
                                Box(modifier = Modifier.size(8.dp).background(colors[index % colors.size], CircleShape))
                                Spacer(modifier = Modifier.width(5.dp))
                                Text(
                                    text = "$label: $itemVal ($pct%)",
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.SemiBold,
                                    color = Color(0xFF334155),
                                    maxLines = 1
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}
