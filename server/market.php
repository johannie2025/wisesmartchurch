<?php
/* WDSCS Creative Market — catalogue public + espace licence (téléchargements, achats, favoris). */
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/market_lib.php';
require_once __DIR__ . '/og_lib.php';
$L = ['fr', 'es', 'en'];
$lang = $_GET['lang'] ?? ($_COOKIE['lang'] ?? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr', 0, 2));
if (!in_array($lang, $L, true)) $lang = 'fr';
$X = [
'fr' => ['t' => 'Donnez une identité professionnelle à vos productions.', 's' => 'Des créations animées HTML + JSON prêtes à utiliser pour les chaînes TV, les églises, les événements et les studios. Compatibles Hyperframes.', 'all' => 'Toutes', 'search' => 'Rechercher…', 'fmt' => 'Format', 'res' => 'Résolution', 'acc' => 'Accès', 'free' => 'Gratuit', 'pro' => 'Pro', 'premium' => 'Premium', 'single' => 'Pack', 'dl' => 'Télécharger', 'buy' => 'Acheter', 'lic' => 'Clé de licence (WDSC-…)', 'none' => 'Aucun template pour ce filtre.', 'my' => 'Mon espace', 'mys' => 'Entrez votre clé de licence pour voir vos achats, téléchargements et favoris.', 'go' => 'Ouvrir', 'bought' => 'Mes achats', 'hist' => 'Mes téléchargements', 'favs' => 'Mes favoris', 'empty' => 'Rien pour le moment.', 'badkey' => 'Licence inconnue ou expirée.', 'incl' => 'Inclus dans votre accès', 'locked' => 'Licence requise', 'sep' => 'Vendu séparément', 'name' => 'Votre nom', 'mail' => 'E-mail ou téléphone', 'send' => 'Envoyer la demande', 'ok' => 'Demande envoyée, nous vous recontactons.', 'err' => 'Erreur, réessayez.', 'each' => 'Chaque pack : aperçu animé, fichiers HTML et JSON, documentation et licence d\'utilisation.', 'back' => '← Accueil', 'note' => 'La licence des templates est distincte de celle du logiciel : achetez un pack sans abonnement Pro, ou téléchargez les packs Pro/Premium selon votre formule.', 'play' => '↻ Rejouer', 'cats' => ['tv' => 'TV Graphics', 'news' => 'News & Sport', 'church' => 'Church', 'gospel' => 'Gospel', 'events' => 'Events', 'cinematic' => 'Cinematic', 'social' => 'Social Media']],
'es' => ['t' => 'Dale una identidad profesional a tus producciones.', 's' => 'Creaciones animadas HTML + JSON listas para canales de TV, iglesias, eventos y estudios. Compatibles con Hyperframes.', 'all' => 'Todas', 'search' => 'Buscar…', 'fmt' => 'Formato', 'res' => 'Resolución', 'acc' => 'Acceso', 'free' => 'Gratis', 'pro' => 'Pro', 'premium' => 'Premium', 'single' => 'Pack', 'dl' => 'Descargar', 'buy' => 'Comprar', 'lic' => 'Clave de licencia (WDSC-…)', 'none' => 'Ninguna plantilla con este filtro.', 'my' => 'Mi espacio', 'mys' => 'Introduce tu clave de licencia para ver compras, descargas y favoritos.', 'go' => 'Abrir', 'bought' => 'Mis compras', 'hist' => 'Mis descargas', 'favs' => 'Mis favoritos', 'empty' => 'Nada por ahora.', 'badkey' => 'Licencia desconocida o caducada.', 'incl' => 'Incluido en tu acceso', 'locked' => 'Licencia requerida', 'sep' => 'Se vende aparte', 'name' => 'Tu nombre', 'mail' => 'Correo o teléfono', 'send' => 'Enviar solicitud', 'ok' => 'Solicitud enviada, te contactaremos.', 'err' => 'Error, reintenta.', 'each' => 'Cada pack: vista previa animada, archivos HTML y JSON, documentación y licencia de uso.', 'back' => '← Inicio', 'note' => 'La licencia de plantillas es distinta de la del software: compra un pack sin suscripción Pro o descarga los packs Pro/Premium según tu plan.', 'play' => '↻ Repetir', 'cats' => ['tv' => 'TV Graphics', 'news' => 'News & Sport', 'church' => 'Church', 'gospel' => 'Gospel', 'events' => 'Events', 'cinematic' => 'Cinematic', 'social' => 'Social Media']],
'en' => ['t' => 'Give your productions a professional identity.', 's' => 'Ready-to-use animated HTML + JSON creations for TV channels, churches, events and studios. Hyperframes-compatible.', 'all' => 'All', 'search' => 'Search…', 'fmt' => 'Format', 'res' => 'Resolution', 'acc' => 'Access', 'free' => 'Free', 'pro' => 'Pro', 'premium' => 'Premium', 'single' => 'Pack', 'dl' => 'Download', 'buy' => 'Buy', 'lic' => 'License key (WDSC-…)', 'none' => 'No template for this filter.', 'my' => 'My space', 'mys' => 'Enter your license key to see your purchases, downloads and favourites.', 'go' => 'Open', 'bought' => 'My purchases', 'hist' => 'My downloads', 'favs' => 'My favourites', 'empty' => 'Nothing yet.', 'badkey' => 'Unknown or expired license.', 'incl' => 'Included in your access', 'locked' => 'License required', 'sep' => 'Sold separately', 'name' => 'Your name', 'mail' => 'Email or phone', 'send' => 'Send request', 'ok' => 'Request sent, we will contact you.', 'err' => 'Error, try again.', 'each' => 'Every pack: animated preview, HTML and JSON files, documentation and usage licence.', 'back' => '← Home', 'note' => 'Template licensing is separate from the software licence: buy a pack without a Pro subscription, or download Pro/Premium packs according to your plan.', 'play' => '↻ Replay', 'cats' => ['tv' => 'TV Graphics', 'news' => 'News & Sport', 'church' => 'Church', 'gospel' => 'Gospel', 'events' => 'Events', 'cinematic' => 'Cinematic', 'social' => 'Social Media']],
];
$T = $X[$lang]; $cur = CURRENCY;
try { ensure_market_tables(); } catch (Throwable $e) { http_response_code(500); exit('Service indisponible'); }

/* --- actions JSON : favoris --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['a'] ?? '') === 'fav') {
    header('Content-Type: application/json; charset=utf-8');
    $s = market_sub($_POST['license'] ?? ''); $id = (int)($_POST['id'] ?? 0);
    if (!$s || !one('SELECT id FROM market_items WHERE id=?', [$id])) { echo json_encode(['ok' => false]); exit; }
    if (one('SELECT 1 x FROM market_fav WHERE item_id=? AND sub_id=?', [$id, $s['id']])) { q('DELETE FROM market_fav WHERE item_id=? AND sub_id=?', [$id, $s['id']]); echo json_encode(['ok' => true, 'fav' => false]); }
    else { q('INSERT INTO market_fav (item_id,sub_id) VALUES (?,?)', [$id, $s['id']]); echo json_encode(['ok' => true, 'fav' => true]); }
    exit;
}
$mySub = null; $myErr = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['a'] ?? '') === 'my') { $mySub = market_sub($_POST['license'] ?? ''); $myErr = !$mySub; }

$cat = $_GET['cat'] ?? ''; $fmt = $_GET['fmt'] ?? ''; $res = $_GET['res'] ?? ''; $acc = $_GET['acc'] ?? ''; $qs = trim($_GET['q'] ?? '');
$w = ['active=1']; $p = [];
if (isset(MARKET_CATS[$cat])) { $w[] = 'category=?'; $p[] = $cat; }
if (in_array($fmt, ['html', 'json', 'zip'], true)) { $w[] = 'format=?'; $p[] = $fmt; }
if (in_array($res, ['HD', 'FHD', '4K'], true)) { $w[] = 'resolution=?'; $p[] = $res; }
if (in_array($acc, ['free', 'pro', 'premium', 'single'], true)) { $w[] = 'tier=?'; $p[] = $acc; }
if ($qs !== '') { $w[] = '(title LIKE ? OR tags LIKE ? OR description LIKE ?)'; $l = '%' . str_replace(['%', '_'], ['\%', '\_'], $qs) . '%'; array_push($p, $l, $l, $l); }
$items = all('SELECT * FROM market_items WHERE ' . implode(' AND ', $w) . ' ORDER BY id DESC LIMIT 120', $p);
$qsb = fn(array $o) => '?' . http_build_query(array_filter(array_merge(['lang' => $lang, 'cat' => $cat, 'fmt' => $fmt, 'res' => $res, 'acc' => $acc, 'q' => $qs], $o), fn($v) => $v !== ''));
function m_badge(array $it, array $T, string $cur): string {
    if ($it['tier'] === 'free') return '<span class="mb ok">' . h($T['free']) . '</span>';
    if ($it['tier'] === 'single') return '<span class="mb gold">' . h($T['single']) . ($it['price'] > 0 ? ' · ' . h(rtrim(rtrim(number_format((float)$it['price'], 2, '.', ''), '0'), '.')) . ' ' . h($cur) : '') . '</span>';
    return '<span class="mb">' . h($T[$it['tier']]) . '</span>';
}
function m_card(array $it, array $T, string $cur, ?array $sub, bool $fav = false): string {
    $id = (int)$it['id']; $can = market_can($it, $sub);
    $pv = $it['preview'] ? 'market_preview.php/' . $id . '/' . implode('/', array_map('rawurlencode', explode('/', $it['preview']))) : '';
    $o = '<div class="mc" id="it' . $id . '" data-id="' . $id . '" data-t="' . h($it['title']) . '"><div class="mv" data-w="' . (int)$it['pw'] . '" data-h="' . (int)$it['ph'] . '">' . ($pv ? '<iframe loading="lazy" sandbox="allow-scripts" data-src="' . h($pv) . '" title="' . h($it['title']) . '" tabindex="-1"></iframe><button class="rp" type="button" title="' . h($T['play']) . '">↻</button><button class="xp" type="button" title="⛶">⛶</button>' : '<div class="nv">▶</div>') . '</div>';
    $o .= '<div class="mi"><div class="mt"><b>' . h($it['title']) . '</b><span class="tb"><button class="sh" type="button" title="Share / Partager" aria-label="Share">⤴</button><button class="fv" type="button" title="♥">' . ($fav ? '♥' : '♡') . '</button></span></div><div class="mm">' . m_badge($it, $T, $cur) . '<span class="mb">' . h(strtoupper($it['format'])) . '</span><span class="mb">' . h($it['resolution']) . '</span><span class="mb">' . h(MARKET_CATS[$it['category']] ?? '') . '</span></div>';
    if ($it['description']) $o .= '<p>' . h(mb_strimwidth($it['description'], 0, 150, '…')) . '</p>';
    $o .= '<div class="ma">';
    if ($it['tier'] === 'free' || $can) $o .= ($it['tier'] === 'free' ? '<a class="btn sm" href="market_dl.php?id=' . $id . '">⬇ ' . h($T['dl']) . '</a>' : '<form method="post" action="market_dl.php"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="license" value="' . h($_POST['license'] ?? '') . '"><button class="btn sm">⬇ ' . h($T['dl']) . '</button></form>');
    else {
        $o .= '<form method="post" action="market_dl.php" class="dlf"><input type="hidden" name="id" value="' . $id . '"><input name="license" class="lk" placeholder="' . h($T['lic']) . '" autocomplete="off"><button class="btn sm">⬇ ' . h($T['dl']) . '</button></form>';
        if ($it['tier'] === 'single' || $it['price'] > 0) $o .= '<button type="button" class="btn sm gold buy" data-id="' . $id . '" data-t="' . h($it['title']) . '" data-pay="' . h($it['pay_url'] ?? '') . '">🛒 ' . h($T['buy']) . '</button>';
    }
    return $o . '</div></div></div>';
}
?><!doctype html>
<html lang="<?= h($lang) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php $ogI = null; $sid = (int)($_GET['id'] ?? 0); if ($sid) { try { $ogI = one('SELECT title,description,category,format,resolution FROM market_items WHERE id=? AND active=1', [$sid]); } catch (Throwable $e) {} }
$ogT = $ogI ? $ogI['title'] . ' — WDSCS Creative Market' : 'WDSCS Creative Market'; $ogD = $ogI ? (trim((string)$ogI['description']) ?: ($T['s'])) . ' · ' . strtoupper($ogI['format']) . ' · ' . $ogI['resolution'] : $T['s']; ?>
<title><?= h($ogT) ?></title><meta name="description" content="<?= h($ogD) ?>"><?= og_tags($ogT, $ogD, 'market.php?lang=' . $lang . ($sid ? '&id=' . $sid : '')) ?>
<link rel="icon" href="assets/favicon.png"><link rel="stylesheet" href="assets/site.css">
<style>
.mk-hero{padding:56px 0 24px;text-align:center}.mk-hero small{letter-spacing:.3em;color:var(--ac2);font-weight:800}.mk-hero h1{font-size:clamp(28px,4.5vw,52px);margin:10px auto;max-width:900px}.mk-hero p{max-width:760px;margin:0 auto;color:var(--mut)}
.mf{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin:24px 0}.mf a,.mf select,.mf input{background:var(--card);border:1px solid #1d2a4d;color:var(--tx);border-radius:999px;padding:8px 14px;text-decoration:none;font:inherit;font-size:14px}.mf a.on,.mf a:hover{border-color:var(--ac);background:#14224a}
.mg{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}.mc{background:var(--card);border:1px solid #1d2a4d;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;transition:transform .25s,border-color .25s}.mc:hover{transform:translateY(-4px);border-color:var(--ac)}
.mv{position:relative;width:100%;aspect-ratio:16/9;background:#050913;overflow:hidden}.mv iframe{position:absolute;left:0;top:0;border:0;transform-origin:0 0;pointer-events:none;background:transparent}.nv{display:grid;place-items:center;height:100%;font-size:40px;color:#334}.rp{position:absolute;right:8px;bottom:8px;background:#000a;color:#fff;border:1px solid #fff3;border-radius:8px;padding:2px 8px;cursor:pointer}
.mi{padding:14px 16px;display:grid;gap:8px}.mt{display:flex;justify-content:space-between;gap:8px}.mt b{font-size:16px}.fv{background:none;border:0;color:var(--gold);font-size:20px;cursor:pointer}.mi p{margin:0;color:var(--mut);font-size:14px}
.mm{display:flex;flex-wrap:wrap;gap:6px}.mb{font-size:11px;font-weight:700;letter-spacing:.05em;padding:3px 9px;border-radius:999px;background:#1a2751;color:#bcd0ff}.mb.ok{background:#123a2b;color:#5ee0a8}.mb.gold{background:#3b2f10;color:var(--gold)}
.ma{display:flex;flex-wrap:wrap;gap:8px;align-items:center}.ma form{display:flex;gap:6px;flex:1;min-width:0}.lk{flex:1;min-width:0;background:#0a1124;border:1px solid #1d2a4d;color:var(--tx);border-radius:10px;padding:8px 10px}.btn.sm{padding:8px 14px;font-size:14px}.btn.gold{background:linear-gradient(135deg,#f5b82e,#d98e00);color:#1a1200}
.sp{max-width:760px;margin:30px auto;background:var(--card);border:1px solid #1d2a4d;border-radius:16px;padding:20px}.sp form{display:flex;gap:8px;flex-wrap:wrap}.sp input{flex:1;min-width:200px;background:#0a1124;border:1px solid #1d2a4d;color:var(--tx);border-radius:10px;padding:10px}.sp h3{margin:16px 0 8px}
#bm{position:fixed;inset:0;background:#000b;display:none;place-items:center;z-index:50}#bm.on{display:grid}#bm form{background:var(--card);border:1px solid #1d2a4d;border-radius:16px;padding:22px;width:min(420px,92vw);display:grid;gap:10px}#bm input,#bm textarea{background:#0a1124;border:1px solid #1d2a4d;color:var(--tx);border-radius:10px;padding:10px;font:inherit}.hp{position:absolute;left:-9999px}
</style></head><body>
<header class="top"><div class="wrap"><a class="brand" href="index.php?lang=<?= h($lang) ?>"><img src="assets/logo.png" alt="">WDSCS Studio 3.0</a><nav><a class="h" href="index.php?lang=<?= h($lang) ?>"><?= h($T['back']) ?></a><a class="h" href="#my"><?= h($T['my']) ?></a>
<span class="lang"><?php foreach ($L as $l) echo '<a href="' . h($qsb(['lang' => $l])) . '" class="' . ($l === $lang ? 'on' : '') . '">' . strtoupper($l) . '</a>'; ?></span></nav></div></header>
<main class="wrap">
<div class="mk-hero"><small>MOTION GRAPHICS • HTML • JSON • HYPERFRAMES</small><h1>WDSCS Creative Market</h1><p><b><?= h($T['t']) ?></b><br><?= h($T['s']) ?></p></div>
<form class="mf" method="get"><input type="hidden" name="lang" value="<?= h($lang) ?>">
<a href="<?= h($qsb(['cat' => ''])) ?>" class="<?= $cat === '' ? 'on' : '' ?>"><?= h($T['all']) ?></a><?php foreach ($T['cats'] as $c => $l) echo '<a href="' . h($qsb(['cat' => $c])) . '" class="' . ($cat === $c ? 'on' : '') . '">' . h($l) . '</a>'; ?>
<select name="fmt" onchange="this.form.submit()"><option value=""><?= h($T['fmt']) ?></option><?php foreach (['html', 'json', 'zip'] as $f) echo '<option value="' . $f . '"' . ($fmt === $f ? ' selected' : '') . '>' . strtoupper($f) . '</option>'; ?></select>
<select name="res" onchange="this.form.submit()"><option value=""><?= h($T['res']) ?></option><?php foreach (['HD', 'FHD', '4K'] as $f) echo '<option' . ($res === $f ? ' selected' : '') . '>' . $f . '</option>'; ?></select>
<select name="acc" onchange="this.form.submit()"><option value=""><?= h($T['acc']) ?></option><?php foreach (['free', 'pro', 'premium', 'single'] as $f) echo '<option value="' . $f . '"' . ($acc === $f ? ' selected' : '') . '>' . h($T[$f]) . '</option>'; ?></select>
<input name="q" value="<?= h($qs) ?>" placeholder="<?= h($T['search']) ?>"><input type="hidden" name="cat" value="<?= h($cat) ?>"></form>
<div class="mg"><?php foreach ($items as $it) echo m_card($it, $T, $cur, null) ; if (!$items) echo '<p style="grid-column:1/-1;text-align:center;color:var(--mut)">' . h($T['none']) . '</p>'; ?></div>
<p style="text-align:center;color:var(--mut);margin-top:26px"><?= h($T['each']) ?><br><small><?= h($T['note']) ?></small></p>

<div class="sp" id="my"><h2 style="margin:0 0 6px"><?= h($T['my']) ?></h2><p style="color:var(--mut);margin:0 0 12px"><?= h($T['mys']) ?></p>
<form method="post" action="#my"><input type="hidden" name="a" value="my"><input name="license" class="lk" placeholder="<?= h($T['lic']) ?>" value="<?= h($_POST['license'] ?? '') ?>" autocomplete="off"><button class="btn sm"><?= h($T['go']) ?></button></form>
<?php if ($myErr) echo '<p style="color:#ff6b7d">' . h($T['badkey']) . '</p>';
if ($mySub) {
    $sid = (int)$mySub['id'];
    $sets = ['bought' => all('SELECT i.* FROM market_grants g JOIN market_items i ON i.id=g.item_id WHERE g.sub_id=? AND i.active=1', [$sid]), 'hist' => all('SELECT i.* FROM market_items i WHERE i.active=1 AND i.id IN (SELECT item_id FROM market_log WHERE sub_id=?) ORDER BY i.id DESC', [$sid]), 'favs' => all('SELECT i.* FROM market_fav f JOIN market_items i ON i.id=f.item_id WHERE f.sub_id=? AND i.active=1', [$sid])];
    echo '<p><b>' . h($mySub['uname']) . '</b> · ' . h($mySub['plan']) . ' · ' . h(substr($mySub['expires_at'], 0, 10)) . '</p>';
    foreach ($sets as $k => $rows) { echo '<h3>' . h($T[$k]) . '</h3>'; if (!$rows) echo '<p style="color:var(--mut)">' . h($T['empty']) . '</p>'; else { echo '<div class="mg" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">'; foreach ($rows as $r) echo m_card($r, $T, $cur, $mySub, $k === 'favs'); echo '</div>'; } }
} ?></div>
</main>
<style>
.mt .tb{display:flex;gap:4px}.mt .sh,.mt .fv{background:#10213a;border:1px solid #2a4a7a;color:#fff;border-radius:8px;width:30px;height:30px;cursor:pointer;font-size:15px}.mt .sh:hover,.mt .fv:hover{border-color:#19c8ee}
.mv .xp{position:absolute;right:8px;top:8px;z-index:3;background:rgba(5,11,24,.75);border:1px solid #2a4a7a;color:#fff;border-radius:8px;width:30px;height:30px;cursor:pointer;opacity:.85}.mv .xp:hover{opacity:1;border-color:#19c8ee}
.mv .rp{z-index:3}
#pop{position:fixed;inset:0;z-index:99990;background:rgba(2,6,14,.88);display:none;align-items:center;justify-content:center;padding:3vh 3vw}#pop.on{display:flex}
#pbox{background:#080f20;border:1px solid #2a4a7a;border-radius:16px;width:min(1280px,100%);display:flex;flex-direction:column;overflow:hidden}
#pbar{display:flex;gap:8px;align-items:center;padding:10px 14px;color:#f4f7ff}#pbar b{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#pbar button{background:#10213a;border:1px solid #2a4a7a;color:#fff;border-radius:8px;padding:6px 12px;cursor:pointer}
#pst{position:relative;width:100%;aspect-ratio:16/9;background:#000;overflow:hidden}#pst iframe{position:absolute;border:0;transform-origin:0 0;background:transparent}
#pst:fullscreen{width:100vw;height:100vh;aspect-ratio:auto}
#shm{position:absolute;z-index:99995;background:#0d1b32;border:1px solid #2a4a7a;border-radius:10px;padding:6px;display:none;flex-direction:column;min-width:170px}#shm a,#shm button{display:block;color:#f4f7ff;background:none;border:0;text-align:left;padding:7px 10px;border-radius:6px;cursor:pointer;text-decoration:none;font-size:14px}#shm a:hover,#shm button:hover{background:#12335e}
</style>
<div id="pop"><div id="pbox"><div id="pbar"><b id="ptt"></b><button id="prp" type="button">↻</button><button id="pfs" type="button">⛶ Fullscreen</button><button id="pcl" type="button">✕</button></div><div id="pst"></div></div></div>
<div id="shm"></div>
<script>
(function(){var pop=document.getElementById('pop'),st=document.getElementById('pst'),fr=null,cur=null;
function fitP(){if(!fr||!cur)return;var w=cur.w,h=cur.h,k=Math.min(st.clientWidth/w,st.clientHeight/h);fr.style.width=w+'px';fr.style.height=h+'px';fr.style.transform='scale('+k+')';fr.style.left=(st.clientWidth-w*k)/2+'px';fr.style.top=(st.clientHeight-h*k)/2+'px'}
function openP(c){var v=c.querySelector('.mv'),f=v.querySelector('iframe');if(!f)return;cur={w:+v.dataset.w||1280,h:+v.dataset.h||720,src:f.dataset.src};st.style.aspectRatio=cur.w+'/'+cur.h;st.innerHTML='';fr=document.createElement('iframe');fr.setAttribute('sandbox','allow-scripts');fr.src=cur.src;st.appendChild(fr);document.getElementById('ptt').textContent=c.dataset.t;pop.classList.add('on');setTimeout(fitP,30)}
function closeP(){if(document.fullscreenElement)document.exitFullscreen();pop.classList.remove('on');st.innerHTML='';fr=null}
document.addEventListener('click',function(e){var x=e.target.closest('.xp');if(x){openP(x.closest('.mc'));return}
 if(e.target.id==='pcl'||e.target===pop){closeP();return}
 if(e.target.id==='prp'&&fr){fr.src=cur.src+'?'+Date.now()%1e6;return}
 if(e.target.id==='pfs'){if(document.fullscreenElement)document.exitFullscreen();else if(st.requestFullscreen)st.requestFullscreen();return}
 var sh=e.target.closest('.sh'),m=document.getElementById('shm');
 if(sh){var c=sh.closest('.mc'),u=location.origin+location.pathname+'?lang='+document.documentElement.lang+'&id='+c.dataset.id+'#it'+c.dataset.id,t=c.dataset.t;
  if(navigator.share&&matchMedia('(pointer:coarse)').matches){navigator.share({title:t,url:u}).catch(function(){});return}
  var q=encodeURIComponent(u),tt=encodeURIComponent(t);m.innerHTML='<a target="_blank" rel="noopener" href="https://wa.me/?text='+tt+'%20'+q+'">WhatsApp</a><a target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u='+q+'">Facebook</a><a target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text='+tt+'&url='+q+'">X</a><a href="mailto:?subject='+tt+'&body='+q+'">Email</a><button type="button" data-cp="'+u+'">🔗 Copy link</button>';
  var r=sh.getBoundingClientRect();m.style.display='flex';m.style.top=(scrollY+r.bottom+6)+'px';m.style.left=Math.max(8,Math.min(innerWidth-190,r.right-170))+'px';m.dataset.on=1;e.stopPropagation();return}
 var cp=e.target.closest('[data-cp]');if(cp){var done=function(){cp.textContent='✔ OK'};if(navigator.clipboard)navigator.clipboard.writeText(cp.dataset.cp).then(done,done);else done();return}
 if(m.style.display==='flex')m.style.display='none'},true);
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&pop.classList.contains('on'))closeP()});
addEventListener('resize',fitP);document.addEventListener('fullscreenchange',function(){setTimeout(fitP,60)});
if(location.hash.indexOf('#it')===0){var t=document.querySelector(location.hash);if(t){t.scrollIntoView({block:'center'});t.style.outline='2px solid #19c8ee'}}
})();
</script>
<div id="bm"><form id="bf"><b id="bt"></b><input type="hidden" name="kind" value="lead"><input type="hidden" name="plan" value=""><input type="hidden" name="message"><input class="hp" name="website" tabindex="-1" autocomplete="off"><input name="name" placeholder="<?= h($T['name']) ?>" required maxlength="120"><input name="email" placeholder="<?= h($T['mail']) ?>" required maxlength="160"><button class="btn"><?= h($T['send']) ?></button><small id="bs" style="color:var(--mut)"></small></form></div>
<script>
const LS=(k,v)=>{try{if(v===undefined)return localStorage.getItem(k)||'';localStorage.setItem(k,v)}catch{return''}},ERR=<?= json_encode($T['err']) ?>,OK=<?= json_encode($T['ok']) ?>;
function fit(v){const f=v.querySelector('iframe');if(!f)return;const w=+v.dataset.w||1280,h=+v.dataset.h||720,k=Math.min(v.clientWidth/w,v.clientHeight/h);f.style.width=w+'px';f.style.height=h+'px';f.style.transform='scale('+k+')';f.style.left=((v.clientWidth-w*k)/2)+'px';f.style.top=((v.clientHeight-h*k)/2)+'px'}
const io='IntersectionObserver'in window?new IntersectionObserver(es=>es.forEach(e=>{const f=e.target.querySelector('iframe');if(!f)return;if(e.isIntersecting){if(!f.src)f.src=f.dataset.src;fit(e.target)}}),{rootMargin:'200px'}):null;
document.querySelectorAll('.mv').forEach(v=>{io?io.observe(v):(v.querySelector('iframe')&&(v.querySelector('iframe').src=v.querySelector('iframe').dataset.src));fit(v)});
addEventListener('resize',()=>document.querySelectorAll('.mv').forEach(fit));
document.addEventListener('click',e=>{const r=e.target.closest('.rp');if(r){const f=r.parentNode.querySelector('iframe');f.src=f.dataset.src+'?'+Date.now()%1e6;return}
 const fv=e.target.closest('.fv');if(fv){const k=LS('mkKey');if(!k){alert(<?= json_encode($T['mys']) ?>);location.hash='my';return}const c=fv.closest('.mc'),fd=new FormData();fd.append('a','fav');fd.append('id',c.dataset.id);fd.append('license',k);fetch('market.php',{method:'POST',body:fd}).then(r=>r.json()).then(j=>{if(j.ok)fv.textContent=j.fav?'♥':'♡';else alert(<?= json_encode($T['badkey']) ?>)});return}
 const b=e.target.closest('.buy');if(b){if(b.dataset.pay){open(b.dataset.pay,'_blank','noopener');return}const f=$bf;$bt.textContent=b.dataset.t;f.message.value='[market #'+b.dataset.id+'] '+b.dataset.t;f.plan.value='market';$bm.classList.add('on')}
 if(e.target===$bm)$bm.classList.remove('on')});
const $bf=document.getElementById('bf'),$bt=document.getElementById('bt'),$bm=document.getElementById('bm'),$bs=document.getElementById('bs');
$bf.addEventListener('submit',async e=>{e.preventDefault();try{const r=await fetch('contact.php',{method:'POST',body:new FormData($bf)}),j=await r.json();$bs.textContent=j.ok?OK:ERR;if(j.ok)setTimeout(()=>$bm.classList.remove('on'),1500)}catch{$bs.textContent=ERR}});
document.querySelectorAll('.dlf .lk,#my .lk').forEach(i=>{if(!i.value)i.value=LS('mkKey');i.addEventListener('change',()=>LS('mkKey',i.value.trim()))});
document.querySelectorAll('#my form').forEach(f=>f.addEventListener('submit',()=>LS('mkKey',f.license.value.trim())));
</script></body></html>
