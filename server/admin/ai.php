<?php
require_once __DIR__ . '/../ai_lib.php';
require_admin();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $a = $_POST['a'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    try {
        if ($a === 'settings') {
            set_setting('ai_enabled', isset($_POST['ai_enabled']) ? '1' : '0');
            set_setting('ai_daily_limit', (string)max(0, (int)$_POST['ai_daily_limit']));
            set_setting('ai_cooldown_min', (string)max(1, (int)$_POST['ai_cooldown_min']));
            $msg = 'Réglages enregistrés.';
        } elseif ($a === 'add') {
            $pr = AI_PRESETS[$_POST['preset'] ?? 'custom'] ?? AI_PRESETS['custom'];
            $ep = trim($_POST['endpoint'] ?? '') ?: $pr[2];
            $type = $pr[1];
            if (($_POST['preset'] ?? '') === 'custom' && ($_POST['type'] ?? '') === 'gemini') $type = 'gemini';
            if (!preg_match('#^https://#', $ep)) throw new RuntimeException('Endpoint https requis.');
            q('INSERT INTO ai_providers (name,type,endpoint,api_key,free_only,priority) VALUES (?,?,?,?,?,?)',
              [trim($_POST['name'] ?? '') ?: $pr[0], $type, $ep, enc(trim($_POST['api_key'] ?? '')), $pr[3], 10]);
            $msg = 'Fournisseur ajouté. Cliquez sur « Récupérer les modèles ».';
        } elseif ($a === 'save_p') {
            $k = trim($_POST['api_key'] ?? '');
            q('UPDATE ai_providers SET name=?, endpoint=?, priority=?, enabled=?, free_only=? WHERE id=?',
              [trim($_POST['name']), trim($_POST['endpoint']), (int)$_POST['priority'], isset($_POST['enabled']) ? 1 : 0, isset($_POST['free_only']) ? 1 : 0, $id]);
            if ($k !== '') q('UPDATE ai_providers SET api_key=?, last_error=NULL WHERE id=?', [enc($k), $id]);
            $msg = 'Fournisseur mis à jour.';
        } elseif ($a === 'del_p') {
            q('DELETE FROM ai_models WHERE provider_id=?', [$id]); q('DELETE FROM ai_providers WHERE id=?', [$id]); $msg = 'Supprimé.';
        } elseif ($a === 'fetch') {
            $p = one('SELECT * FROM ai_providers WHERE id=?', [$id]);
            try { $ids = ai_fetch_models($p); $n = ai_store_models($p, $ids); $msg = count($ids) . ' modèles trouvés, ' . $n . ' nouveaux.'; }
            catch (RuntimeException $e) { q('UPDATE ai_providers SET last_error=? WHERE id=?', [substr($e->getMessage(), 0, 250), $id]); $msg = 'Échec : ' . $e->getMessage(); }
        } elseif ($a === 'models') {
            foreach ($_POST['pr'] ?? [] as $mid => $pv) {
                q('UPDATE ai_models SET priority=?, enabled=?, audio=? WHERE id=?', [(int)$pv, isset($_POST['en'][$mid]) ? 1 : 0, isset($_POST['au'][$mid]) ? 1 : 0, (int)$mid]);
            }
            $msg = 'Modèles enregistrés.';
        } elseif ($a === 'reset') {
            q('UPDATE ai_models SET cooldown_until=NULL, fails=0 WHERE provider_id=?', [$id]); $msg = 'Cooldowns remis à zéro.';
        } elseif ($a === 'test') {
            [$ok, $t, $m] = ai_complete('Réponds uniquement par le mot: OK', '', false, null, 0);
            $msg = $ok ? "Test réussi via $m : " . substr($t, 0, 80) : 'Test échoué : ' . $t;
        }
    } catch (Throwable $e) { $msg = 'Erreur : ' . $e->getMessage(); }
}
page_head('IA');
echo '<h1>Configuration IA</h1>';
if ($msg) echo '<div class="msg">' . h($msg) . '</div>';
echo '<p style="color:var(--mut)">Les clés sont chiffrées (AES-256-GCM) et ne quittent jamais le serveur. Les régies ayant une licence active appellent ces modèles via <code>/api/ai.php</code>. ' . ai_models_available() . ' modèle(s) disponible(s) actuellement.</p>';
echo '<h2>Réglages généraux</h2><form method="post" class="row">' . csrf_field() . '<input type="hidden" name="a" value="settings">
<label><input type="checkbox" name="ai_enabled" ' . (setting('ai_enabled', '1') === '1' ? 'checked' : '') . '> IA activée pour les licences</label>
<label>Quota/jour/licence <input type="number" name="ai_daily_limit" value="' . h(setting('ai_daily_limit', '300')) . '" style="width:90px"></label>
<label>Cooldown 429 (min) <input type="number" name="ai_cooldown_min" value="' . h(setting('ai_cooldown_min', '5')) . '" style="width:70px"></label>
<button>Enregistrer</button></form>
<form method="post" class="row">' . csrf_field() . '<input type="hidden" name="a" value="test"><button class="sec">Tester la rotation (appel réel)</button></form>';

echo '<h2>Ajouter un fournisseur</h2><form method="post" class="row">' . csrf_field() . '<input type="hidden" name="a" value="add"><select name="preset">';
foreach (AI_PRESETS as $k => $v) echo '<option value="' . h($k) . '">' . h($v[0]) . '</option>';
echo '</select><input name="name" placeholder="Nom (facultatif)"><input name="endpoint" placeholder="Endpoint (auto si preset)" size="38"><select name="type"><option value="openai">Custom: format OpenAI</option><option value="gemini">Custom: format Gemini</option></select><input name="api_key" placeholder="Clé API" type="password" size="28"><button>Ajouter</button></form>';

foreach (all('SELECT * FROM ai_providers ORDER BY priority,id') as $p) {
    $nm = (int)one('SELECT COUNT(*) c FROM ai_models WHERE provider_id=? AND enabled=1', [$p['id']])['c'];
    echo '<h2>' . h($p['name']) . ' <span class="badge ' . ($p['enabled'] ? 'active' : 'suspended') . '">' . ($p['enabled'] ? 'actif' : 'désactivé') . '</span> <span class="badge">' . h($p['type']) . '</span> <span class="badge">' . ($p['api_key'] ? 'clé OK' : 'sans clé') . '</span></h2>';
    if ($p['last_error']) echo '<div class="msg" style="background:#4d1d25">' . h($p['last_error']) . '</div>';
    echo '<form method="post" class="row">' . csrf_field() . '<input type="hidden" name="a" value="save_p"><input type="hidden" name="id" value="' . (int)$p['id'] . '">
<input name="name" value="' . h($p['name']) . '"><input name="endpoint" value="' . h($p['endpoint']) . '" size="40"><input name="api_key" type="password" placeholder="Nouvelle clé (vide = inchangée)" size="24">
<label>Priorité <input type="number" name="priority" value="' . (int)$p['priority'] . '" style="width:60px"></label>
<label><input type="checkbox" name="enabled" ' . ($p['enabled'] ? 'checked' : '') . '> actif</label><label><input type="checkbox" name="free_only" ' . ($p['free_only'] ? 'checked' : '') . '> gratuits seulement</label><button>OK</button></form>';
    echo '<div class="row"><form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="a" value="fetch"><input type="hidden" name="id" value="' . (int)$p['id'] . '"><button class="sec">Récupérer les modèles</button></form>
<form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="a" value="reset"><input type="hidden" name="id" value="' . (int)$p['id'] . '"><button class="sec">Reset cooldowns</button></form>
<form method="post" class="inline" onsubmit="return confirm(\'Supprimer ?\')">' . csrf_field() . '<input type="hidden" name="a" value="del_p"><input type="hidden" name="id" value="' . (int)$p['id'] . '"><button class="bad">Supprimer</button></form>
<small style="color:var(--mut)">' . $nm . ' modèle(s) actifs' . ($p['models_fetched_at'] ? ' · récupérés le ' . h($p['models_fetched_at']) . ' UTC' : '') . '</small></div>';
    $ms = all('SELECT * FROM ai_models WHERE provider_id=? ORDER BY priority,model', [$p['id']]);
    if ($ms) {
        echo '<form method="post">' . csrf_field() . '<input type="hidden" name="a" value="models"><table><tr><th>Modèle</th><th>Actif</th><th>Audio</th><th>Prio</th><th>OK</th><th>Échecs</th><th>Cooldown</th></tr>';
        foreach ($ms as $m) {
            $cd = $m['cooldown_until'] && strtotime($m['cooldown_until'] . ' UTC') > time();
            echo '<tr><td>' . h($m['model']) . '</td><td><input type="checkbox" name="en[' . (int)$m['id'] . ']" ' . ($m['enabled'] ? 'checked' : '') . '></td><td><input type="checkbox" name="au[' . (int)$m['id'] . ']" ' . ($m['audio'] ? 'checked' : '') . '></td><td><input type="number" name="pr[' . (int)$m['id'] . ']" value="' . (int)$m['priority'] . '" style="width:60px"></td><td>' . (int)$m['ok_count'] . '</td><td>' . (int)$m['fails'] . '</td><td>' . ($cd ? '<span class="badge exp">jusqu\'à ' . h(substr($m['cooldown_until'], 11, 5)) . ' UTC</span>' : '—') . '</td></tr>';
        }
        echo '</table><p><button>Enregistrer les modèles</button></p></form>';
    }
}
echo '<h2>Usage (7 derniers jours)</h2><table><tr><th>Modèle</th><th>Appels</th><th>Échecs</th></tr>';
foreach (all('SELECT model, COUNT(*) n, SUM(ok=0) f FROM ai_log WHERE created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY) GROUP BY model ORDER BY n DESC LIMIT 30') as $r) echo '<tr><td>' . h($r['model']) . '</td><td>' . (int)$r['n'] . '</td><td>' . (int)$r['f'] . '</td></tr>';
echo '</table>';
page_foot();
