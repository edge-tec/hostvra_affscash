# Keep all data models for Kotlinx Serialization / Retrofit
-keep class net.affscash.android.data.model.** { *; }
-keepclassmembers class net.affscash.android.data.model.** { *; }

# Keep Compose UI
-keep class net.affscash.android.ui.** { *; }

# Keep Serialization Annotations
-keepattributes *Annotation*,Signature,InnerClasses,EnclosingMethod
-keepclassmembers class * {
    @kotlinx.serialization.SerialName <fields>;
}

# Retrofit & OkHttp
-dontwarn retrofit2.**
-keep class retrofit2.** { *; }
-keepattributes RuntimeVisibleAnnotations,RuntimeVisibleParameterAnnotations

-dontwarn okhttp3.**
-keep class okhttp3.** { *; }

# Coil Image Loader
-keep class coil.** { *; }
