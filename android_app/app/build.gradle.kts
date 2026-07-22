plugins {
  alias(libs.plugins.android.application)
  alias(libs.plugins.compose.compiler)
  alias(libs.plugins.kotlin.android)
  alias(libs.plugins.kotlin.serialization)
  alias(libs.plugins.hilt)
  alias(libs.plugins.ksp)
  alias(libs.plugins.google.services)
}

import java.util.Properties
import java.io.FileInputStream

android {
    namespace = "net.affscash.android"
    compileSdk = 35
    defaultConfig {
        applicationId = "net.affscash.android"
        minSdk = 23
        targetSdk = 35
        versionCode = 10
        versionName = "2.0.2"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
        vectorDrawables {
            useSupportLibrary = true
        }
    }

    signingConfigs {
        val keystoreFile = rootProject.file("keystore.properties")
        val properties = Properties()
        if (keystoreFile.exists()) {
            properties.load(FileInputStream(keystoreFile))
        }

        getByName("debug") {
            enableV1Signing = true
            enableV2Signing = true
            enableV3Signing = true
            enableV4Signing = true
        }

        create("release") {
            if (keystoreFile.exists()) {
                storeFile = rootProject.file(properties.getProperty("storeFile") ?: "")
                storePassword = properties.getProperty("storePassword")
                keyAlias = properties.getProperty("keyAlias")
                keyPassword = properties.getProperty("keyPassword")
            }
            enableV1Signing = true
            enableV2Signing = true
            enableV3Signing = true
            enableV4Signing = true
        }
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
            
            val keystoreFile = rootProject.file("keystore.properties")
            if (keystoreFile.exists()) {
                signingConfig = signingConfigs.getByName("release")
            } else {
                signingConfig = signingConfigs.getByName("debug")
            }
        }
        debug {
            signingConfig = signingConfigs.getByName("debug")
        }
    }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        freeCompilerArgs += "-opt-in=androidx.compose.material3.ExperimentalMaterial3Api"
    }

    buildFeatures {
      compose = true
      aidl = false
      buildConfig = false
      shaders = false
    }

    packaging {
        resources {
            excludes += "/META-INF/{AL2.0,LGPL2.1}"
        }
        jniLibs {
            // Android 15 (API 35) requirement for 16KB page alignment
            // Setting this to false ensures native libraries are stored uncompressed and aligned
            useLegacyPackaging = false
        }
    }
}

kotlin {
    jvmToolchain(17)
}

ksp {
    arg("hilt.correctErrorTypes", "true")
}

dependencies {
  val composeBom = platform(libs.androidx.compose.bom)
  implementation(composeBom)
  androidTestImplementation(composeBom)

  // Core Android dependencies
  implementation(libs.androidx.core.ktx)
  implementation(libs.androidx.lifecycle.runtime.ktx)
  implementation(libs.androidx.activity.compose)

  // Arch Components
  implementation(libs.androidx.lifecycle.runtime.compose)
  implementation(libs.androidx.lifecycle.viewmodel.compose)

  // Compose
  implementation(libs.androidx.compose.ui)
  implementation(libs.androidx.compose.ui.tooling.preview)
  implementation(libs.androidx.compose.foundation)
  implementation(libs.androidx.compose.material3)
  implementation(libs.androidx.compose.material.icons.extended)
  // Tooling
  debugImplementation(libs.androidx.compose.ui.tooling)
  // Instrumented tests
  androidTestImplementation(libs.androidx.compose.ui.test.junit4)
  debugImplementation(libs.androidx.compose.ui.test.manifest)

  // Local tests: jUnit, coroutines, Android runner
  testImplementation(libs.junit)
  testImplementation(libs.kotlinx.coroutines.test)

  // Instrumented tests: jUnit rules and runners
  androidTestImplementation(libs.androidx.test.core)
  androidTestImplementation(libs.androidx.test.ext.junit)
  androidTestImplementation(libs.androidx.test.runner)
  androidTestImplementation(libs.androidx.test.espresso.core)

  // Networking (Retrofit, OkHttp, KotlinX Serialization)
  implementation(libs.retrofit)
  implementation(libs.retrofit.kotlinx.serialization)
  implementation(libs.gson)
  implementation(platform(libs.okhttp.bom))
  implementation(libs.okhttp)
  implementation(libs.okhttp.logging)
  implementation(libs.kotlinx.serialization.json)
  
  // Room Database
  implementation(libs.room.runtime)
  implementation(libs.room.ktx)
  ksp(libs.room.compiler)
  
  // Dependency Injection (Hilt)
  implementation(libs.hilt.android)
  ksp(libs.hilt.compiler)
  implementation(libs.hilt.navigation.compose)
  
  // Image Loading (Coil)
  implementation(libs.coil.compose)
  implementation(libs.coil.svg)
  implementation(libs.coil.gif)

  // Charting (Vico)
  implementation("com.patrykandpatrick.vico:compose-m3:1.15.0")
  implementation("com.patrykandpatrick.vico:core:1.15.0")

  // ZXing for QR Code generation
  implementation("com.google.zxing:core:3.5.2")

  // Firebase
  implementation(platform(libs.firebase.bom))
  implementation(libs.firebase.messaging)
  implementation(libs.firebase.analytics)
  implementation(libs.kotlinx.coroutines.play.services)

  // WorkManager (reliable FCM token registration)
  implementation(libs.work.runtime.ktx)
  implementation(libs.hilt.work)
  ksp(libs.hilt.work.compiler)

  // Biometric & Security Crypto
  implementation(libs.androidx.biometric)
  implementation(libs.androidx.security.crypto)
}

