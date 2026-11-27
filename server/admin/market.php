<?php
require_once __DIR__ . '/../lib.php';
require_once __DIR__ . '/../market_lib.php';
require_admin();
ensure_market_tables();
$msg = '';
function detect_size(string $html): array {
    $w = 0; $h = 0;
    if (preg_match('/data-width="(\d{3,5})"/i', $html, $m)) $w = (int)$m[1];
    if (preg_match('/data-height="(\d{3,5})"/i', $html, $m)) $h = (int)$m[1];
    if (!$w && preg_match('/width\s*=\s*(\d{3,5})/i', $html, $m)) $w = (int)$m[1];
    if (!$w && preg_match('/body\s*\{[^}]*?width\s*:\s*(\d{3,5})px[^}]*?height\s*:\s*(\d{3,5})px/is', $html, $m)) { $w = (int)$m[1]; $h = (int)$m[2]; }
    $w = $w ?: 1280; $h = $h ?: (int)round($w * 9 / 16);
    return [$w, $h];
}
function res_label(int $w): string { return $w >= 3000 ? '4K' : ($w >= 1800 ? 'FHD' : 'HD'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $a = $_POST['a'] ?? '';
    try {
        if ($a === 'add') {
            $title = mb_substr(trim($_POST['title'] ?? ''), 0, 160);
            $f = $_FILES['pack'] ?? null;
            if ($title === '' || !$f || $f['error'] !== UPLOAD_ERR_OK) throw new Exception('Titre et fichier (.html, .json ou .zip) requis.');
            if ($f['size'] > 25 * 1024 * 1024) throw new Exception('Fichier trop volumineux (25 Mo max).');
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if ($ext === 'htm') $ext = 'html';
            if (!in_array($ext, ['html', 'json', 'zip'], true)) throw new Exception('Format non pris en charge : .html, .json ou .zip.');
            $cat = array_key_exists($_POST['category'] ?? '', MARKET_CATS) ? $_POST['category'] : 'church';
            $tier = in_array($_POST['tier'] ?? '', ['free', 'pro', 'premium', 'single'], true) ? $_POST['tier'] : 'pro';
            $pu = trim($_POST['pay_url'] ?? ''); if ($pu !== '' && !preg_match('#^https://#', $pu)) $pu = '';
            q('INSERT INTO market_items (title,category,format,tier,price,description,tags,pay_url) VALUES (?,?,?,?,?,?,?,?)', [$title, $cat, $ext, $tier, max(0, (float)($_POST['price'] ?? 0)), mb_substr(trim($_POST['description'] ?? ''), 0, 1500), mb_substr(trim($_POST['tags'] ?? ''), 0, 200), $pu ?: null]);
            $id = (int)db()->lastInsertId(); $dir = market_dir($id);
            try {
                if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new Exception('Dossier files/market non inscriptible.');
                $prev = ''; $w = 1280; $h = 720; $note = '';
                $raw = file_get_contents($f['tmp_name']);
                if ($ext === 'html') {
                    if (stripos($raw, '<') === false) throw new Exception('HTML invalide.');
                    file_put_contents("$dir/index.html", $raw); $prev = 'index.html'; [$w, $h] = detect_size($raw);
                    file_put_contents("$dir/source.txt", 'html');
                } elseif ($ext === 'json') {
                    $j = json_decode($raw, true); if (!is_array($j)) throw new Exception('JSON invalide.');
                    file_put_contents("$dir/source.json", $raw);
                    $pv = market_json_preview($j);
                    if ($pv) { file_put_contents("$dir/index.html", $pv[0]); $prev = 'index.html'; $w = $pv[1]; $h = $pv[2]; }
                    else $note = ' (JSON sans aperçu automatique : ajoutez un aperçu HTML)';
                } else {
                    if (!class_exists('ZipArchive')) throw new Exception('Extension PHP zip absente.');
                    $z = new ZipArchive(); if ($z->open($f['tmp_name']) !== true) throw new Exception('ZIP illisible.');
                    if ($z->numFiles > 300) throw new Exception('ZIP : 300 fichiers max.');
                    $tot = 0; $htmls = [];
                    for ($i = 0; $i < $z->numFiles; $i++) {
                        $st = $z->statIndex($i); $rel = market_safe_rel($st['name']); if ($rel === null) continue;
                        $tot += $st['size']; if ($tot > 80 * 1024 * 1024) throw new Exception('ZIP trop volumineux une fois décompressé.');
                        $dst = "$dir/$rel"; if (!is_dir(dirname($dst))) mkdir(dirname($dst), 0755, true);
                        file_put_contents($dst, $z->getFromIndex($i));
                        if (preg_match('/\.html?$/i', $rel)) $htmls[] = $rel;
                    }
                    copy($f['tmp_name'], "$dir/source.zip"); $z->close();
                    usort($htmls, fn($x, $y) => (strtolower(basename($x)) === 'index.html' ? 0 : 1) <=> (strtolower(basename($y)) === 'index.html' ? 0 : 1) ?: strlen($x) <=> strlen($y));
                    if ($htmls) { $prev = $htmls[0]; [$w, $h] = detect_size(file_get_contents("$dir/$prev")); }
                    else $note = ' (aucun HTML dans le ZIP : pas d\'aperçu)';
                }
                $pf = $_FILES['prev'] ?? null;
                if ($pf && $pf['error'] === UPLOAD_ERR_OK && preg_match('/\.html?$/i', $pf['name']) && $pf['size'] < 5 * 1024 * 1024) {
                    $pr = file_get_contents($pf['tmp_name']); file_put_contents("$dir/preview_custom.html", $pr); $prev = 'preview_custom.html'; [$w, $h] = detect_size($pr);
                }
                q('UPDATE market_items SET preview=?,pw=?,ph=?,resolution=?,source=? WHERE id=?', [$prev, $w, $h, res_label($w), mb_substr($f['name'], 0, 80), $id]);
                $msg = 'Produit créé' . $note . '. Il est actif : vérifiez l\'aperçu sur market.php.';
            } catch (Throwable $e) { market_rm($dir); q('DELETE FROM market_items WHERE id=?', [$id]); throw $e; }
        } elseif ($a === 'upd') {
            $tier = in_array($_POST['tier'] ?? '', ['free', 'pro', 'premium', 'single'], true) ? $_POST['tier'] : 'pro';
            $pu = trim($_POST['pay_url'] ?? ''); if ($pu !== '' && !preg_match('#^https://#', $pu)) $pu = '';
            q('UPDATE market_items SET title=?,category=?,tier=?,price=?,description=?,tags=?,pay_url=? WHERE id=?', [mb_substr(trim($_POST['title']), 0, 160), array_key_exists($_POST['category'], MARKET_CATS) ? $_POST['category'] : 'church', $tier, max(0, (float)$_POST['price']), mb_substr(trim($_POST['description'] ?? ''), 0, 1500), mb_substr(trim($_POST['tags'] ?? ''), 0, 200), $pu ?: null, (int)$_POST['id']]);
            $msg = 'Enregistré.';
        } elseif ($a === 'tog') { q('UPDATE market_items SET active=1-active WHERE id=?', [(int)$_POST['id']]);
        } elseif ($a === 'del') { $id = (int)$_POST['id']; q('DELETE FROM market_items WHERE id=?', [$id]); q('DELETE FROM market_grants WHERE item_id=?', [$id]); q('DELETE FROM market_fav WHERE item_id=?', [$id]); market_rm(market_dir($id)); $msg = 'Supprimé.';
        } elseif ($a === 'grant') {
            $s = one('SELECT id FROM subscriptions WHERE license_key=?', [strtoupper(trim($_POST['license'] ?? ''))]);
            if (!$s) throw new Exception('Clé de licence inconnue.');
            q('INSERT INTO market_grants (item_id,sub_id,amount) VALUES (?,?,?) ON DUPLICATE KEY UPDATE amount=VALUES(amount)', [(int)$_POST['id'], $s['id'], max(0, (float)($_POST['amount'] ?? 0))]);
            $msg = 'Achat individuel accordé à cette licence.';
        }
    } catch (Throwable $e) { $msg = 'Erreur : ' . $e->getMessage(); }
}
page_head('Creative Market');
$k = one('SELECT COUNT(*) n, COALESCE(SUM(downloads),0) d FROM market_items');
$rev = one('SELECT COALESCE(SUM(amount),0) a, COUNT(*) n FROM market_grants');
echo '<h1>Creative Market — compositions HTML / JSON / Hyperframes</h1>' . ($msg ? '<div class="msg">' . h($msg) . '</div>' : '');
echo '<div class="grid"><div class="kpi"><b>' . (int)$k['n'] . '</b><span>Produits</span></div><div class="kpi"><b>' . (int)$k['d'] . '</b><span>Téléchargements</span></div><div class="kpi"><b>' . (int)$rev['n'] . '</b><span>Achats individuels</span></div><div class="kpi"><b>' . number_format((float)$rev['a'], 2) . ' ' . CURRENCY . '</b><span>Ventes de packs</span></div></div>';
echo '<p style="color:var(--mut)">Importez un fichier <b>.html</b> (hyperframes), <b>.json</b> (wdscsDesigns ou composition) ou un <b>.zip</b> (HTML + JSON + images + polices). La fiche, les dimensions et la page de démonstration sont créées automatiquement. Les aperçus s\'exécutent dans un cadre isolé (sandbox, sans accès au site). Droits : <b>free</b> = tous ; <b>pro</b> = abonnement actif ; <b>premium</b> = formules annuelles (réglable via le paramètre <code>market_premium_plans</code>) ; <b>single</b> = achat d\'un pack, accordé ci-dessous à une clé de licence.</p>';
echo '<form method="post" enctype="multipart/form-data" class="card" style="display:grid;gap:8px;max-width:680px"><b>Nouveau produit</b>' . csrf_field() . '<input type="hidden" name="a" value="add">
<input name="title" placeholder="Titre (ex. Pack 9 Lower Thirds)" required><select name="category">';
foreach (MARKET_CATS as $c => $l) echo '<option value="' . $c . '">' . h($l) . '</option>';
echo '</select><select name="tier"><option value="pro">Pro (abonnement actif)</option><option value="free">Gratuit</option><option value="premium">Premium</option><option value="single">Achat individuel</option></select>
<input name="price" type="number" step="0.01" min="0" placeholder="Prix du pack (€) — achat individuel" value="0"><textarea name="description" rows="2" placeholder="Description"></textarea><input name="tags" placeholder="Mots-clés (séparés par des virgules)"><input name="pay_url" placeholder="Lien de paiement https:// du pack (optionnel, sinon formulaire de contact)">
<label>Fichier HTML / JSON / ZIP <input type="file" name="pack" accept=".html,.htm,.json,.zip" required></label><label>Aperçu HTML personnalisé (optionnel) <input type="file" name="prev" accept=".html,.htm"></label><button class="pri">Importer et publier</button></form>';
echo '<h2>Catalogue</h2><table><tr><th>Produit</th><th>Catégorie</th><th>Format</th><th>Droit</th><th>Prix</th><th>Télécharg.</th><th>Actif</th><th>Modifier</th><th></th></tr>';
foreach (all('SELECT * FROM market_items ORDER BY id DESC') as $m) {
    $i = (int)$m['id']; $f = '<input type="hidden" name="csrf" value="' . h(csrf()) . '">';
    $u = ' form="up' . $i . '"';
    echo '<tr><td><div style="display:grid;gap:4px;min-width:220px"><input' . $u . ' name="title" value="' . h($m['title']) . '"><textarea' . $u . ' name="description" rows="2">' . h($m['description']) . '</textarea><input' . $u . ' name="tags" value="' . h($m['tags']) . '" placeholder="tags"><input' . $u . ' name="pay_url" value="' . h($m['pay_url']) . '" placeholder="lien de paiement https://"></div></td>';
    echo '<td><select' . $u . ' name="category">'; foreach (MARKET_CATS as $c => $l) echo '<option value="' . $c . '"' . ($c === $m['category'] ? ' selected' : '') . '>' . h($l) . '</option>'; echo '</select></td><td>' . h(strtoupper($m['format'])) . ' · ' . h($m['resolution']) . '<br><small>' . (int)$m['pw'] . '×' . (int)$m['ph'] . '</small></td>';
    echo '<td><select' . $u . ' name="tier">'; foreach (['free', 'pro', 'premium', 'single'] as $t) echo '<option' . ($t === $m['tier'] ? ' selected' : '') . '>' . $t . '</option>'; echo '</select></td><td><input' . $u . ' name="price" type="number" step="0.01" min="0" style="width:80px" value="' . h($m['price']) . '"></td><td>' . (int)$m['downloads'] . '</td>';
    echo '<td><button form="tg' . $i . '">' . ($m['active'] ? 'Oui' : 'Non') . '</button></td><td><button class="pri"' . $u . '>💾</button>';
    echo '<form method="post" style="display:flex;gap:4px;margin-top:6px">' . $f . '<input type="hidden" name="a" value="grant"><input type="hidden" name="id" value="' . $i . '"><input name="license" placeholder="WDSC-…" style="width:130px"><input name="amount" type="number" step="0.01" placeholder="€" style="width:60px"><button>Accorder</button></form></td>';
    echo '<td><form method="post" id="up' . $i . '">' . $f . '<input type="hidden" name="a" value="upd"><input type="hidden" name="id" value="' . $i . '"></form><form method="post" id="tg' . $i . '">' . $f . '<input type="hidden" name="a" value="tog"><input type="hidden" name="id" value="' . $i . '"></form><form method="post" onsubmit="return confirm(\'Supprimer ce produit et ses fichiers ?\')">' . $f . '<input type="hidden" name="a" value="del"><input type="hidden" name="id" value="' . $i . '"><button>✕</button></form></td></tr>';
}
echo '</table>'; page_foot();
