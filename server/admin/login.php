<?php
require_once __DIR__ . '/../lib.php';
admin_start();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['tries'] = ($_SESSION['tries'] ?? 0) + 1;
    if ($_SESSION['tries'] > 8) { sleep(2); }
    $a = one('SELECT * FROM admins WHERE username=?', [trim($_POST['u'] ?? '')]);
    if ($a && password_verify($_POST['p'] ?? '', $a['pass_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = $a['id'];
        header('Location: index.php'); exit;
    }
    $err = 'Identifiants incorrects';
}
?><!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="style.css"><title>Connexion</title>
<div class="login"><h1>Wise Design SCS</h1><?php if ($err) echo '<div class="msg" style="background:#4d1d25">' . h($err) . '</div>'; ?>
<form method="post"><input name="u" placeholder="Identifiant" required autofocus><input name="p" type="password" placeholder="Mot de passe" required><button style="width:100%">Connexion</button></form></div>
