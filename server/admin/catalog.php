<?php
require_once __DIR__ . '/../lib.php';
require_once __DIR__ . '/../catalog_lib.php';
require_admin();
ensure_catalog_tables();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $a = $_POST['a'] ?? '';
    if ($a === 'add') {
        $kind = $_POST['kind'] ?? ''; $url = trim($_POST['url'] ?? '');
        if (!in_array($kind, ['bible', 'background', 'studio', 'hymns', 'hyperframe'], true) || !preg_match('#^https://#', $url) || trim($_POST['title'] ?? '') === '') $msg = 'Type, titre et URL https:// requis.';
        else { q('INSERT INTO catalog (kind,title,lang,url,info,sort) VALUES (?,?,?,?,?,?)', [$kind, mb_substr(trim($_POST['title']), 0, 160), substr(trim($_POST['lang'] ?? ''), 0, 5) ?: null, $url, mb_substr(trim($_POST['info'] ?? ''), 0, 255) ?: null, (int)($_POST['sort'] ?? 0)]); $msg = 'Ajouté.'; }
    } elseif ($a === 'del') { q('DELETE FROM catalog WHERE id=?', [(int)$_POST['id']]); $msg = 'Supprimé.'; }
    elseif ($a === 'tog') { q('UPDATE catalog SET active=1-active WHERE id=?', [(int)$_POST['id']]); }
}
page_head('Catalogue');
echo '<h1>Catalogue de contenus</h1>' . ($msg ? '<div class="msg">' . h($msg) . '</div>' : '') . '<p style="color:var(--mut)">Liens que les abonnés peuvent installer depuis l\'app (Outils → Catalogue) : Bibles (JSON/XML/CSV), fonds d\'écran (image/vidéo), studios 3D (.glb/.gltf/.js), cantiques (JSON — voir le modèle <code>samples/chants-exemple.json</code>). Les URL doivent être en https et autoriser le téléchargement direct (CORS).</p>';
echo '<form method="post" class="card" style="display:grid;gap:8px;max-width:640px"><input type="hidden" name="csrf" value="' . h(csrf()) . '"><input type="hidden" name="a" value="add">
<select name="kind"><option value="bible">Bible</option><option value="background">Fond d\'écran</option><option value="studio">Studio virtuel 3D</option><option value="hymns">Cantiques</option><option value="hyperframe">Template hyperframes HTML (.html / .json avec {{champs}})</option></select>
<input name="title" placeholder="Titre (ex. Louis Segond 1910)" required><input name="lang" placeholder="Langue (fr, es, en…)" maxlength="5"><input name="url" placeholder="https://…" required><input name="info" placeholder="Description (optionnel)"><input name="sort" type="number" placeholder="Ordre" value="0"><button class="pri">Ajouter</button></form>';
echo '<table><tr><th>Type</th><th>Titre</th><th>Langue</th><th>URL</th><th>Actif</th><th></th></tr>';
foreach (all('SELECT * FROM catalog ORDER BY kind, sort, title') as $c) {
    echo '<tr><td>' . h($c['kind']) . '</td><td>' . h($c['title']) . '</td><td>' . h($c['lang']) . '</td><td style="max-width:280px;overflow:hidden;text-overflow:ellipsis">' . h($c['url']) . '</td><td><form method="post" style="display:inline"><input type="hidden" name="csrf" value="' . h(csrf()) . '"><input type="hidden" name="a" value="tog"><input type="hidden" name="id" value="' . (int)$c['id'] . '"><button>' . ($c['active'] ? 'Oui' : 'Non') . '</button></form></td><td><form method="post" onsubmit="return confirm(\'Supprimer ?\')" style="display:inline"><input type="hidden" name="csrf" value="' . h(csrf()) . '"><input type="hidden" name="a" value="del"><input type="hidden" name="id" value="' . (int)$c['id'] . '"><button>✕</button></form></td></tr>';
}
echo '</table>'; page_foot();
