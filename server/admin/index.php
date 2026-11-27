<?php
require_once __DIR__ . '/../lib.php';
require_admin();
page_head('Tableau de bord');
$k = one("SELECT
  (SELECT COUNT(*) FROM users) users,
  (SELECT COUNT(*) FROM subscriptions WHERE status='active' AND expires_at>UTC_TIMESTAMP()) active,
  (SELECT COUNT(*) FROM subscriptions WHERE status='active' AND expires_at BETWEEN UTC_TIMESTAMP() AND DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 DAY)) expiring,
  (SELECT COUNT(*) FROM subscriptions WHERE expires_at<=UTC_TIMESTAMP()) expired,
  (SELECT COALESCE(SUM(amount),0) FROM payments WHERE paid_at>=DATE_FORMAT(UTC_TIMESTAMP(),'%Y-%m-01')) rev_month,
  (SELECT COALESCE(SUM(amount),0) FROM payments) rev_total,
  (SELECT COUNT(*) FROM devices WHERE last_seen>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY)) dev7,
  (SELECT COALESCE(SUM(takes),0) FROM usage_log WHERE created_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 DAY)) takes30");
echo '<h1>Tableau de bord</h1><div class="grid">';
foreach ([['users','Utilisateurs'],['active','Abonnements actifs'],['expiring','Expirent sous 30 j'],['expired','Expirés'],['dev7','Appareils actifs (7 j)'],['takes30','TAKE (30 j)']] as [$f,$l])
    echo '<div class="kpi"><b>' . h($k[$f]) . '</b><span>' . $l . '</span></div>';
echo '<div class="kpi"><b>' . h(number_format($k['rev_month'], 2)) . ' ' . CURRENCY . '</b><span>Revenus ce mois</span></div>';
echo '<div class="kpi"><b>' . h(number_format($k['rev_total'], 2)) . ' ' . CURRENCY . '</b><span>Revenus totaux</span></div></div>';

function bars(array $rows, string $lab, string $val) {
    $max = max(1, ...array_map(fn($r) => (float)$r[$val], $rows ?: [[$val => 1]]));
    echo '<div class="bars">';
    foreach ($rows as $r) echo '<div style="height:' . round(100 * $r[$val] / $max) . '%" title="' . h($r[$lab] . ' : ' . $r[$val]) . '"><small>' . h($r[$lab]) . '</small></div>';
    echo '</div>';
}
echo '<h2>Revenus par mois (12 derniers)</h2>';
bars(all("SELECT DATE_FORMAT(paid_at,'%m/%y') m, ROUND(SUM(amount)) v FROM payments WHERE paid_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(paid_at,'%Y-%m') ORDER BY DATE_FORMAT(paid_at,'%Y-%m')"), 'm', 'v');
echo '<h2>Nouveaux utilisateurs par mois</h2>';
bars(all("SELECT DATE_FORMAT(created_at,'%m/%y') m, COUNT(*) v FROM users WHERE created_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY DATE_FORMAT(created_at,'%Y-%m')"), 'm', 'v');

echo '<h2>Répartition par formule (actifs)</h2><table><tr><th>Formule</th><th>Abonnés</th><th>Revenus cumulés</th></tr>';
foreach (all("SELECT p.name, COUNT(s.id) n, COALESCE(SUM(s.amount),0) a FROM plans p LEFT JOIN subscriptions s ON s.plan=p.code AND s.status='active' AND s.expires_at>UTC_TIMESTAMP() GROUP BY p.code,p.name,p.months ORDER BY p.months") as $r)
    echo '<tr><td>' . h($r['name']) . '</td><td>' . $r['n'] . '</td><td>' . number_format($r['a'], 2) . ' ' . CURRENCY . '</td></tr>';
echo '</table><h2>Utilisation par utilisateur (30 jours)</h2><table><tr><th>Utilisateur</th><th>Église</th><th>Formule</th><th>Expire</th><th>TAKE</th><th>Minutes en direct</th><th>Appareils</th></tr>';
foreach (all("SELECT u.id,u.name,u.church,s.plan,s.expires_at, COALESCE(SUM(l.takes),0) t, COALESCE(SUM(l.mins),0) m, (SELECT COUNT(*) FROM devices d WHERE d.sub_id=s.id) nd
  FROM subscriptions s JOIN users u ON u.id=s.user_id LEFT JOIN usage_log l ON l.sub_id=s.id AND l.created_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 DAY)
  WHERE s.status='active' GROUP BY s.id,u.id,u.name,u.church,s.plan,s.expires_at ORDER BY t DESC LIMIT 25") as $r)
    echo '<tr><td><a href="user.php?id=' . $r['id'] . '">' . h($r['name']) . '</a></td><td>' . h($r['church']) . '</td><td>' . h($r['plan']) . '</td><td>' . h(substr($r['expires_at'], 0, 10)) . '</td><td>' . $r['t'] . '</td><td>' . $r['m'] . '</td><td>' . $r['nd'] . '</td></tr>';
echo '</table>';
page_foot();
