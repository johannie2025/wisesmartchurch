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

## Affichage & remote (v3.1)
- Texte auto-ajusté à l'écran (preview/program = rendu réel, même proportion), option **MAJ** (majuscules, remplit l'écran, 1 px de bordure), choix de la **police** (polices installées sur le système dans l'EXE/macOS via la commande Rust `list_fonts`).
- Remote : Précédent/Suivant agissent sur le menu ouvert ; menus plein écran (glisser vers le bas pour fermer) ; 1 appui = preview, 2 = direct, 3 = retirer ; numéros de versets visibles ; import photo/vidéo/audio/chants depuis le téléphone.
- Scanner QR : BarcodeDetector natif sinon jsQR (chargé à la demande) ; caméra : nouvelles tentatives automatiques (NotReadableError).

> IA : aucune clé dans la régie. Les clés sont saisies uniquement dans le panel (Admin → IA) ; la régie appelle `/api/ai.php` avec sa licence active.

## Android TV / anciens WebView
`tools/make-android-html.js` génère `android/app/src/main/assets/index.html` en JS ES2017 (sans `?.`, `catch{}`…) et CSS sans `inset:`, pour les boîtiers TV à WebView ancienne ; le workflow APK l'exécute automatiquement. Le sélecteur de fichiers Android est implémenté (import médias/chants depuis le téléphone). Le scan réseau teste toutes les interfaces (Wi‑Fi + Ethernet).

## Windows : conflit avec Wamp/XAMPP (port 80)
L'EXE charge maintenant l'interface en `https://tauri.localhost` (`useHttpsScheme`) : plus de collision avec un serveur local sur le port 80. Les données locales (localStorage) repartent de zéro au premier lancement après cette mise à jour ; faites une sauvegarde complète avant si besoin.

## Responsive
Régie : sous 900 px (petites fenêtres, téléphone) la barre du bas bascule entre Contenu / Régie / Contrôle ; la barre du haut passe à la ligne ; fenêtre EXE redimensionnable jusqu'à 640×420.

## Port dynamique du relais
Le relais (WebSocket) essaie dans l'ordre : 8787, 8788, 8789, 8790, 8791, 8800, 8880, 9787, 18787, 28787, puis un port libre choisi par l'OS.
Le port retenu est transmis par le QR code, le mDNS, et la connexion manuelle (sans port saisi, l'app teste toute la liste).
