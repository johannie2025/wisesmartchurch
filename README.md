# Wise Smart Church v5

Système de projection intelligent — APK Android + EXE Windows

## 🎯 Comportement adaptatif

| Appareil | Mode |
|----------|------|
| 📺 Android TV Box / Smart TV | Kiosk plein écran + boot auto + se publie sur le réseau (mDNS) |
| 📱 Phone / Tablet (régie) | Mode normal + découvre automatiquement les écrans TV sur le réseau |
| 🖥️ PC Windows | Application Electron normale |

## 📡 Découverte des écrans (mDNS)

Les TV Boxes publient leur présence via **NSD / mDNS** (_wsc._tcp) sur le réseau WiFi local.
La régie (phone/PC) les découvre automatiquement — pas besoin de saisir les IPs.

### Depuis wismachv5.html (régie) :
```javascript
// L'app reçoit les écrans automatiquement via callbacks injectés
window.onWscScreenFound = function(screen) {
  console.log('Écran trouvé:', screen.label, screen.host, screen.wsPort);
  // screen = { serviceName, host, wsPort, httpPort, label, role }
};

window.onWscScreenLost = function(serviceName) {
  console.log('Écran déconnecté:', serviceName);
};

// Forcer un refresh de découverte
window.electronBridge.startScreenDiscovery();

// Lire la liste courante
const screens = window.electronBridge.getDiscoveredScreens();

// Savoir si on est sur TV
if (window._wscIsTvMode) { /* mode écran */ }
else { /* mode régie */ }
```

## 🚀 Premier démarrage (local)

```bash
npm install
npx cap add android          # génère le dossier android/ complet
npx cap sync android         # copie wismachv5.html dans l'APK
```

Après `npx cap add android`, remplace les fichiers générés par ceux du repo :
- `android/app/src/main/AndroidManifest.xml`
- `android/app/src/main/java/com/wisesmartchurch/app/MainActivity.java`

Les nouveaux fichiers Java sont déjà au bon chemin dans ce repo.

## 🔒 Activer le Kiosk Lock Task (TV Box — une seule fois)

```bash
adb connect 192.168.1.xxx:5555
adb shell dpm set-device-owner com.wisesmartchurch.app/.BootReceiver
adb shell dpm set-lock-task-features com.wisesmartchurch.app 31
```

> Sur certaines TV Box chinoises, simplement définir l'app comme launcher
> par défaut dans les paramètres système suffit.

## 📦 Build GitHub Actions

| Workflow | Résultat | Déclencheur |
|---------|----------|-------------|
| `build-android.yml` | APK Debug + Release | push `main` ou tag `v*` |
| `build-windows.yml` | EXE + MSI Windows | push `main` ou tag `v*` |

Build manuel : GitHub → **Actions** → **Run workflow**
Téléchargement : GitHub → **Actions** → dernier run → **Artifacts**

## 🔐 Secrets GitHub (APK signé Play Store)

| Secret | Comment |
|--------|---------|
| `ANDROID_KEYSTORE_BASE64` | `base64 -w0 wisesmartchurch.jks` |
| `ANDROID_KEY_ALIAS` | ex: `wisesmartchurch` |
| `ANDROID_KEY_PASSWORD` | mot de passe clé |
| `ANDROID_STORE_PASSWORD` | mot de passe keystore |

```bash
keytool -genkey -v -keystore wisesmartchurch.jks \
  -alias wisesmartchurch -keyalg RSA -keysize 2048 -validity 10000
```

## 📁 Structure du repo

```
wise-smart-church/
├── .github/workflows/
│   ├── build-android.yml
│   └── build-windows.yml
├── electron/
│   ├── main.js              ← WS + HTTP + fenêtre Electron
│   ├── preload.js           ← Bridge IPC
│   └── assets/              ← icon.ico / icon.png / tray.png  ← AJOUTER
├── android/app/src/main/
│   ├── AndroidManifest.xml  ← Permissions + Launcher TV + Boot
│   └── java/com/wisesmartchurch/app/
│       ├── MainActivity.java    ← Adaptatif TV/Phone + NSD
│       ├── WscLanServer.java    ← Serveur WebSocket embarqué
│       ├── WscNsdManager.java   ← Découverte mDNS
│       └── BootReceiver.java    ← Démarrage auto boot
├── capacitor.config.json
├── package.json
└── wismachv5.html           ← App principale
```
