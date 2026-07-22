package net.affscash.android.data.model

import java.time.DayOfWeek
import java.time.LocalDate
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.time.temporal.TemporalAdjusters

enum class DateRangeOption(val label: String, val key: String) {
    TODAY("Today", "today"),
    YESTERDAY("Yesterday", "yesterday"),
    THIS_MONTH("This Month", "this_month"),
    LAST_MONTH("Last Month", "last_month"),
    LAST_7_DAYS("Last 7 Days", "last_7_days"),
    LAST_15_DAYS("Last 15 Days", "last_15_days"),
    LAST_30_DAYS("Last 30 Days", "last_30_days"),
    LAST_90_DAYS("Last 90 Days", "last_90_days"),
    CUSTOM("Custom Range", "custom");

    companion object {
        fun fromKey(key: String?): DateRangeOption {
            return values().find { it.key.equals(key, ignoreCase = true) } ?: THIS_MONTH
        }
    }
}

data class DateRangeState(
    val option: DateRangeOption = DateRangeOption.THIS_MONTH,
    val customStartDate: String = "",
    val customEndDate: String = ""
) {
    private val dateFormatter = DateTimeFormatter.ofPattern("yyyy-MM-dd")

    /**
     * Calculates (start_date, end_date) as Pair<String, String> based on local timezone.
     */
    fun getFormattedDates(): Pair<String, String> {
        val today = LocalDate.now(ZoneId.systemDefault())

        return when (option) {
            DateRangeOption.TODAY -> {
                val dateStr = today.format(dateFormatter)
                Pair(dateStr, dateStr)
            }
            DateRangeOption.YESTERDAY -> {
                val yesterday = today.minusDays(1)
                val dateStr = yesterday.format(dateFormatter)
                Pair(dateStr, dateStr)
            }
            DateRangeOption.THIS_MONTH -> {
                val firstDay = today.with(TemporalAdjusters.firstDayOfMonth())
                Pair(firstDay.format(dateFormatter), today.format(dateFormatter))
            }
            DateRangeOption.LAST_MONTH -> {
                val lastMonthDate = today.minusMonths(1)
                val firstDay = lastMonthDate.with(TemporalAdjusters.firstDayOfMonth())
                val lastDay = lastMonthDate.with(TemporalAdjusters.lastDayOfMonth())
                Pair(firstDay.format(dateFormatter), lastDay.format(dateFormatter))
            }
            DateRangeOption.LAST_7_DAYS -> {
                val startDay = today.minusDays(6)
                Pair(startDay.format(dateFormatter), today.format(dateFormatter))
            }
            DateRangeOption.LAST_15_DAYS -> {
                val startDay = today.minusDays(14)
                Pair(startDay.format(dateFormatter), today.format(dateFormatter))
            }
            DateRangeOption.LAST_30_DAYS -> {
                val startDay = today.minusDays(29)
                Pair(startDay.format(dateFormatter), today.format(dateFormatter))
            }
            DateRangeOption.LAST_90_DAYS -> {
                val startDay = today.minusDays(89)
                Pair(startDay.format(dateFormatter), today.format(dateFormatter))
            }
            DateRangeOption.CUSTOM -> {
                val start = if (customStartDate.isNotBlank()) customStartDate else today.with(TemporalAdjusters.firstDayOfMonth()).format(dateFormatter)
                val end = if (customEndDate.isNotBlank()) customEndDate else today.format(dateFormatter)
                Pair(start, end)
            }
        }
    }

    val displayLabel: String
        get() {
            val (start, end) = getFormattedDates()
            return if (option == DateRangeOption.CUSTOM) {
                "$start to $end"
            } else {
                "${option.label} ($start - $end)"
            }
        }
}
