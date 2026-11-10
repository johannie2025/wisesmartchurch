# Wise Church Studio

UI unique (`web/index.html`) réutilisée par :
- **EXE Windows** : Tauri 2 (`src-tauri/`), embarque un relais WebSocket (port 8787) et annonce `_wisechurch._tcp` en mDNS.
- **APK mobile + Android TV** : Java natif (`android/`), WebView plein écran, relais LAN + NSD (mDNS).
- **Web** : ouvrir `web/index.html`.

## Build (GitHub Actions)
- `build-exe.yml` → artefact `WiseChurch-Setup` (.exe NSIS)
- `build-apk.yml` → artefact `WiseChurch-apk` (APK debug, copie `web/index.html` dans les assets)

## Flux de connexion
1. La régie affiche un code à 6 caractères + QR.
2. Les écrans saisissent le code (ou scannent le QR) ; sur le même LAN, ils trouvent le relais via mDNS (`useRelay`).
3. Le rôle de chaque écran (Program / Preview / Stage / Confidence / Side / Stream) se règle depuis la régie.

## À faire ensuite
WebRTC (média), enregistrement MP4, multi-stream RTMP, layers, import chants, Bibles complètes, signature release APK, icônes finales.
