<?php
require_once __DIR__ . '/../lib.php';
require_admin();
page_head('Abonnements');
$f = $_GET['f'] ?? 'all';
$w = ['all' => '1=1', 'active' => "s.status='active' AND s.expires_at>UTC_TIMESTAMP()", 'expiring' => "s.status='active' AND s.expires_at BETWEEN UTC_TIMESTAMP() AND DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 DAY)", 'expired' => 's.expires_at<=UTC_TIMESTAMP()', 'suspended' => "s.status<>'active'"][$f] ?? '1=1';
echo '<h1>Abonnements</h1><div class="row">';
foreach (['all' => 'Tous', 'active' => 'Actifs', 'expiring' => 'Expirent sous 30 j', 'expired' => 'Expirés', 'suspended' => 'Suspendus/annulés'] as $k => $l) echo '<a class="btn' . ($f === $k ? '' : ' sec') . '" style="' . ($f === $k ? '' : 'background:#2b3855') . '" href="?f=' . $k . '">' . $l . '</a>';
echo '</div><table><tr><th>Utilisateur</th><th>Clé</th><th>Formule</th><th>Expire</th><th>Statut</th><th>Appareils</th></tr>';
foreach (all("SELECT s.*,u.name,u.id uid,(SELECT COUNT(*) FROM devices d WHERE d.sub_id=s.id) nd FROM subscriptions s JOIN users u ON u.id=s.user_id WHERE $w ORDER BY s.expires_at DESC LIMIT 300") as $r) {
    $exp = strtotime($r['expires_at'] . ' UTC') < time();
    echo '<tr><td><a href="user.php?id=' . $r['uid'] . '">' . h($r['name']) . '</a></td><td><code>' . h($r['license_key']) . '</code></td><td>' . h($r['plan']) . '</td><td>' . h(substr($r['expires_at'], 0, 10)) . '</td><td><span class="badge ' . ($exp ? 'exp' : h($r['status'])) . '">' . ($exp ? 'expiré' : h($r['status'])) . '</span></td><td>' . $r['nd'] . '/' . $r['max_devices'] . '</td></tr>';
}
echo '</table>';
page_foot();
