# Nexio CRM — mobile apps (Android + iOS, Capacitor)

The app bundles the built SPA (`dist/`) in a native shell and talks to the production API (`https://crmb.nexiolab.uz`).
Bundle / application id: `crm.nexiolab.uz` (change it before the first store upload in `capacitor.config.ts`,
`android/app/build.gradle` and the Xcode target; reverse-DNS `uz.nexiolab.crm` is the usual convention).

## Daily commands

| Task | Command |
|---|---|
| Rebuild web + copy into both native projects | `npm run cap:sync` |
| Open Android Studio (debug APK: ▶ Run, or `cd android && ./gradlew assembleDebug`) | `npm run cap:android` |
| Open Xcode (▶ Run on a simulator) | `npm run cap:ios` |
| Regenerate icons / splash from `resources/` | `npm run cap:assets` |
| Signed release bundle for Google Play | `cd android && ./gradlew bundleRelease` → `app/build/outputs/bundle/release/app-release.aab` |

`resources/` sources: `icon-only.png` 1024×1024, `icon-foreground.png` 1024×1024 (transparent, logo in the centre 66%),
`icon-background.png` 1024×1024, `splash.png` / `splash-dark.png` 2732×2732 (logo in the centre ~1200 px).
Web-only changes never need a store release if you ship a new `npm run cap:sync` build; native changes (plugins, permissions) do.

## How the app differs from the website
- **Login**: cookies do not work reliably across origins in a WebView, so the app sends `X-Nexio-Client: capacitor`; the API returns the
  session id in the login/register/invite response and the app keeps it in the Keychain/Keystore (`@aparajita/capacitor-secure-storage`)
  and sends it as `X-Nexio-Session`. The website still uses the HttpOnly cookie only. API origins allowed for the app:
  `MOBILE_APP_ORIGINS` (default `capacitor://localhost,https://localhost`).
- **Back button** walks the screen history and minimises the app on a root screen. **Status bar** follows the theme, **safe areas** are handled
  in `src/styles/index.css` (`html.native`), the **keyboard** resizes the body.
- **Offline notice** (Network plugin), **Face ID / fingerprint lock** (Settings → Security), **push notifications**, share-sheet
  **downloads** (CSV export, proposals) and in-app browser for the Telegram link replace behaviour that does not work in a WebView.
  All of it is gated by `isNative()` in `src/lib/native.ts`, so the website is unchanged.

## What you must do yourself
**Google / Firebase (push on both platforms)**
1. Firebase console → create a project → add an Android app `crm.nexiolab.uz` → download `google-services.json` → put it in `android/app/`.
2. Add an iOS app with the same id → download `GoogleService-Info.plist` → drag into Xcode (target *App*, "Copy items").
3. Firebase → Project settings → Cloud Messaging → upload the **APNs Auth Key (.p8)** from your Apple Developer account.
4. Firebase → Project settings → Service accounts → *Generate new private key*. Put the JSON on the server (outside the web root), set
   `FCM_CREDENTIALS_PATH=/path/to/that.json` in `backend/.env`, run `php artisan migrate` (creates `device_tokens`), `php artisan optimize`.
   (These files are git-ignored: `google-services.json`, `GoogleService-Info.plist`, `*.p8`.)

**Android signing (never commit these)**
```
keytool -genkeypair -v -storetype PKCS12 -keystore ~/nexio-release.jks -alias nexio -keyalg RSA -keysize 2048 -validity 10000
```
Create `android/keystore.properties` (git-ignored): `storeFile=/Users/you/nexio-release.jks`, `storePassword=…`, `keyAlias=nexio`, `keyPassword=…`.
Back up the keystore and passwords (losing them blocks updates) or enrol in Play App Signing. Then `./gradlew bundleRelease`.
Raise `versionCode` in `android/app/build.gradle` for every upload.

**iOS (Xcode on a Mac)**: Apple Developer account → Xcode → target *App* → Signing & Capabilities: choose your Team; add **Push Notifications** and
**Background Modes → Remote notifications**; Product → Archive → Distribute App → App Store Connect → TestFlight. iOS uses Swift Package Manager
(no CocoaPods); if `npm run cap:sync` is run on Linux the native step is skipped, run it once on the Mac. Permission texts (camera, photos, Face ID) are
already in `Info.plist` in Russian and Uzbek.

## Store review checklist
- Working native features reviewers can see: push, offline notice, biometric lock, share-sheet downloads, proper back navigation (not a bare website wrapper).
- Prepare: privacy policy URL, support URL, app description (ru/uz/en), screenshots (6.7" and 5.5" iPhone, 12.9" iPad if iPad is supported, Android phone),
  age rating questionnaire, data-safety / privacy-nutrition answers (account data, usage, push token), and a **demo account** with sample data for reviewers
  (put its credentials in App Store Connect "App Review Information" and Play Console "App access").
- Apple 5.1.1(v): if accounts can be created in the app, account deletion must be available in the app.
