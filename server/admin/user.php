<?php
require_once __DIR__ . '/../lib.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$u = one('SELECT * FROM users WHERE id=?', [$id]);
if (!$u) { http_response_code(404); exit('Introuvable'); }
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $a = $_POST['a'] ?? '';
    $sid = (int)($_POST['sid'] ?? 0);
    $own = $sid ? one('SELECT id FROM subscriptions WHERE id=? AND user_id=?', [$sid, $id]) : null;
    if ($a === 'new') {
        $plan = $_POST['plan'] ?? 'monthly';
        $price = (float)($_POST['amount'] ?? 0);
        $sid = create_subscription($id, $plan, $price, max(1, (int)($_POST['maxdev'] ?? 3)), $_POST['method'] ?? '', $_POST['ref'] ?? '');
        $msg = 'Abonnement créé — clé : ' . one('SELECT license_key k FROM subscriptions WHERE id=?', [$sid])['k'];
    } elseif ($a === 'extend' && $own) { extend_subscription($sid, $_POST['plan'] ?? 'monthly', (float)($_POST['amount'] ?? 0), $_POST['method'] ?? '', $_POST['ref'] ?? ''); $msg = 'Prolongé'; }
    elseif ($a === 'suspend' && $own) { q("UPDATE subscriptions SET status='suspended' WHERE id=?", [$sid]); $msg = 'Suspendu'; }
    elseif ($a === 'activate' && $own) { q("UPDATE subscriptions SET status='active' WHERE id=?", [$sid]); $msg = 'Réactivé'; }
    elseif ($a === 'cancel' && $own) { q("UPDATE subscriptions SET status='cancelled' WHERE id=?", [$sid]); $msg = 'Annulé'; }
    elseif ($a === 'rekey' && $own) { q('UPDATE subscriptions SET license_key=? WHERE id=?', [gen_key(), $sid]); q('DELETE FROM devices WHERE sub_id=?', [$sid]); $msg = 'Nouvelle clé générée (appareils réinitialisés)'; }
    elseif ($a === 'deldev' && $own) { q('DELETE FROM devices WHERE id=? AND sub_id=?', [(int)$_POST['did'], $sid]); $msg = 'Appareil retiré'; }
    elseif ($a === 'save') { q('UPDATE users SET name=?,email=?,phone=?,church=?,country=?,notes=? WHERE id=?', [trim($_POST['name']), trim($_POST['email']), trim($_POST['phone']), trim($_POST['church']), trim($_POST['country']), $_POST['notes'] ?? '', $id]); $msg = 'Enregistré'; }
    elseif ($a === 'deluser') { q('DELETE FROM users WHERE id=?', [$id]); header('Location: users.php'); exit; }
    $u = one('SELECT * FROM users WHERE id=?', [$id]);
}
$plans = all('SELECT * FROM plans ORDER BY months');
page_head($u['name']);
echo '<h1>' . h($u['name']) . '</h1>' . ($msg ? '<div class="msg">' . h($msg) . '</div>' : '');
echo '<form method="post" class="row">' . csrf_field() . '<input type="hidden" name="a" value="save"><input name="name" value="' . h($u['name']) . '"><input name="email" value="' . h($u['email']) . '" placeholder="Email"><input name="phone" value="' . h($u['phone']) . '" placeholder="Tél."><input name="church" value="' . h($u['church']) . '" placeholder="Église"><input name="country" value="' . h($u['country']) . '" placeholder="Pays"><input name="notes" value="' . h($u['notes']) . '" placeholder="Notes" style="min-width:220px"><button>Enregistrer</button></form>';
$opts = '';
foreach ($plans as $p) $opts .= '<option value="' . h($p['code']) . '" data-p="' . h($p['price']) . '">' . h($p['name']) . ' (' . (int)$p['months'] . ' mois — ' . h($p['price']) . ' ' . CURRENCY . ')</option>';
echo '<h2>Nouvel abonnement</h2><form method="post" class="row">' . csrf_field() . '<input type="hidden" name="a" value="new"><select name="plan" onchange="this.form.amount.value=this.selectedOptions[0].dataset.p">' . $opts . '</select><input name="amount" type="number" step="0.01" value="' . h($plans[0]['price'] ?? 0) . '" style="width:100px"><input name="method" placeholder="Moyen (Mobile Money, virement…)"><input name="ref" placeholder="Référence"><input name="maxdev" type="number" value="3" min="1" style="width:70px" title="Appareils max"><button>Créer + générer la clé</button></form>';
echo '<h2>Abonnements</h2>';
foreach (all('SELECT * FROM subscriptions WHERE user_id=? ORDER BY id DESC', [$id]) as $s) {
    $exp = strtotime($s['expires_at'] . ' UTC') < time();
    echo '<div class="kpi" style="margin-bottom:12px"><div class="row"><code>' . h($s['license_key']) . '</code><span class="badge ' . ($exp ? 'exp' : h($s['status'])) . '">' . ($exp ? 'expiré' : h($s['status'])) . '</span><span>' . h($s['plan']) . ' · jusqu’au <b>' . h(substr($s['expires_at'], 0, 10)) . '</b> · ' . h($s['amount']) . ' ' . CURRENCY . ' · max ' . (int)$s['max_devices'] . ' appareils</span></div>';
    echo '<form method="post" class="row">' . csrf_field() . '<input type="hidden" name="sid" value="' . $s['id'] . '"><input type="hidden" name="a" value="extend"><select name="plan" onchange="this.form.amount.value=this.selectedOptions[0].dataset.p">' . $opts . '</select><input name="amount" type="number" step="0.01" value="' . h($plans[0]['price'] ?? 0) . '" style="width:100px"><input name="method" placeholder="Moyen"><input name="ref" placeholder="Réf."><button>Prolonger / renouveler</button></form>';
    echo '<div class="row">';
    foreach ([['suspend', 'Suspendre', 'sec'], ['activate', 'Réactiver', 'sec'], ['cancel', 'Annuler', 'bad'], ['rekey', 'Régénérer la clé', 'sec']] as [$ac, $lb, $cl])
        echo '<form method="post" class="inline" onsubmit="return confirm(\'Confirmer ?\')">' . csrf_field() . '<input type="hidden" name="sid" value="' . $s['id'] . '"><input type="hidden" name="a" value="' . $ac . '"><button class="' . $cl . '">' . $lb . '</button></form>';
    echo '</div><table><tr><th>Appareil</th><th>Plateforme</th><th>Vu pour la dernière fois</th><th></th></tr>';
    foreach (all('SELECT * FROM devices WHERE sub_id=?', [$s['id']]) as $d)
        echo '<tr><td>' . h(substr($d['device_id'], 0, 14)) . '</td><td>' . h($d['platform']) . '</td><td>' . h($d['last_seen']) . '</td><td><form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="sid" value="' . $s['id'] . '"><input type="hidden" name="a" value="deldev"><input type="hidden" name="did" value="' . $d['id'] . '"><button class="sec">Retirer</button></form></td></tr>';
    $st = one('SELECT COALESCE(SUM(takes),0) t, COALESCE(SUM(mins),0) m FROM usage_log WHERE sub_id=?', [$s['id']]);
    echo '</table><p style="color:var(--mut)">Utilisation totale : ' . $st['t'] . ' TAKE · ' . $st['m'] . ' min en direct</p>';
    echo '<table><tr><th>Date</th><th>Montant</th><th>Moyen</th><th>Réf.</th></tr>';
    foreach (all('SELECT * FROM payments WHERE sub_id=? ORDER BY id DESC', [$s['id']]) as $p) echo '<tr><td>' . h($p['paid_at']) . '</td><td>' . h($p['amount']) . '</td><td>' . h($p['method']) . '</td><td>' . h($p['ref']) . '</td></tr>';
    echo '</table></div>';
}
echo '<form method="post" onsubmit="return confirm(\'Supprimer cet utilisateur et ses abonnements ?\')">' . csrf_field() . '<input type="hidden" name="a" value="deluser"><button class="bad">Supprimer l’utilisateur</button></form>';
page_foot();
