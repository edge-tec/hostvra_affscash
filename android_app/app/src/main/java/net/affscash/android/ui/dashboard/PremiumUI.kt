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
    // Soft Slate & Pure White Tokens (No Harsh White Glow)
    val PageBackground = Color(0xFFF8FAFC)
    val GlassCardBg = Color(0xFFFFFFFF)
    val GlassBorder = BorderStroke(1.dp, Color(0xFFE2E8F0))
    
    val BackgroundGradient = Brush.verticalGradient(
        colors = listOf(Color(0xFFF8FAFC), Color(0xFFF1F5F9), Color(0xFFE2E8F0))
    )

    val CardGradient = Brush.linearGradient(
        colors = listOf(Color(0xFFFFFFFF), Color(0xFFFFFFFF))
    )

    val PastelHeader = Brush.horizontalGradient(
        colors = listOf(Color(0xFF4F46E5), Color(0xFF6366F1), Color(0xFF818CF8))
    )

    val HeaderGradient = Brush.horizontalGradient(
        colors = listOf(
            Color(0xFF4F46E5), // Indigo
            Color(0xFF7C3AED), // Purple
            Color(0xFFEC4899)  // Pink accent
        )
    )

    val PrimaryGradient = Brush.horizontalGradient(
        colors = listOf(Color(0xFF4F46E5), Color(0xFF6366F1))
    )

    val EmeraldGradient = Brush.horizontalGradient(
        colors = listOf(Color(0xFF10B981), Color(0xFF059669))
    )

    val CyanGradient = Brush.horizontalGradient(
        colors = listOf(Color(0xFF06B6D4), Color(0xFF0284C7))
    )

    val PurpleGradient = Brush.horizontalGradient(
        colors = listOf(Color(0xFF8B5CF6), Color(0xFF6D28D9))
    )

    val AmberGradient = Brush.horizontalGradient(
        colors = listOf(Color(0xFFF59E0B), Color(0xFFD97706))
    )

    val RoseGradient = Brush.horizontalGradient(
        colors = listOf(Color(0xFFF43F5E), Color(0xFFE11D48))
    )

    // Rounded Corner Shapes (18-24dp)
    val CardShape = RoundedCornerShape(18.dp)
    val PillShape = RoundedCornerShape(50.dp)
    val ButtonShape = RoundedCornerShape(14.dp)

    // Status Colors
    val StatusApproved = Color(0xFF10B981)
    val StatusApprovedBg = Color(0xFFD1FAE5)
    val StatusRejected = Color(0xFFEF4444)
    val StatusRejectedBg = Color(0xFFFEE2E2)
    val StatusPending = Color(0xFFF59E0B)
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
 * Clean Card Container without white glow or harsh drop shadows
 */
@Composable
fun GlassCard(
    modifier: Modifier = Modifier,
    onClick: (() -> Unit)? = null,
    elevation: Dp = 2.dp,
    containerColor: Color = Color.White,
    content: @Composable ColumnScope.() -> Unit
) {
    Card(
        modifier = modifier
            .fillMaxWidth()
            .then(
                if (onClick != null) Modifier.clickable { onClick() } else Modifier
            ),
        shape = PremiumUI.CardShape,
        colors = CardDefaults.cardColors(containerColor = containerColor),
        elevation = CardDefaults.cardElevation(
            defaultElevation = elevation,
            pressedElevation = 1.dp
        ),
        border = PremiumUI.GlassBorder
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            content = content
        )
    }
}

/**
 * Modern 3D KPI Metric Card with clean borders and no white shadow halo
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
        elevation = CardDefaults.cardElevation(defaultElevation = 3.dp, pressedElevation = 1.dp),
        border = PremiumUI.GlassBorder
    ) {
        Column(
            modifier = Modifier.padding(14.dp),
            verticalArrangement = Arrangement.SpaceBetween
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Gradient Icon Box
                Box(
                    modifier = Modifier
                        .size(38.dp)
                        .clip(RoundedCornerShape(12.dp))
                        .background(iconGradient),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = icon,
                        contentDescription = title,
                        tint = Color.White,
                        modifier = Modifier.size(20.dp)
                    )
                }

                // Trend Pill (if available)
                if (trendPercent != null) {
                    val isPositive = trendPercent >= 0
                    val badgeBg = if (isPositive) Color(0xFFD1FAE5) else Color(0xFFFEE2E2)
                    val badgeColor = if (isPositive) Color(0xFF059669) else Color(0xFFDC2626)

                    Surface(
                        color = badgeBg,
                        shape = PremiumUI.PillShape
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
                fontSize = 18.sp,
                fontWeight = FontWeight.ExtraBold,
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
 * Segmented Control Row for Date Filters
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
        color = Color.White,
        shadowElevation = 2.dp,
        border = PremiumUI.GlassBorder
    ) {
        Row(
            modifier = Modifier
                .horizontalScroll(scrollState)
                .padding(4.dp),
            horizontalArrangement = Arrangement.spacedBy(4.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            options.forEach { option ->
                val isSelected = option == selectedOption

                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(12.dp))
                        .background(
                            if (isSelected) PremiumUI.PrimaryGradient
                            else Brush.linearGradient(listOf(Color.Transparent, Color.Transparent))
                        )
                        .clickable { onOptionSelected(option) }
                        .padding(horizontal = 14.dp, vertical = 8.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = option.label,
                        fontSize = 12.sp,
                        fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
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
