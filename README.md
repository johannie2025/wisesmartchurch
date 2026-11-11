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

## Fonctions clés
- **Appairage auto** : annonce « LOBBY » + mDNS ; code et QR (avec adresse du relais) en secours.
- **Programme** : chants, versets (par référence), médias, lower thirds, sections, durées auto ; fichier de service `.wcs.json` (programme + config écrans + médias).
- **Config par écran** (⚙) : taille/position/couleur du texte, marges, couches 1-3, logo, compte à rebours, « Suivant », notes.
- **Caméras** : mode « Caméra » sur téléphone → WebRTC vers la régie → couches L1/L2/L3 des écrans.
- **Bibles** FR/ES/EN au choix (téléchargement ou fichier), enregistrement WebM du Program.

## À faire ensuite
Son des caméras, multi-stream RTMP, enregistrement MP4, signature release APK, icônes finales.


## Nouveautés
- **Trilingue FR / ES / EN** : sélecteur de langue en haut à droite (mémorisé), tout l'interface est traduit (dictionnaire `I18N` + traducteur DOM automatique). Pour ajouter une chaîne : ajouter la clé française dans `I18N.es` / `I18N.en`.
- **EXE — sorties automatiques** : chaque écran externe branché (HDMI/VGA/DVI/DP) reçoit une fenêtre plein écran, sans configuration (écran 1 = Program, 2 = Stage, 3 = Confidence, suivants = Side). Branchement/débranchement à chaud ; pastille « sorties auto » pour activer/désactiver.
- **Bibles** : import manuel (fichier, glisser-déposer, texte collé), URL, et explorateur de dépôt GitHub (`utilisateur/dépôt` ou lien `blob`). Formats : JSON (tableaux, livre→chapitre→verset, « Livre c:v », scrollmapper), USFX, OSIS.
- **Lower thirds TV** (25 types culte/concert/événement, 8 styles, 8 animations), logo TV animé + badge EN DIRECT + horloge, compte à rebours d'avant-programme, transitions Cut/Fade/Slide/Zoom/Wipe/Blur/Flip/Stinger.
- **APK** : Gradle wrapper 8.7 ajouté (`android/gradlew`), workflow `build-apk.yml` réécrit. Si le build échoue, envoyez le log de l'étape « Compiler l'APK ».
- **Diffusion RTMP multi-flux + MP4** (EXE) : onglet Diffusion, via FFmpeg embarqué (téléchargé par le workflow).

## Mise à jour — espace de travail, remote, caméra, enregistrement
- **Zone centrale** : selon l'onglet, couplets / chapitre biblique entier / programme / lower thirds / tuiles média. Clic = sélectionner, re-clic = désélectionner ; double-clic = afficher en direct.
- **Tuiles média (type vMix)** : ⟳ Loop, 🔊 Audio, couches 1·2·3·4 (re-clic = retirer), ▶/⏸, + Prog.
- **Menu 📁 Fichier** : enregistrer / ouvrir un événement complet (programmes, chants, réglages, médias, Bibles au choix) pour le recharger lors d'un autre culte.
- **Remote** : aperçu Preview/Program avec vidéos, onglets Bible / Médias / Chants (ajout au programme, couches, loop, audio).
- **Android TV / box** : verrou multicast, balayage automatique du réseau, saisie manuelle de l'adresse, boutons grands et navigables à la télécommande.
- **Mode Caméra** : bouton ⏺ pour enregistrer vidéo + son dans la mémoire du téléphone (Films/WiseDesign).
- **Enregistrement Program** : MP4 (FFmpeg) dans l'EXE ; MP4 ou WebM dans le navigateur.
- **macOS** : workflow `build-mac.yml` (dmg). **iPhone/iPad** : ouvrir la version web en HTTPS puis « Ajouter à l'écran d'accueil » (PWA : Remote, Caméra, Écran).
- **Panel PHP/MySQL** : dossier `server/` (voir `server/README.md`).
