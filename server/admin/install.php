<?php
// Exécutez UNE fois (création des tables + compte admin), puis SUPPRIMEZ ce fichier.
require_once __DIR__ . '/../lib.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_filter(array_map('trim', explode(';', file_get_contents(__DIR__ . '/../schema.sql')))) as $sql) db()->exec($sql);
    $u = trim($_POST['u'] ?? ''); $p = $_POST['p'] ?? '';
    if ($u && strlen($p) >= 10) {
        q('INSERT INTO admins (username,pass_hash) VALUES (?,?) ON DUPLICATE KEY UPDATE pass_hash=VALUES(pass_hash)', [$u, password_hash($p, PASSWORD_DEFAULT)]);
        $msg = 'Installation terminée. SUPPRIMEZ install.php puis connectez-vous.';
    } else $msg = 'Mot de passe : 10 caractères minimum.';
}
?><!doctype html><meta charset="utf-8"><link rel="stylesheet" href="style.css"><div class="login"><h1>Installation</h1>
<?php if ($msg) echo '<div class="msg">' . h($msg) . '</div>'; ?>
<form method="post"><input name="u" placeholder="Identifiant admin" required><input name="p" type="password" placeholder="Mot de passe (10+ car.)" required><button>Installer</button></form></div>
