<?php
require_once __DIR__ . '/../lib.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); foreach ($_POST['price'] ?? [] as $c => $v) q('UPDATE plans SET price=?, pay_url=? WHERE code=?', [(float)$v, trim($_POST['pay'][$c] ?? '') ?: null, $c]); }
page_head('Tarifs');
echo '<h1>Tarifs des formules</h1><form method="post">' . csrf_field() . '<table><tr><th>Formule</th><th>Durée</th><th>Prix (' . CURRENCY . ')</th><th>Lien de paiement (optionnel)</th></tr>';
foreach (all('SELECT * FROM plans ORDER BY months') as $p) echo '<tr><td>' . h($p['name']) . '</td><td>' . (int)$p['months'] . ' mois</td><td><input type="number" step="0.01" name="price[' . h($p['code']) . ']" value="' . h($p['price']) . '"></td><td><input name="pay[' . h($p['code']) . ']" value="' . h($p['pay_url']) . '" size="36" placeholder="https://…"></td></tr>';
echo '</table><p><button>Enregistrer</button></p></form>';
page_foot();
