package net.affscash.android.util

import android.content.Context
import android.content.pm.PackageManager
import android.os.Build
import androidx.biometric.BiometricManager
import androidx.biometric.BiometricManager.Authenticators.*
import androidx.biometric.BiometricPrompt
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton

data class BiometricCapability(
    val isAvailable: Boolean,
    val isFingerprintSupported: Boolean,
    val isFaceSupported: Boolean,
    val canUseDeviceCredential: Boolean,
    val statusMessage: String
)

@Singleton
class BiometricAuthManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    fun checkBiometricCapability(): BiometricCapability {
        val biometricManager = BiometricManager.from(context)
        val authenticators = BIOMETRIC_STRONG or BIOMETRIC_WEAK or DEVICE_CREDENTIAL

        val result = biometricManager.canAuthenticate(authenticators)
        val pm = context.packageManager

        val isFingerprintSupported = pm.hasSystemFeature(PackageManager.FEATURE_FINGERPRINT)
        val isFaceSupported = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            pm.hasSystemFeature(PackageManager.FEATURE_FACE)
        } else {
            pm.hasSystemFeature("android.hardware.biometrics.face")
        }

        return when (result) {
            BiometricManager.BIOMETRIC_SUCCESS -> {
                BiometricCapability(
                    isAvailable = true,
                    isFingerprintSupported = isFingerprintSupported || !isFaceSupported,
                    isFaceSupported = isFaceSupported,
                    canUseDeviceCredential = true,
                    statusMessage = "Biometric authentication is available"
                )
            }
            BiometricManager.BIOMETRIC_ERROR_NONE_ENROLLED -> {
                BiometricCapability(
                    isAvailable = false,
                    isFingerprintSupported = isFingerprintSupported,
                    isFaceSupported = isFaceSupported,
                    canUseDeviceCredential = true,
                    statusMessage = "No biometric credentials enrolled on device"
                )
            }
            BiometricManager.BIOMETRIC_ERROR_NO_HARDWARE -> {
                BiometricCapability(
                    isAvailable = false,
                    isFingerprintSupported = false,
                    isFaceSupported = false,
                    canUseDeviceCredential = false,
                    statusMessage = "No biometric hardware available"
                )
            }
            BiometricManager.BIOMETRIC_ERROR_HW_UNAVAILABLE -> {
                BiometricCapability(
                    isAvailable = false,
                    isFingerprintSupported = isFingerprintSupported,
                    isFaceSupported = isFaceSupported,
                    canUseDeviceCredential = false,
                    statusMessage = "Biometric hardware is currently unavailable"
                )
            }
            else -> {
                BiometricCapability(
                    isAvailable = false,
                    isFingerprintSupported = false,
                    isFaceSupported = false,
                    canUseDeviceCredential = false,
                    statusMessage = "Biometric authentication unsupported"
                )
            }
        }
    }

    fun authenticate(
        activity: FragmentActivity,
        title: String = "Biometric Sign In",
        subtitle: String = "Log in securely using your fingerprint or face",
        description: String = "Confirm your identity to access AffsCash",
        onSuccess: () -> Unit,
        onError: (String) -> Unit,
        onCancel: () -> Unit = {}
    ) {
        val executor = ContextCompat.getMainExecutor(activity)

        val callback = object : BiometricPrompt.AuthenticationCallback() {
            override fun onAuthenticationSucceeded(result: BiometricPrompt.AuthenticationResult) {
                super.onAuthenticationSucceeded(result)
                onSuccess()
            }

            override fun onAuthenticationError(errorCode: Int, errString: CharSequence) {
                super.onAuthenticationError(errorCode, errString)
                if (errorCode == BiometricPrompt.ERROR_USER_CANCELED || errorCode == BiometricPrompt.ERROR_NEGATIVE_BUTTON) {
                    onCancel()
                } else {
                    onError(errString.toString())
                }
            }

            override fun onAuthenticationFailed() {
                super.onAuthenticationFailed()
                onError("Biometric verification failed. Please try again.")
            }
        }

        val biometricPrompt = BiometricPrompt(activity, executor, callback)

        val promptInfoBuilder = BiometricPrompt.PromptInfo.Builder()
            .setTitle(title)
            .setSubtitle(subtitle)
            .setDescription(description)
            .setAllowedAuthenticators(BIOMETRIC_STRONG or BIOMETRIC_WEAK or DEVICE_CREDENTIAL)

        biometricPrompt.authenticate(promptInfoBuilder.build())
    }
}
