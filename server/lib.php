<?php
require_once __DIR__ . '/config.php';
date_default_timezone_set('UTC');

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function q(string $sql, array $p = []): PDOStatement { $st = db()->prepare($sql); $st->execute($p); return $st; }
function one(string $sql, array $p = []) { return q($sql, $p)->fetch(); }
function all(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }

function gen_key(): string {
    $a = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $k = 'WDSC';
        for ($g = 0; $g < 3; $g++) { $k .= '-'; for ($i = 0; $i < 4; $i++) $k .= $a[random_int(0, strlen($a) - 1)]; }
    } while (one('SELECT id FROM subscriptions WHERE license_key=?', [$k]));
    return $k;
}
function plan_months(string $code): int {
    $r = one('SELECT months FROM plans WHERE code=?', [$code]);
    return $r ? (int)$r['months'] : 1;
}
function create_subscription(int $uid, string $plan, float $amount, int $maxDev, string $method = '', string $ref = ''): int {
    $m = plan_months($plan);
    $key = gen_key();
    q('INSERT INTO subscriptions (user_id,license_key,plan,starts_at,expires_at,max_devices,amount) VALUES (?,?,?,UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(),INTERVAL ? MONTH),?,?)', [$uid, $key, $plan, $m, $maxDev, $amount]);
    $id = (int)db()->lastInsertId();
    if ($amount > 0) q('INSERT INTO payments (sub_id,amount,method,ref) VALUES (?,?,?,?)', [$id, $amount, $method, $ref]);
    return $id;
}
function extend_subscription(int $sid, string $plan, float $amount, string $method = '', string $ref = ''): void {
    $m = plan_months($plan);
    q("UPDATE subscriptions SET plan=?, status='active', expires_at=DATE_ADD(GREATEST(expires_at,UTC_TIMESTAMP()),INTERVAL ? MONTH), amount=amount+? WHERE id=?", [$plan, $m, $amount, $sid]);
    if ($amount > 0) q('INSERT INTO payments (sub_id,amount,method,ref) VALUES (?,?,?,?)', [$sid, $amount, $method, $ref]);
}

/* --- session admin --- */
function admin_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
        session_start();
    }
}
function require_admin(): void {
    admin_start();
    if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; }
}
function csrf(): string { admin_start(); if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf()) . '">'; }
function csrf_check(): void {
    admin_start();
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? null)) { http_response_code(400); exit('CSRF'); }
}
function page_head(string $title): void {
    echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . h($title) . ' — Wise Design SCS</title><link rel="stylesheet" href="style.css"></head><body>';
    echo '<nav><b>Wise Design SCS</b><a href="index.php">Tableau de bord</a><a href="users.php">Utilisateurs</a><a href="subscriptions.php">Abonnements</a><a href="plans.php">Tarifs</a><a href="ai.php">IA</a><a href="downloads.php">Téléchargements</a><a href="testimonials.php">Témoignages</a><a href="settings.php">Réglages</a><a href="logout.php" style="margin-left:auto">Déconnexion</a></nav><main>';
}
function page_foot(): void { echo '</main></body></html>'; }

/* ===== v2 ===== */
function setting(string $k, $d = '') {
    try { $r = one('SELECT v FROM settings WHERE k=?', [$k]); return $r ? $r['v'] : $d; } catch (Throwable $e) { return $d; }
}
function set_setting(string $k, string $v): void { q('INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)', [$k, $v]); }
function enc(string $plain): string {
    if ($plain === '') return '';
    $key = hash('sha256', AI_KEY_SECRET, true); $iv = random_bytes(12); $tag = '';
    $c = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $c);
}
function dec(?string $b): string {
    if (!$b) return '';
    $raw = base64_decode($b); if (strlen($raw) < 29) return '';
    $key = hash('sha256', AI_KEY_SECRET, true);
    $r = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    return $r === false ? '' : $r;
}
/** Requête HTTP JSON (cURL). Renvoie [code, data|null, rawHeaders]. */
function http_json(string $method, string $url, array $headers = [], $body = null, int $timeout = 60): array {
    $ch = curl_init($url);
    $h = array_merge(['Accept: application/json'], $headers);
    if ($body !== null) { $h[] = 'Content-Type: application/json'; curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); }
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $h, CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => true]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    if ($resp === false) return [0, null, ''];
    return [$code, json_decode(substr($resp, $hs), true), substr($resp, 0, $hs)];
}
function client_ip(): string { return substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45); }
