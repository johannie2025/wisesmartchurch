<?php
/* Landing premium broadcast B2C : bandeau live, chiffres, parcours par profil, CTA final immersif, barre collante. */
function lp_txt(string $lang): array {
    $t = [
    'fr' => ['live'=>'EN DIRECT','tick'=>['Projection biblique en 3 langues','Preview / Program en un clic','Multi-caméras & studio virtuel 3D','Assistant IA pour la régie','Montage vidéo intégré','Creative Market : designs HTML + JSON'],
      'st'=>[['3','langues FR · ES · EN'],['4','plateformes EXE · APK · TV · Web'],['0','abonnement obligatoire pour démarrer'],['1','régie pour toute la production']],
      'w_t'=>'Pour qui ? Choisissez votre parcours','w'=>[['⛪','Églises & ministères','Projetez versets, chants et annonces sans stress, diffusez vos cultes en direct.','Essayer pour mon église'],['📺','Chaînes & web TV','Une vraie régie broadcast : habillage antenne, lower thirds, multicaméra.','Voir la régie broadcast'],['🎬','Créateurs & studios','Montez, animez et exportez en H.264 avec des modèles prêts à l’emploi.','Découvrir le studio'],['🎤','Événements & mariages','Écrans, vidéos, compte à rebours et direct, pilotés depuis un téléphone.','Préparer mon événement']],
      'f_k'=>'Prêt à passer en direct ?','f_h'=>'Donnez à votre production <span class="gtxt">une identité de chaîne TV.</span>','f_p'=>'Téléchargez WDSCS Studio, essayez gratuitement et passez Pro quand vous êtes prêt.','f_1'=>'Télécharger gratuitement','f_2'=>'Voir les tarifs','f_3'=>'Parcourir le Creative Market','trust'=>['✔ Sans carte bancaire','✔ Windows · Android · TV · Web','✔ Support FR / ES / EN'],'bar'=>'Essayez WDSCS Studio gratuitement','barb'=>'Télécharger'],
    'es' => ['live'=>'EN VIVO','tick'=>['Proyección bíblica en 3 idiomas','Preview / Program en un clic','Multicámara y estudio virtual 3D','Asistente IA para la regiduría','Montaje de vídeo integrado','Creative Market: diseños HTML + JSON'],
      'st'=>[['3','idiomas FR · ES · EN'],['4','plataformas EXE · APK · TV · Web'],['0','suscripción obligatoria para empezar'],['1','regiduría para toda la producción']],
      'w_t'=>'¿Para quién? Elige tu camino','w'=>[['⛪','Iglesias y ministerios','Proyecta versículos, cantos y anuncios sin estrés y transmite tus cultos en vivo.','Probar en mi iglesia'],['📺','Canales y web TV','Una regiduría broadcast real: habillage de antena, rótulos, multicámara.','Ver la regiduría broadcast'],['🎬','Creadores y estudios','Monta, anima y exporta en H.264 con plantillas listas.','Descubrir el estudio'],['🎤','Eventos y bodas','Pantallas, vídeos, cuenta atrás y directo, desde un teléfono.','Preparar mi evento']],
      'f_k'=>'¿Listo para el directo?','f_h'=>'Da a tu producción <span class="gtxt">identidad de canal de TV.</span>','f_p'=>'Descarga WDSCS Studio, pruébalo gratis y pasa a Pro cuando quieras.','f_1'=>'Descargar gratis','f_2'=>'Ver precios','f_3'=>'Explorar el Creative Market','trust'=>['✔ Sin tarjeta','✔ Windows · Android · TV · Web','✔ Soporte FR / ES / EN'],'bar'=>'Prueba WDSCS Studio gratis','barb'=>'Descargar'],
    'en' => ['live'=>'LIVE','tick'=>['Bible projection in 3 languages','Preview / Program in one click','Multicamera & 3D virtual studio','AI assistant for the control room','Built-in video editor','Creative Market: HTML + JSON designs'],
      'st'=>[['3','languages FR · ES · EN'],['4','platforms EXE · APK · TV · Web'],['0','subscription required to start'],['1','control room for the whole production']],
      'w_t'=>'Who is it for? Pick your path','w'=>[['⛪','Churches & ministries','Project verses, songs and announcements stress-free and stream your services live.','Try it for my church'],['📺','TV channels & web TV','A real broadcast control room: on-air branding, lower thirds, multicamera.','See the broadcast suite'],['🎬','Creators & studios','Edit, animate and export in H.264 with ready-made templates.','Discover the studio'],['🎤','Events & weddings','Screens, videos, countdowns and live, driven from a phone.','Plan my event']],
      'f_k'=>'Ready to go live?','f_h'=>'Give your production <span class="gtxt">a TV-channel identity.</span>','f_p'=>'Download WDSCS Studio, try it free and go Pro when you are ready.','f_1'=>'Download for free','f_2'=>'See pricing','f_3'=>'Browse the Creative Market','trust'=>['✔ No credit card','✔ Windows · Android · TV · Web','✔ FR / ES / EN support'],'bar'=>'Try WDSCS Studio for free','barb'=>'Download'],
    ];
    return $t[$lang] ?? $t['en'];
}
function lp_top(string $lang): void {
    $P = lp_txt($lang);
    echo '<div class="tickr"><div class="tk-live"><i></i>' . h($P['live']) . '</div><div class="tk-in"><div class="tk-tr">';
    for ($k = 0; $k < 2; $k++) foreach ($P['tick'] as $x) echo '<span>' . h($x) . '</span>';
    echo '</div></div></div>';
    echo '<section class="lp-st"><div class="wrap">'; foreach ($P['st'] as $s) echo '<div class="rv"><b data-n="' . (int)$s[0] . '">' . h($s[0]) . '</b><span>' . h($s[1]) . '</span></div>'; echo '</div></section>';
    $lnk = ['#download', '#broadcast', '#studio', '#download'];
    echo '<section class="lp-who"><div class="wrap"><h2 class="t rv">' . h($P['w_t']) . '</h2><div class="who">';
    foreach ($P['w'] as $i => $w) echo '<a class="wc rv" href="' . $lnk[$i] . '"><span class="ic">' . $w[0] . '</span><h3>' . h($w[1]) . '</h3><p>' . h($w[2]) . '</p><em>' . h($w[3]) . ' →</em></a>';
    echo '</div></div></section>';
}
function lp_bottom(string $lang): void {
    $P = lp_txt($lang);
    echo '<section class="lp-fin"><div class="wrap"><span class="kick">' . h($P['f_k']) . '</span><h2>' . $P['f_h'] . '</h2><p>' . h($P['f_p']) . '</p><div class="cta" style="justify-content:center"><a class="btn gold" href="#download">' . h($P['f_1']) . '</a><a class="btn" href="#pricing">' . h($P['f_2']) . '</a><a class="btn ghost" href="market.php?lang=' . h($lang) . '">' . h($P['f_3']) . '</a></div><div class="trust">';
    foreach ($P['trust'] as $x) echo '<span>' . h($x) . '</span>'; echo '</div></div></section>';
    echo '<div id="stk"><span>' . h($P['bar']) . '</span><a class="btn gold sm" href="#download">' . h($P['barb']) . '</a><button type="button" onclick="this.parentNode.remove()" aria-label="x">✕</button></div>';
    echo '<script>(function(){var s=document.getElementById("stk"),h=document.querySelector(".hero");addEventListener("scroll",function(){if(s)s.classList.toggle("on",scrollY>(h?h.offsetHeight:600))},{passive:true});document.querySelectorAll(".lp-st b").forEach(function(b){var n=+b.dataset.n,t=0;if(!("IntersectionObserver"in window)||n<2)return;var io=new IntersectionObserver(function(e){if(e[0].isIntersecting){io.disconnect();var i=setInterval(function(){t++;b.textContent=Math.min(n,Math.round(n*t/20));if(t>=20)clearInterval(i)},40)}});io.observe(b)})})()</script>';
}
