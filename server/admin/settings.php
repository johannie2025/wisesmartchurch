<?php
require_once __DIR__ . '/../lib.php';
require_admin();
$keys = ['whatsapp' => 'WhatsApp de contact (international, sans +, ex: 243812345678)', 'contact_email' => 'E-mail de contact', 'web_app_url' => 'URL de la web-app (iPhone/iPad, PWA)', 'download_requires' => 'Téléchargement : "register" (formulaire nom/e-mail) ou "open"'];
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); foreach ($keys as $k => $_) set_setting($k, trim($_POST[$k] ?? '')); $msg = 'Enregistré.'; }
page_head('Réglages');
echo '<h1>Réglages du site</h1>' . ($msg ? '<div class="msg">' . h($msg) . '</div>' : '') . '<form method="post">' . csrf_field();
foreach ($keys as $k => $l) echo '<p><label>' . h($l) . '<br><input name="' . h($k) . '" value="' . h(setting($k, '')) . '" size="60"></label></p>';
echo '<button>Enregistrer</button></form>';
echo '<h2>Prospects (formulaire « Me contacter »)</h2>';
echo '<table><tr><th>Date</th><th>Nom</th><th>Contact</th><th>Formule</th><th>Message</th></tr>';
foreach (all('SELECT * FROM leads ORDER BY id DESC LIMIT 100') as $l) echo '<tr><td>' . h($l['created_at']) . '</td><td>' . h($l['name']) . '</td><td>' . h($l['email'] . ' ' . $l['phone']) . '</td><td>' . h($l['plan']) . '</td><td>' . h($l['message']) . '</td></tr>';
echo '</table>';
page_foot();
