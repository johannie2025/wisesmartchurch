<?php
/* Démos animées (CSS pur, en boucle) : régie Preview/Program, multicaméra, montage. */
function ld_txt(string $l): array {
 $t=['fr'=>['k'=>'Voyez l’app en action','h'=>'Trois écrans. <span class="gtxt">Zéro stress.</span>','p'=>'Des démonstrations animées de ce que vous piloterez en direct.','c1'=>'Régie Preview → Program','c1p'=>'Préparez dans Preview, un TAKE et c’est à l’antenne avec le bandeau animé.','c2'=>'Multicaméra & tally','c2p'=>'Basculez entre vos caméras téléphones, webcams et sources réseau.','c3'=>'Studio de montage','c3p'=>'Pistes vidéo, audio et texte, effets et export H.264.','pv'=>'PREVIEW','pg'=>'PROGRAM','take'=>'TAKE','lt'=>['Pasteur Daniel','Message du jour'],'cam'=>'CAM','ex'=>'Exporter MP4 H.264'],
 'es'=>['k'=>'Mira la app en acción','h'=>'Tres pantallas. <span class="gtxt">Cero estrés.</span>','p'=>'Demostraciones animadas de lo que controlarás en directo.','c1'=>'Regiduría Preview → Program','c1p'=>'Prepara en Preview, un TAKE y sale al aire con el rótulo animado.','c2'=>'Multicámara y tally','c2p'=>'Cambia entre cámaras de teléfono, webcams y fuentes de red.','c3'=>'Estudio de montaje','c3p'=>'Pistas de vídeo, audio y texto, efectos y exportación H.264.','pv'=>'PREVIEW','pg'=>'PROGRAM','take'=>'TAKE','lt'=>['Pastor Daniel','Mensaje del día'],'cam'=>'CAM','ex'=>'Exportar MP4 H.264'],
 'en'=>['k'=>'See the app in action','h'=>'Three screens. <span class="gtxt">Zero stress.</span>','p'=>'Animated demos of what you will drive live.','c1'=>'Preview → Program control room','c1p'=>'Prepare in Preview, hit TAKE and it goes on air with the animated lower third.','c2'=>'Multicamera & tally','c2p'=>'Switch between phone cameras, webcams and network sources.','c3'=>'Editing studio','c3p'=>'Video, audio and text tracks, effects and H.264 export.','pv'=>'PREVIEW','pg'=>'PROGRAM','take'=>'TAKE','lt'=>['Pastor Daniel','Message of the day'],'cam'=>'CAM','ex'=>'Export H.264 MP4']];
 return $t[$l]??$t['en'];}
function ld_show(string $lang): void { $D=ld_txt($lang);
 echo '<section class="ld" id="demos"><div class="wrap"><span class="kick">'.h($D['k']).'</span><h2 class="t rv">'.$D['h'].'</h2><p class="sub rv">'.h($D['p']).'</p><div class="ldg">';
 // 1 régie
 echo '<div class="ldc rv"><div class="ldw"><div class="dm1"><div class="sc pv"><b>'.h($D['pv']).'</b><div class="vt">Jean 3:16<small>Car Dieu a tant aimé le monde…</small></div></div><div class="sc pg"><b class="rd">'.h($D['pg']).'</b><div class="vt a">Psaume 23<small>L’Éternel est mon berger</small></div><div class="vt b">Jean 3:16<small>Car Dieu a tant aimé le monde…</small></div><div class="l3"><i></i><span><u>'.h($D['lt'][0]).'</u>'.h($D['lt'][1]).'</span></div></div><button class="tk">'.h($D['take']).'</button></div></div><h3>'.h($D['c1']).'</h3><p>'.h($D['c1p']).'</p></div>';
 // 2 multicam
 echo '<div class="ldc rv"><div class="ldw"><div class="dm2"><div class="big"><b class="rd">PGM</b><span class="g1"></span><span class="g2"></span><span class="g3"></span></div><div class="th">';
 for($i=1;$i<=3;$i++) echo '<div class="t t'.$i.'"><em>'.h($D['cam']).' '.$i.'</em></div>';
 echo '</div></div></div><h3>'.h($D['c2']).'</h3><p>'.h($D['c2p']).'</p></div>';
 // 3 montage
 echo '<div class="ldc rv"><div class="ldw"><div class="dm3"><div class="mon"><span></span></div><div class="tl"><div class="ph"></div><div class="tr"><i style="left:2%;width:34%"></i><i style="left:40%;width:30%" class="c2"></i><i style="left:72%;width:24%"></i></div><div class="tr"><i style="left:8%;width:48%" class="c3"></i><i style="left:60%;width:30%" class="c3"></i></div><div class="tr au"><i style="left:0;width:96%"></i></div></div><button class="ex">⬇ '.h($D['ex']).'</button></div></div><h3>'.h($D['c3']).'</h3><p>'.h($D['c3p']).'</p></div>';
 echo '</div></div></section>';
}
