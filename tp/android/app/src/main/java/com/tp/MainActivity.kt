package com.tp.cgm.badil

import android.os.Bundle
import android.view.WindowManager
import com.facebook.react.ReactActivity
import com.facebook.react.ReactActivityDelegate
import com.facebook.react.defaults.DefaultNewArchitectureEntryPoint.fabricEnabled
import com.facebook.react.defaults.DefaultReactActivityDelegate

class MainActivity : ReactActivity() {

  /**
   * Returns the name of the main component registered from JavaScript. This is used to schedule
   * rendering of the component.
   */
  override fun getMainComponentName(): String = "tp"

  /**
   * Returns the instance of the [ReactActivityDelegate]. We use [DefaultReactActivityDelegate]
   * which allows you to enable New Architecture with a single boolean flags [fabricEnabled]
   */
  override fun createReactActivityDelegate(): ReactActivityDelegate =
      DefaultReactActivityDelegate(this, mainComponentName, fabricEnabled)

  /**
   * Prevent screenshots and screen recording for security.
   *
   * super.onCreate(null) e' voluto: react-native-screens lo richiede per
   * impedire ad Android di ricostruire i Fragment salvati quando il processo
   * viene ucciso in background (fra questi il BiometricFragment di
   * androidx.biometric, tirato dentro da react-native-keychain). Al ripristino
   * quelle classi non sono piu' istanziabili e l'activity muore in onCreate
   * con androidx.fragment.app.Fragment[InstantiationException]. Lo stato
   * dell'app lo tiene JS, quindi non c'e' nulla da ripristinare qui.
   */
  override fun onCreate(savedInstanceState: Bundle?) {
    super.onCreate(null)
    window.setFlags(
      WindowManager.LayoutParams.FLAG_SECURE,
      WindowManager.LayoutParams.FLAG_SECURE
    )
  }
}
