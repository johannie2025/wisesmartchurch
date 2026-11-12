<?php
require_once __DIR__ . '/../lib.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (trim($_POST['name'] ?? '') !== '') {
        q('INSERT INTO users (name,email,phone,church,country) VALUES (?,?,?,?,?)', [trim($_POST['name']), trim($_POST['email'] ?? ''), trim($_POST['phone'] ?? ''), trim($_POST['church'] ?? ''), trim($_POST['country'] ?? '')]);
        header('Location: user.php?id=' . db()->lastInsertId()); exit;
    }
}
page_head('Utilisateurs');
$s = trim($_GET['s'] ?? '');
$rows = all("SELECT u.*, (SELECT MAX(expires_at) FROM subscriptions WHERE user_id=u.id AND status='active') exp FROM users u WHERE (?='' OR u.name LIKE ? OR u.email LIKE ? OR u.church LIKE ?) ORDER BY u.id DESC LIMIT 200", [$s, "%$s%", "%$s%", "%$s%"]);
echo '<h1>Utilisateurs</h1><form class="row"><input name="s" value="' . h($s) . '" placeholder="Recherche nom / email / église"><button>Chercher</button></form>';
echo '<table><tr><th>Nom</th><th>Email</th><th>Église</th><th>Pays</th><th>Abonnement jusqu’au</th></tr>';
foreach ($rows as $r) {
    $e = $r['exp'] ? substr($r['exp'], 0, 10) : '—';
    $cls = $r['exp'] && strtotime($r['exp'] . ' UTC') > time() ? 'active' : 'exp';
    echo '<tr><td><a href="user.php?id=' . $r['id'] . '">' . h($r['name']) . '</a></td><td>' . h($r['email']) . '</td><td>' . h($r['church']) . '</td><td>' . h($r['country']) . '</td><td><span class="badge ' . $cls . '">' . h($e) . '</span></td></tr>';
}
echo '</table><h2>Nouvel utilisateur</h2><form method="post" class="row">' . csrf_field() . '<input name="name" placeholder="Nom" required><input name="email" placeholder="Email"><input name="phone" placeholder="Téléphone"><input name="church" placeholder="Église"><input name="country" placeholder="Pays"><button>Créer</button></form>';
page_foot();
