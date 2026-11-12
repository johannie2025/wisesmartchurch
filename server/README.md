# Panel d'abonnements — PHP + MySQL

1. Créez une base MySQL/MariaDB et un utilisateur, puis éditez `config.php`.
2. Envoyez le dossier `server/` sur votre hébergement PHP 8+ (HTTPS obligatoire), par ex. `https://votre-domaine.com/wdscs/`.
3. Ouvrez `https://votre-domaine.com/wdscs/admin/install.php` : créez le compte admin (10+ caractères), puis **supprimez `install.php`**.
4. Connexion : `/wdscs/admin/login.php`. Créez un utilisateur → « Nouvel abonnement » (mensuel / trimestriel / semestriel / annuel) → la clé `WDSC-XXXX-XXXX-XXXX` est générée ; donnez-la au client.
5. Dans l'application (onglet Outils → Licence) : URL de l'API = `https://votre-domaine.com/wdscs/api`, clé d'activation → Activer.
   Vous pouvez aussi remplacer `LIC_SERVER_DEFAULT` dans `web/index.html` par votre URL pour qu'elle soit préremplie.

## Fonctionnement
- `api/activate.php` lie la clé à l'appareil (limite configurable, 3 par défaut) ; `api/verify.php` revérifie toutes les 6 h et reçoit des statistiques d'usage (TAKE, minutes en direct, sessions).
- Sans abonnement valide : mode BASIC (chants, Bible, médias, Preview/Program). Hors ligne, l'abonnement reste valable 7 jours après la dernière vérification réussie.
- Tableau de bord : utilisateurs, abonnements actifs/expirant/expirés, revenus par mois, nouveaux utilisateurs, répartition par formule, utilisation par utilisateur. Fiche utilisateur : abonnements, prolongation, suspension, régénération de clé, appareils, paiements.

## Sécurité
- Requêtes préparées, mots de passe `password_hash`, CSRF, cookies `HttpOnly`/`SameSite`. Servez le tout en HTTPS.
- `.htaccess` (Apache) bloque `config.php`, `lib.php`, `_common.php`, `*.sql`. Sur Nginx, ajoutez des règles équivalentes.
- Le contrôle de licence côté application est un verrou « honnête » (le code web est lisible) : il dissuade l'usage non payé, il ne constitue pas une protection anti-piratage.

## v3 — IA configurable, landing page, téléchargements

- **Site public** : `index.php` (FR/ES/EN, démo animée, captures, témoignages approuvés uniquement, tarifs lus depuis la table `plans`, téléchargements, contact). Formulaires : `contact.php` (honeypot + limitation), `download.php` (jeton 30 min, 3 usages, 10 jetons/h/IP).
- **Admin → IA** : fournisseurs avec endpoints automatiques (Gemini, OpenRouter, Groq, Mistral, Cerebras, SambaNova, GitHub Models) ou personnalisé ; clé chiffrée AES-256-GCM (`AI_KEY_SECRET` dans `config.php`, à changer avant l'installation) ; « Récupérer les modèles » ; priorité/activation par modèle ; rotation + cooldown automatiques (429 → N min, 401/403 → 30 min fournisseur, 400/404 → 60 min). Les régies sous licence active appellent `/api/ai.php`.
- **Admin → Téléchargements** : envoi d'un .exe/.apk/.dmg dans `files/` (protégé par .htaccess, servi uniquement via jeton) ou URL externe (GitHub Releases, TestFlight). Pensez à `upload_max_filesize`/`post_max_size`.
- **Admin → Témoignages / Réglages** : modération, WhatsApp, e-mail, URL de la web-app (iOS), prospects.
- Réinstaller le schéma : relancer `admin/install.php` (CREATE IF NOT EXISTS), puis le supprimer.
