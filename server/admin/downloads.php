<?php
require_once __DIR__ . '/../lib.php';
require_admin();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $pf = $_POST['platform'] ?? '';
    if (one('SELECT platform FROM downloads WHERE platform=?', [$pf])) {
        q('UPDATE downloads SET label=?, version=?, external_url=?, need_license=?, updated_at=UTC_TIMESTAMP() WHERE platform=?',
          [trim($_POST['label']), trim($_POST['version']), trim($_POST['external_url']) ?: null, isset($_POST['need_license']) ? 1 : 0, $pf]);
        if (!empty($_FILES['file']['tmp_name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $fn = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['file']['name']));
            if (!preg_match('/\.(exe|msi|apk|dmg|pkg|zip)$/i', $fn)) $msg = 'Extension refusée.';
            else {
                if (!is_dir(FILES_DIR)) mkdir(FILES_DIR, 0750, true);
                $old = one('SELECT filename FROM downloads WHERE platform=?', [$pf]);
                if (move_uploaded_file($_FILES['file']['tmp_name'], FILES_DIR . '/' . $fn)) {
                    q('UPDATE downloads SET filename=? WHERE platform=?', [$fn, $pf]);
                    if ($old && $old['filename'] && $old['filename'] !== $fn) @unlink(FILES_DIR . '/' . basename($old['filename']));
                    $msg = 'Fichier envoyé.';
                } else $msg = 'Échec d\'envoi (taille max PHP ? upload_max_filesize / post_max_size). Sinon utilisez une URL externe (GitHub Releases).';
            }
        } else $msg = $msg ?: 'Enregistré.';
    }
}
page_head('Téléchargements');
echo '<h1>Téléchargements</h1>' . ($msg ? '<div class="msg">' . h($msg) . '</div>' : '') . '<p style="color:var(--mut)">Fichier envoyé ici (dossier <code>files/</code>, protégé, servi via jeton à durée limitée) <b>ou</b> URL externe (GitHub Releases, TestFlight, App Store…). Limite PHP actuelle : ' . h(ini_get('upload_max_filesize')) . '.</p>';
foreach (all('SELECT * FROM downloads') as $d) {
    $n = (int)one('SELECT COUNT(*) c FROM downloads_log WHERE platform=?', [$d['platform']])['c'];
    $sz = $d['filename'] && is_file(FILES_DIR . '/' . $d['filename']) ? round(filesize(FILES_DIR . '/' . $d['filename']) / 1048576, 1) . ' Mo' : '—';
    echo '<h2>' . h($d['platform']) . '</h2><form method="post" enctype="multipart/form-data" class="row">' . csrf_field() . '<input type="hidden" name="platform" value="' . h($d['platform']) . '">
<input name="label" value="' . h($d['label']) . '"><input name="version" placeholder="Version" value="' . h($d['version']) . '" style="width:90px"><input name="external_url" placeholder="URL externe (optionnel)" value="' . h($d['external_url']) . '" size="36">
<input type="file" name="file"><label><input type="checkbox" name="need_license" ' . ($d['need_license'] ? 'checked' : '') . '> licence requise</label><button>Enregistrer</button></form>
<small style="color:var(--mut)">Fichier : ' . h($d['filename'] ?: '—') . ' (' . $sz . ') · ' . $n . ' téléchargement(s)</small>';
}
echo '<h2>Derniers téléchargements</h2><table><tr><th>Date</th><th>Plateforme</th><th>E-mail</th><th>IP</th></tr>';
foreach (all('SELECT * FROM downloads_log ORDER BY id DESC LIMIT 50') as $l) echo '<tr><td>' . h($l['created_at']) . '</td><td>' . h($l['platform']) . '</td><td>' . h($l['email']) . '</td><td>' . h($l['ip']) . '</td></tr>';
echo '</table>';
page_foot();
