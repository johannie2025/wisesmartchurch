<?php
require_once __DIR__ . '/../lib.php';
require_once __DIR__ . '/../catalog_lib.php';
require_admin();
ensure_catalog_tables();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['a'] ?? '') === 'add') {
        $v = trim($_POST['version'] ?? '');
        if (!preg_match('/^\d+(\.\d+){0,3}$/', $v)) $msg = 'Version attendue : 1.2.0';
        else { foreach ((array)($_POST['platform'] ?? []) as $pf) q('INSERT INTO releases (platform,version,notes_fr,notes_es,notes_en,mandatory) VALUES (?,?,?,?,?,?)', [$pf, $v, trim($_POST['nf']), trim($_POST['ne']), trim($_POST['nn']), isset($_POST['mandatory']) ? 1 : 0]); $msg = 'Notification publiée aux abonnés.'; }
    } elseif (($_POST['a'] ?? '') === 'del') { q('DELETE FROM releases WHERE id=?', [(int)$_POST['id']]); }
}
page_head('Mises à jour');
echo '<h1>Mises à jour (notification aux abonnés)</h1>' . ($msg ? '<div class="msg">' . h($msg) . '</div>' : '') . '<p style="color:var(--mut)">Publiez une version après avoir envoyé le nouveau fichier dans <a href="downloads.php">Téléchargements</a>. Les apps des abonnés actifs affichent une alerte « Mise à jour disponible » avec les notes dans leur langue.</p>';
echo '<form method="post" class="card" style="display:grid;gap:8px;max-width:640px"><input type="hidden" name="csrf" value="' . h(csrf()) . '"><input type="hidden" name="a" value="add">
<input name="version" placeholder="Version (ex. 1.2.0)" required><label><input type="checkbox" name="platform[]" value="exe" checked> Windows (EXE)</label><label><input type="checkbox" name="platform[]" value="apk" checked> Android (APK)</label><label><input type="checkbox" name="platform[]" value="mac"> macOS</label><label><input type="checkbox" name="platform[]" value="ios"> Web / iOS</label>
<textarea name="nf" placeholder="Nouveautés (français)" rows="3"></textarea><textarea name="ne" placeholder="Novedades (español)" rows="3"></textarea><textarea name="nn" placeholder="What\'s new (English)" rows="3"></textarea><label><input type="checkbox" name="mandatory"> Mise à jour importante</label><button class="pri">Publier</button></form>';
echo '<table><tr><th>Date</th><th>Plateforme</th><th>Version</th><th>Notes (FR)</th><th></th></tr>';
foreach (all('SELECT * FROM releases ORDER BY id DESC LIMIT 60') as $r) echo '<tr><td>' . h($r['created_at']) . '</td><td>' . h($r['platform']) . '</td><td>' . h($r['version']) . ($r['mandatory'] ? ' ⚠' : '') . '</td><td>' . h(mb_substr((string)$r['notes_fr'], 0, 80)) . '</td><td><form method="post" style="display:inline"><input type="hidden" name="csrf" value="' . h(csrf()) . '"><input type="hidden" name="a" value="del"><input type="hidden" name="id" value="' . (int)$r['id'] . '"><button>✕</button></form></td></tr>';
echo '</table>'; page_foot();
