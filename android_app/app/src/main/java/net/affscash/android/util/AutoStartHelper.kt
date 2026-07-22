package net.affscash.android.util

import android.content.ComponentName
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.os.PowerManager
import android.provider.Settings
import android.util.Log

/**
 * OEM AutoStart & Battery Saver compatibility helper for Chinese ROMs
 * (Xiaomi, Oppo, Vivo, Huawei, Samsung, Realme).
 *
 * Ensures background FCM notifications and heads-up popups arrive reliably
 * even when OEM battery optimization or auto-start restrictions are active.
 */
object AutoStartHelper {

    private const val TAG = "AutoStartHelper"

    private val AUTO_START_INTENTS = listOf(
        // Xiaomi / MIUI
        Intent().setComponent(ComponentName("com.miui.securitycenter", "com.miui.permcenter.autostart.AutoStartManagementActivity")),
        // Oppo / ColorOS
        Intent().setComponent(ComponentName("com.coloros.safecenter", "com.coloros.safecenter.permission.startup.StartupAppListActivity")),
        Intent().setComponent(ComponentName("com.oppo.safe", "com.oppo.safe.permission.startup.StartupAppListActivity")),
        // Vivo / FuntouchOS
        Intent().setComponent(ComponentName("com.iqoo.secure", "com.iqoo.secure.ui.phoneoptimize.AddWhiteListActivity")),
        Intent().setComponent(ComponentName("com.vivo.permissionmanager", "com.vivo.permissionmanager.activity.BgStartUpManagerActivity")),
        // Huawei / Honor / EMUI
        Intent().setComponent(ComponentName("com.huawei.systemmanager", "com.huawei.systemmanager.startupmgr.ui.StartupNormalAppListActivity")),
        Intent().setComponent(ComponentName("com.huawei.systemmanager", "com.huawei.systemmanager.optimize.process.ProtectActivity")),
        // Samsung
        Intent().setComponent(ComponentName("com.samsung.android.looper", "com.samsung.android.sm.ui.battery.BatteryActivity")),
        Intent().setComponent(ComponentName("com.samsung.android.sm", "com.samsung.android.sm.ui.battery.BatteryActivity")),
        // Asus
        Intent().setComponent(ComponentName("com.asus.mobilemanager", "com.asus.mobilemanager.autostart.AutoStartActivity")),
        // Realme / Letv
        Intent().setComponent(ComponentName("com.letv.android.letvsafe", "com.letv.android.letvsafe.AutobootManageActivity"))
    )

    /**
     * Checks if the device is an OEM brand known for background notification restrictions.
     */
    fun isOemDevice(): Boolean {
        val manufacturer = Build.MANUFACTURER.lowercase()
        return manufacturer.contains("xiaomi") ||
                manufacturer.contains("redmi") ||
                manufacturer.contains("oppo") ||
                manufacturer.contains("vivo") ||
                manufacturer.contains("huawei") ||
                manufacturer.contains("honor") ||
                manufacturer.contains("samsung") ||
                manufacturer.contains("realme") ||
                manufacturer.contains("asus")
    }

    /**
     * Attempts to open the device-specific AutoStart management screen.
     */
    fun openAutoStartSettings(context: Context): Boolean {
        for (intent in AUTO_START_INTENTS) {
            try {
                if (context.packageManager.queryIntentActivities(intent, 0).isNotEmpty()) {
                    intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                    context.startActivity(intent)
                    Log.d(TAG, "Successfully opened OEM autostart settings")
                    return true
                }
            } catch (e: Exception) {
                Log.w(TAG, "Failed to launch intent: ${e.message}")
            }
        }
        return false
    }

    /**
     * Requests ignoring battery optimizations on Android 6.0+ (M+).
     */
    fun requestIgnoreBatteryOptimizations(context: Context) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            try {
                val powerManager = context.getSystemService(Context.POWER_SERVICE) as PowerManager
                val packageName = context.packageName
                if (!powerManager.isIgnoringBatteryOptimizations(packageName)) {
                    val intent = Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS).apply {
                        data = Uri.parse("package:$packageName")
                        addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                    }
                    context.startActivity(intent)
                }
            } catch (e: Exception) {
                Log.e(TAG, "Failed to request battery optimization ignore: ${e.message}")
            }
        }
    }
}
