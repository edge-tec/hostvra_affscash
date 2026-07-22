package net.affscash.android.data.local

import android.content.Context
import android.content.SharedPreferences
import dagger.hilt.android.qualifiers.ApplicationContext
import net.affscash.android.data.model.DateRangeOption
import net.affscash.android.data.model.DateRangeState
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class DateRangePreferenceManager @Inject constructor(
    @ApplicationContext context: Context
) {
    private val prefs: SharedPreferences = context.getSharedPreferences("date_range_prefs", Context.MODE_PRIVATE)

    fun getDateRangeState(sectionKey: String): DateRangeState {
        val optionKey = prefs.getString("${sectionKey}_option", DateRangeOption.THIS_MONTH.key)
        val customStart = prefs.getString("${sectionKey}_custom_start", "") ?: ""
        val customEnd = prefs.getString("${sectionKey}_custom_end", "") ?: ""
        return DateRangeState(
            option = DateRangeOption.fromKey(optionKey),
            customStartDate = customStart,
            customEndDate = customEnd
        )
    }

    fun saveDateRangeState(sectionKey: String, state: DateRangeState) {
        prefs.edit()
            .putString("${sectionKey}_option", state.option.key)
            .putString("${sectionKey}_custom_start", state.customStartDate)
            .putString("${sectionKey}_custom_end", state.customEndDate)
            .apply()
    }
}
