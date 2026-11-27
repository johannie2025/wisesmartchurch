<?php
/* Formulaires publics : témoignage (modéré) et demande de contact/abonnement. Réponse JSON. */
require_once __DIR__ . '/lib.php';
header('Content-Type: application/json; charset=utf-8');
function jr($ok, $m = '') { echo json_encode(['ok' => $ok, 'msg' => $m]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jr(false);
if (!empty($_POST['website'])) jr(true); // honeypot
$ip = client_ip();
session_start();
$now = time();
if (($_SESSION['last_post'] ?? 0) > $now - 20) jr(false, 'wait');
$_SESSION['last_post'] = $now;
$cut = fn($s, $n) => mb_substr(trim(strip_tags((string)$s)), 0, $n);
try {
    $kind = $_POST['kind'] ?? '';
    if ($kind === 'testimonial') {
        $name = $cut($_POST['name'] ?? '', 120); $body = $cut($_POST['body'] ?? '', 800);
        if ($name === '' || mb_strlen($body) < 15) jr(false, 'invalid');
        q('INSERT INTO testimonials (name,role,church,body,approved) VALUES (?,?,?,?,0)', [$name, $cut($_POST['role'] ?? '', 160), $cut($_POST['church'] ?? '', 160), $body]);
        jr(true);
    }
    if ($kind === 'lead') {
        $name = $cut($_POST['name'] ?? '', 120); $email = $cut($_POST['email'] ?? '', 160); $phone = $cut($_POST['phone'] ?? '', 40);
        if ($name === '' || ($email === '' && $phone === '') || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) jr(false, 'invalid');
        q('INSERT INTO leads (name,email,phone,plan,message) VALUES (?,?,?,?,?)', [$name, $email, $phone, $cut($_POST['plan'] ?? '', 20), $cut($_POST['message'] ?? '', 800)]);
        jr(true);
    }
} catch (Throwable $e) { jr(false, 'error'); }
jr(false);
