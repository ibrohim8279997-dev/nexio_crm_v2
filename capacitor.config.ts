import type { CapacitorConfig } from '@capacitor/cli'

// The app bundles the built SPA (dist/) and talks to the production API; see docs/MOBILE.md.
const config: CapacitorConfig = {
  appId: 'crm.nexiolab.uz',
  appName: 'Nexio CRM',
  webDir: 'dist',
  server: {
    // Android serves the bundle from https://localhost (listed in the API's MOBILE_APP_ORIGINS); iOS from capacitor://localhost.
    androidScheme: 'https',
  },
  experimental: {
    ios: { spm: { packageOptions: { '@capacitor-firebase/messaging': { symlink: true } } } },
  },
  plugins: {
    SplashScreen: {
      launchAutoHide: false,          // hidden by the app once the session check has finished
      launchFadeOutDuration: 200,
      backgroundColor: '#FFFFFF',
      androidScaleType: 'CENTER_INSIDE',
      showSpinner: false,
    },
    StatusBar: { overlaysWebView: false },
    Keyboard: { resize: 'body', resizeOnFullScreen: true },
    FirebaseMessaging: { presentationOptions: ['badge', 'sound', 'alert'] },
  },
}

export default config
