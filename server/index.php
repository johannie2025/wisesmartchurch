<?php
/* Landing page B2C — Wise Design Smart Church System */
require_once __DIR__ . '/lib.php';
$L = ['fr', 'es', 'en'];
$lang = $_GET['lang'] ?? ($_COOKIE['lang'] ?? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr', 0, 2));
if (!in_array($lang, $L, true)) $lang = 'fr';
if (isset($_GET['lang'])) setcookie('lang', $lang, time() + 31536000, '/', '', !empty($_SERVER['HTTPS']), true);
$S = [
'fr' => ['nav'=>['Fonctions','Démo','Captures','Tarifs','Télécharger'],'badge'=>'Projection d\'église · Régie · Remote','h1'=>'Toute votre église à l\'écran, sans effort.','lead'=>'Versets, chants, comptes à rebours, lower thirds, médias et diffusion en direct, pilotés depuis la régie, le téléphone ou la voix. Trois langues, plusieurs écrans, un seul outil.','cta1'=>'Télécharger','cta2'=>'Voir les tarifs','by'=>'par Maestro Prophète Josias',
 'f_t'=>'Tout ce qu\'il faut pour un culte réussi','f_s'=>'Une régie complète pensée pour les équipes médias d\'église.',
 'f'=>[['📖','Bible & versets','Chapitres entiers, recherche rapide, import de vos versions de la Bible, affichage instantané.'],['🎵','Chants & paroles','Bibliothèque de chants, saisie instantanée des paroles, recherche par extrait.'],['🎬','Médias & playlist','Vidéo, image, audio : boucle, lecture, tuiles 1-2-3-4, programme du culte.'],['🏷️','Lower thirds & transitions','Plus de 25 bandeaux, 8 transitions, logo, compte à rebours.'],['📱','Remote & caméras téléphone','Pilotez depuis un téléphone, utilisez-le comme caméra, enregistrez en local.'],['📺','Multi-écrans','Écran, TV box ou Smart TV : jumelage automatique, sorties HDMI/VGA/DVI.'],['🔴','Diffusion & enregistrement','Mix programme enregistrable en MP4, flux RTMP.'],['🤖','Assistant IA','Résumé de prédication, texte WhatsApp, images, recherche vocale, pilotage de l\'app.'],['☁️','Sauvegarde Google Drive','Export, import et restauration de vos événements (licence active).']],
 'h_t'=>'Comment ça marche','h'=>[['Installez la régie','Sur Windows (ou macOS) : lancez et c\'est prêt.'],['Jumelez vos écrans','Téléphones, TV box, Smart TV se connectent automatiquement ou par code/QR.'],['Préparez le culte','Programme, versets, chants, médias : enregistrez et rechargez pour un autre événement.'],['Diffusez','Preview, Programme, Take. Pilotez aussi depuis le remote.']],
 'd_t'=>'Un aperçu en mouvement','d_s'=>'Simulation illustrative de l\'écran de projection.','d'=>[['Le culte commence dans','Compte à rebours'],['« Car Dieu a tant aimé le monde… »','Jean 3:16'],['Culte de louange','Pasteur invité'],['Grand est ton nom, Seigneur','Chant']],
 's_t'=>'Captures de l\'application','sh'=>['Régie : programme','Régie : Bible','Régie : médias','Régie : lower thirds','Écran de sortie Programme','Télécommande sur téléphone','Carte WhatsApp générée par l\'IA'],
 'ai_t'=>'Une IA qui travaille pour vous','ai_s'=>'Avec une licence active, la régie accède à des modèles IA configurés côté serveur : vos équipes n\'ont aucune clé à gérer.',
 't_t'=>'Ils en parlent','t_s'=>'Avis publiés après validation.','t_none'=>'Soyez parmi les premiers à partager votre expérience.','t_form'=>'Partager mon témoignage','t_ok'=>'Merci ! Votre témoignage sera publié après validation.',
 'p_t'=>'Tarifs & abonnements','p_s'=>'Basique gratuit. Abonnez-vous pour débloquer toutes les fonctions.','mo'=>'mois','per'=>'/mois','subscribe'=>'Choisir','pop'=>'Populaire',
 'cmp'=>['Fonction','Basique','Pro'],'cmpr'=>['Bible, chants, projection de base','Médias, lower thirds, transitions avancés','Remote, caméras, enregistrement, RTMP','Assistant IA (serveur)','Sauvegarde Google Drive'],
 'dl_t'=>'Téléchargement sécurisé','dl_s'=>'Liens à durée limitée, générés pour vous.','dl_name'=>'Nom','dl_mail'=>'E-mail','dl_lic'=>'Clé de licence','dl_btn'=>'Télécharger','dl_na'=>'Bientôt disponible','dl_note'=>'Le lien expire après 30 minutes.',
 'c_t'=>'Une question ? Un abonnement ?','c_name'=>'Votre nom','c_mail'=>'E-mail','c_phone'=>'Téléphone / WhatsApp','c_msg'=>'Message','c_btn'=>'Envoyer','c_ok'=>'Message envoyé, nous vous recontactons.','err'=>'Erreur, réessayez.','wa'=>'Écrire sur WhatsApp',
 'q_t'=>'Questions fréquentes','q'=>[['Fonctionne-t-il hors ligne ?','Oui pour la projection locale. La licence se vérifie périodiquement et l\'IA/Drive demandent une connexion.'],['Sur quels appareils ?','Windows, Android (téléphone et TV), macOS, et iPhone/iPad via l\'app web.'],['Que se passe-t-il sans licence ?','Les fonctions de base restent disponibles ; les fonctions avancées sont verrouillées.'],['Combien d\'appareils par licence ?','Selon la formule ; la valeur par défaut est de 3 appareils.']]],
'es' => ['nav'=>['Funciones','Demo','Capturas','Precios','Descargar'],'badge'=>'Proyección de iglesia · Control · Remoto','h1'=>'Toda tu iglesia en pantalla, sin esfuerzo.','lead'=>'Versículos, cantos, cuentas regresivas, rótulos, medios y transmisión en vivo, controlados desde la regiduría, el teléfono o la voz. Tres idiomas, varias pantallas, una sola herramienta.','cta1'=>'Descargar','cta2'=>'Ver precios','by'=>'por Maestro Prophète Josias',
 'f_t'=>'Todo lo necesario para un culto exitoso','f_s'=>'Una regiduría completa pensada para equipos de medios de iglesia.',
 'f'=>[['📖','Biblia y versículos','Capítulos completos, búsqueda rápida, importa tus versiones de la Biblia.'],['🎵','Cantos y letras','Biblioteca de cantos, entrada instantánea de letras, búsqueda por fragmento.'],['🎬','Medios y lista','Video, imagen, audio: bucle, reproducción, mosaicos 1-2-3-4, programa del culto.'],['🏷️','Rótulos y transiciones','Más de 25 rótulos, 8 transiciones, logo, cuenta regresiva.'],['📱','Remoto y cámaras de teléfono','Controla desde el teléfono, úsalo como cámara y graba localmente.'],['📺','Multipantalla','Pantalla, TV box o Smart TV: emparejamiento automático, salidas HDMI/VGA/DVI.'],['🔴','Transmisión y grabación','Mezcla de programa grabable en MP4, flujo RTMP.'],['🤖','Asistente IA','Resumen de prédica, texto WhatsApp, imágenes, búsqueda por voz, control de la app.'],['☁️','Respaldo en Google Drive','Exporta, importa y restaura tus eventos (licencia activa).']],
 'h_t'=>'Cómo funciona','h'=>[['Instala la regiduría','En Windows (o macOS): ábrela y listo.'],['Empareja tus pantallas','Teléfonos, TV box y Smart TV se conectan solos o por código/QR.'],['Prepara el culto','Programa, versículos, cantos, medios: guarda y recarga para otro evento.'],['Transmite','Preview, Programa, Take. Controla también desde el remoto.']],
 'd_t'=>'Una vista en movimiento','d_s'=>'Simulación ilustrativa de la pantalla de proyección.','d'=>[['El culto comienza en','Cuenta regresiva'],['«Porque de tal manera amó Dios al mundo…»','Juan 3:16'],['Culto de alabanza','Pastor invitado'],['Grande es tu nombre, Señor','Canto']],
 's_t'=>'Capturas de la aplicación','sh'=>['Regiduría: programa','Regiduría: Biblia','Regiduría: medios','Regiduría: rótulos','Pantalla de salida Programa','Control remoto en teléfono','Tarjeta de WhatsApp generada por la IA'],
 'ai_t'=>'Una IA que trabaja por ti','ai_s'=>'Con una licencia activa, la regiduría accede a modelos de IA configurados en el servidor: sin claves que gestionar.',
 't_t'=>'Opiniones','t_s'=>'Opiniones publicadas tras su validación.','t_none'=>'Sé de los primeros en compartir tu experiencia.','t_form'=>'Compartir mi testimonio','t_ok'=>'¡Gracias! Tu testimonio se publicará tras su validación.',
 'p_t'=>'Precios y suscripciones','p_s'=>'Básico gratis. Suscríbete para desbloquear todas las funciones.','mo'=>'meses','per'=>'/mes','subscribe'=>'Elegir','pop'=>'Popular',
 'cmp'=>['Función','Básico','Pro'],'cmpr'=>['Biblia, cantos, proyección básica','Medios, rótulos, transiciones avanzados','Remoto, cámaras, grabación, RTMP','Asistente IA (servidor)','Respaldo Google Drive'],
 'dl_t'=>'Descarga segura','dl_s'=>'Enlaces de duración limitada, generados para ti.','dl_name'=>'Nombre','dl_mail'=>'Correo','dl_lic'=>'Clave de licencia','dl_btn'=>'Descargar','dl_na'=>'Próximamente','dl_note'=>'El enlace caduca a los 30 minutos.',
 'c_t'=>'¿Una pregunta? ¿Una suscripción?','c_name'=>'Tu nombre','c_mail'=>'Correo','c_phone'=>'Teléfono / WhatsApp','c_msg'=>'Mensaje','c_btn'=>'Enviar','c_ok'=>'Mensaje enviado, te contactaremos.','err'=>'Error, inténtalo de nuevo.','wa'=>'Escribir por WhatsApp',
 'q_t'=>'Preguntas frecuentes','q'=>[['¿Funciona sin conexión?','Sí para la proyección local. La licencia se verifica periódicamente; IA y Drive requieren conexión.'],['¿En qué dispositivos?','Windows, Android (teléfono y TV), macOS e iPhone/iPad mediante la app web.'],['¿Qué pasa sin licencia?','Las funciones básicas siguen disponibles; las avanzadas se bloquean.'],['¿Cuántos dispositivos por licencia?','Según el plan; por defecto 3 dispositivos.']]],
'en' => ['nav'=>['Features','Demo','Screenshots','Pricing','Download'],'badge'=>'Church projection · Control room · Remote','h1'=>'Your whole church on screen, effortlessly.','lead'=>'Verses, songs, countdowns, lower thirds, media and live streaming, driven from the control room, your phone or your voice. Three languages, multiple screens, one tool.','cta1'=>'Download','cta2'=>'See pricing','by'=>'by Maestro Prophète Josias',
 'f_t'=>'Everything for a great service','f_s'=>'A complete control room built for church media teams.',
 'f'=>[['📖','Bible & verses','Whole chapters, quick search, import your own Bible versions.'],['🎵','Songs & lyrics','Song library, instant lyrics entry, search by snippet.'],['🎬','Media & playlist','Video, image, audio: loop, play, 1-2-3-4 tiles, service programme.'],['🏷️','Lower thirds & transitions','25+ lower thirds, 8 transitions, logo, countdown.'],['📱','Remote & phone cameras','Control from a phone, use it as a camera, record locally.'],['📺','Multi-screen','Screen, TV box or Smart TV: automatic pairing, HDMI/VGA/DVI outputs.'],['🔴','Streaming & recording','Programme mix recordable to MP4, RTMP output.'],['🤖','AI assistant','Sermon summary, WhatsApp text, images, voice search, app control.'],['☁️','Google Drive backup','Export, import and restore your events (active licence).']],
 'h_t'=>'How it works','h'=>[['Install the control room','On Windows (or macOS): launch and you\'re ready.'],['Pair your screens','Phones, TV boxes and Smart TVs connect automatically or via code/QR.'],['Prepare the service','Programme, verses, songs, media: save and reload for another event.'],['Go live','Preview, Programme, Take. Drive it from the remote too.']],
 'd_t'=>'A look in motion','d_s'=>'Illustrative simulation of the projection screen.','d'=>[['Service starts in','Countdown'],['"For God so loved the world…"','John 3:16'],['Worship service','Guest pastor'],['Great is Your name, Lord','Song']],
 's_t'=>'App screenshots','sh'=>['Control room: programme','Control room: Bible','Control room: media','Control room: lower thirds','Programme output screen','Phone remote','WhatsApp card generated by AI'],
 'ai_t'=>'An AI that works for you','ai_s'=>'With an active licence the control room uses AI models configured server-side: no keys to manage.',
 't_t'=>'What people say','t_s'=>'Reviews published after approval.','t_none'=>'Be among the first to share your experience.','t_form'=>'Share my testimonial','t_ok'=>'Thank you! Your testimonial will be published once approved.',
 'p_t'=>'Pricing & subscriptions','p_s'=>'Basic is free. Subscribe to unlock every feature.','mo'=>'months','per'=>'/mo','subscribe'=>'Choose','pop'=>'Popular',
 'cmp'=>['Feature','Basic','Pro'],'cmpr'=>['Bible, songs, basic projection','Advanced media, lower thirds, transitions','Remote, cameras, recording, RTMP','AI assistant (server)','Google Drive backup'],
 'dl_t'=>'Secure download','dl_s'=>'Time-limited links generated for you.','dl_name'=>'Name','dl_mail'=>'Email','dl_lic'=>'Licence key','dl_btn'=>'Download','dl_na'=>'Coming soon','dl_note'=>'The link expires after 30 minutes.',
 'c_t'=>'A question? A subscription?','c_name'=>'Your name','c_mail'=>'Email','c_phone'=>'Phone / WhatsApp','c_msg'=>'Message','c_btn'=>'Send','c_ok'=>'Message sent, we\'ll get back to you.','err'=>'Error, please retry.','wa'=>'Chat on WhatsApp',
 'q_t'=>'FAQ','q'=>[['Does it work offline?','Yes for local projection. The licence is checked periodically; AI and Drive need a connection.'],['Which devices?','Windows, Android (phone and TV), macOS, and iPhone/iPad through the web app.'],['What happens without a licence?','Basic features stay available; advanced ones are locked.'],['How many devices per licence?','Depends on the plan; 3 by default.']]],
];
$T = $S[$lang];
$plans = $dls = $quotes = []; $wa = $mail = ''; $req = 'register';
try {
    $plans = all('SELECT * FROM plans WHERE price>0 ORDER BY months');
    $dls = all('SELECT * FROM downloads');
    $quotes = all('SELECT * FROM testimonials WHERE approved=1 ORDER BY id DESC LIMIT 9');
    $wa = preg_replace('/\D/', '', setting('whatsapp', '')); $mail = setting('contact_email', ''); $req = setting('download_requires', 'register');
} catch (Throwable $e) {}
$icons = ['exe' => '🖥️', 'apk' => '🤖', 'mac' => '🍎', 'ios' => '📱'];
$shots = ['regie-programme', 'regie-bible', 'regie-medias', 'regie-lowerthirds', 'ecran-program', 'remote-phone', 'carte-whatsapp'];
$cur = CURRENCY;
?><!doctype html>
<html lang="<?= h($lang) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Wise Design Smart Church System</title><meta name="description" content="<?= h($T['lead']) ?>">
<meta property="og:title" content="Wise Design Smart Church System"><meta property="og:description" content="<?= h($T['h1']) ?>"><meta property="og:image" content="assets/logo.png">
<link rel="icon" href="assets/favicon.png"><link rel="stylesheet" href="assets/site.css"></head><body>
<header class="top"><div class="wrap"><a class="brand" href="#"><img src="assets/logo.png" alt="">Wise Design SCS</a>
<nav><?php foreach (['features', 'demo', 'shots', 'pricing', 'download'] as $k => $id) echo '<a class="h" href="#' . $id . '">' . h($T['nav'][$k]) . '</a>'; ?>
<span class="lang"><?php foreach ($L as $l) echo '<a href="?lang=' . $l . '" class="' . ($l === $lang ? 'on' : '') . '">' . strtoupper($l) . '</a>'; ?></span></nav></div></header>

<div class="hero"><canvas id="fx"></canvas><div class="wrap"><div>
<img class="logo-big" src="assets/logo.png" alt="Wise Design"><div class="badge"><?= h($T['badge']) ?></div>
<h1><?= h($T['h1']) ?></h1><p class="lead"><?= h($T['lead']) ?></p>
<div class="cta"><a class="btn" href="#download"><?= h($T['cta1']) ?></a><a class="btn ghost" href="#pricing"><?= h($T['cta2']) ?></a></div>
<small style="color:var(--mut)">Wise Design — <?= h($T['by']) ?></small></div>
<div class="demo" id="demo"><div class="bar"><i></i><i></i><i></i></div><div class="stage">
<?php foreach ($T['d'] as $k => $d): ?><div class="scene"><?php
 if ($k === 0) echo '<div class="ref">' . h($d[0]) . '</div><div class="big" id="cd">00:10</div>';
 elseif ($k === 1) echo '<div class="txt">' . h($d[0]) . '</div><div class="ref">' . h($d[1]) . '</div>';
 elseif ($k === 2) echo '<div class="lt">' . h($d[0]) . '<small>' . h($d[1]) . '</small></div>';
 else echo '<div class="txt">♪ ' . h($d[0]) . '</div><div class="ref">' . h($d[1]) . '</div>'; ?></div><?php endforeach; ?>
</div><div class="dots"><b></b><b></b><b></b><b></b></div></div><small style="display:block;margin-top:8px;color:var(--mut);text-align:center"><?= h($T['d_s']) ?></small></div></div>

<section id="features" class="alt"><div class="wrap"><h2 class="t rv"><?= h($T['f_t']) ?></h2><p class="sub rv"><?= h($T['f_s']) ?></p>
<div class="grid"><?php foreach ($T['f'] as $f) echo '<div class="card rv"><div class="ic">' . $f[0] . '</div><h3>' . h($f[1]) . '</h3><p>' . h($f[2]) . '</p></div>'; ?></div></div></section>

<section><div class="wrap"><h2 class="t rv"><?= h($T['h_t']) ?></h2><p class="sub"></p>
<div class="steps"><?php foreach ($T['h'] as $s) echo '<div class="card rv"><h3>' . h($s[0]) . '</h3><p>' . h($s[1]) . '</p></div>'; ?></div></div></section>

<section id="demo-note" class="alt"><div class="wrap"><h2 class="t rv"><?= h($T['ai_t']) ?></h2><p class="sub rv"><?= h($T['ai_s']) ?></p>
</div></section>

<section id="shots"><div class="wrap"><h2 class="t rv"><?= h($T['s_t']) ?></h2><p class="sub"></p><div class="shots">
<?php foreach ($shots as $k => $s) if (is_file(__DIR__ . "/assets/shots/$s.jpg")) echo '<figure class="rv"><img loading="lazy" src="assets/shots/' . h($s) . '.jpg" alt="' . h($T['sh'][$k]) . '"><figcaption>' . h($T['sh'][$k]) . '</figcaption></figure>'; ?>
</div></div></section><div id="lb"><img alt=""></div>

<section class="alt"><div class="wrap"><h2 class="t rv"><?= h($T['t_t']) ?></h2><p class="sub rv"><?= h($T['t_s']) ?></p>
<?php if ($quotes): ?><div class="quotes"><?php foreach ($quotes as $q) echo '<div class="quote rv"><p>« ' . h($q['body']) . ' »</p><b>' . h($q['name']) . '</b><small>' . h(trim($q['role'] . ' · ' . $q['church'], ' ·')) . '</small></div>'; ?></div>
<?php else: ?><p class="note"><?= h($T['t_none']) ?></p><?php endif; ?>
<form class="f" data-ajax data-ok="<?= h($T['t_ok']) ?>" data-err="<?= h($T['err']) ?>"><h3 style="text-align:center;margin:10px 0 0"><?= h($T['t_form']) ?></h3>
<input type="hidden" name="kind" value="testimonial"><input class="hp" name="website" tabindex="-1" autocomplete="off">
<input name="name" placeholder="<?= h($T['c_name']) ?>" required maxlength="120"><input name="role" placeholder="Rôle / Role" maxlength="160"><input name="church" placeholder="Église / Iglesia / Church" maxlength="160">
<textarea name="body" rows="3" required minlength="15" maxlength="800" placeholder="…"></textarea><button class="btn"><?= h($T['c_btn']) ?></button></form></div></section>

<section id="pricing"><div class="wrap"><h2 class="t rv"><?= h($T['p_t']) ?></h2><p class="sub rv"><?= h($T['p_s']) ?></p>
<div class="price"><?php foreach ($plans as $i => $p):
  $pm = $p['months'] > 0 ? $p['price'] / $p['months'] : $p['price']; $pop = (int)$p['months'] === 6 || count($plans) === 1;
  $href = $p['pay_url'] ?: '#contact'; ?>
<div class="plan rv <?= $pop ? 'pop' : '' ?>"><?php if ($pop) echo '<span class="tag">' . h($T['pop']) . '</span>'; ?><h3><?= h($p['name']) ?></h3>
<div class="pr"><?= h(rtrim(rtrim(number_format((float)$p['price'], 2, '.', ''), '0'), '.')) ?> <?= h($cur) ?></div>
<div class="pm"><?= (int)$p['months'] ?> <?= h($T['mo']) ?><?php if ($p['months'] > 1) echo ' · ≈ ' . h(number_format($pm, 2)) . ' ' . h($cur) . h($T['per']); ?></div>
<a class="btn" data-plan="<?= h($p['code']) ?>" href="<?= h($href) ?>"<?= $p['pay_url'] ? ' target="_blank" rel="noopener"' : '' ?>><?= h($T['subscribe']) ?></a></div><?php endforeach; ?></div>
<table class="cmp"><tr><th><?= h($T['cmp'][0]) ?></th><th><?= h($T['cmp'][1]) ?></th><th><?= h($T['cmp'][2]) ?></th></tr>
<?php foreach ($T['cmpr'] as $k => $r) echo '<tr><td>' . h($r) . '</td><td>' . ($k === 0 ? '✓' : '—') . '</td><td>✓</td></tr>'; ?></table></div></section>

<section id="download" class="alt"><div class="wrap"><h2 class="t rv"><?= h($T['dl_t']) ?></h2><p class="sub rv"><?= h($T['dl_s']) ?></p>
<div class="dlgrid"><?php foreach ($dls as $d): $ok = $d['filename'] || $d['external_url'] || ($d['platform'] === 'ios' && setting('web_app_url', '')); ?>
<div class="dl rv"><h3><?= $icons[$d['platform']] ?? '⬇️' ?> <?= h($d['label']) ?></h3><small><?= $d['version'] ? 'v' . h($d['version']) : '' ?></small>
<?php if ($ok): ?><form method="post" action="download.php" class="row"><input type="hidden" name="platform" value="<?= h($d['platform']) ?>"><input class="hp" name="website" tabindex="-1" autocomplete="off">
<?php if ($req === 'register') echo '<input name="name" placeholder="' . h($T['dl_name']) . '" required><input name="email" type="email" placeholder="' . h($T['dl_mail']) . '" required>'; ?>
<?php if ($d['need_license']) echo '<input name="license" placeholder="' . h($T['dl_lic']) . '" required>'; ?>
<button class="btn"><?= h($T['dl_btn']) ?></button></form><?php else: ?><p style="color:var(--mut)"><?= h($T['dl_na']) ?></p><?php endif; ?></div><?php endforeach; ?></div>
<p class="note"><?= h($T['dl_note']) ?></p></div></section>

<section id="contact"><div class="wrap"><h2 class="t rv"><?= h($T['c_t']) ?></h2>
<form class="f" data-ajax data-ok="<?= h($T['c_ok']) ?>" data-err="<?= h($T['err']) ?>"><input type="hidden" name="kind" value="lead"><input class="hp" name="website" tabindex="-1" autocomplete="off">
<input name="name" placeholder="<?= h($T['c_name']) ?>" required maxlength="120"><input name="email" type="email" placeholder="<?= h($T['c_mail']) ?>" maxlength="160"><input name="phone" placeholder="<?= h($T['c_phone']) ?>" maxlength="40">
<select name="plan" id="leadplan"><option value="">—</option><?php foreach ($plans as $p) echo '<option value="' . h($p['code']) . '">' . h($p['name']) . '</option>'; ?></select>
<textarea name="message" rows="3" maxlength="800" placeholder="<?= h($T['c_msg']) ?>"></textarea><button class="btn"><?= h($T['c_btn']) ?></button>
<?php if ($wa) echo '<a class="btn ghost" style="text-align:center" target="_blank" rel="noopener" href="https://wa.me/' . h($wa) . '">' . h($T['wa']) . '</a>'; ?></form></div></section>

<section class="alt"><div class="wrap"><h2 class="t rv"><?= h($T['q_t']) ?></h2><p class="sub"></p>
<?php foreach ($T['q'] as $q) echo '<details class="rv"><summary>' . h($q[0]) . '</summary><p>' . h($q[1]) . '</p></details>'; ?></div></section>

<footer><img src="assets/logo.png" alt=""><div>Wise Design — <?= h($T['by']) ?></div><div>© <?= date('Y') ?> Wise Design Smart Church System<?= $mail ? ' · <a href="mailto:' . h($mail) . '">' . h($mail) . '</a>' : '' ?></div></footer>
<div class="toast" id="toast"></div><script src="assets/site.js"></script></body></html>
