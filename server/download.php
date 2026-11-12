<?php
/* Téléchargement sécurisé : formulaire → jeton (30 min, 3 utilisations) → fichier du dossier files/ ou redirection. */
require_once __DIR__ . '/lib.php';
$ip = client_ip();
function dl_fail(string $m, int $c = 400): void { http_response_code($c); header('Content-Type: text/plain; charset=utf-8'); exit($m); }
function dl_target(array $d): ?string {
    $e = trim((string)$d['external_url']);
    if ($e !== '' && preg_match('#^https://#', $e)) return $e;
    if ($d['platform'] === 'ios') { $w = trim((string)setting('web_app_url', '')); if (preg_match('#^https://#', $w)) return $w; }
    return null;
}
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST['website'])) dl_fail('OK'); // honeypot
        $pf = $_POST['platform'] ?? '';
        $d = one('SELECT * FROM downloads WHERE platform=?', [$pf]);
        if (!$d) dl_fail('Plateforme inconnue');
        if ((int)one('SELECT COUNT(*) c FROM download_tokens WHERE ip=? AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR)', [$ip])['c'] >= 10) dl_fail('Trop de demandes, réessayez plus tard.', 429);
        $email = trim($_POST['email'] ?? '');
        if (setting('download_requires', 'register') === 'register') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || trim($_POST['name'] ?? '') === '') dl_fail('Nom et e-mail valides requis.');
            if (!one('SELECT id FROM leads WHERE email=? AND message=?', [$email, '[download]'])) q('INSERT INTO leads (name,email,message) VALUES (?,?,?)', [mb_substr(trim($_POST['name']), 0, 120), $email, '[download]']);
        }
        if ((int)$d['need_license']) {
            $s = one("SELECT id FROM subscriptions WHERE license_key=? AND status='active' AND expires_at>UTC_TIMESTAMP()", [strtoupper(trim($_POST['license'] ?? ''))]);
            if (!$s) dl_fail('Licence active requise pour ce téléchargement.', 403);
        }
        if (!$d['filename'] && !dl_target($d)) dl_fail('Ce fichier n\'est pas encore disponible.', 404);
        $tok = bin2hex(random_bytes(20));
        q('INSERT INTO download_tokens (token,platform,email,expires_at,ip) VALUES (?,?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 MINUTE),?)', [$tok, $pf, $email ?: null, $ip]);
        header('Location: download.php?t=' . $tok); exit;
    }
    $t = preg_replace('/[^a-f0-9]/', '', $_GET['t'] ?? '');
    $r = $t ? one('SELECT * FROM download_tokens WHERE token=? AND expires_at>UTC_TIMESTAMP() AND uses<3', [$t]) : null;
    if (!$r) dl_fail('Lien expiré ou invalide. Redemandez le téléchargement depuis le site.', 403);
    $d = one('SELECT * FROM downloads WHERE platform=?', [$r['platform']]);
    q('UPDATE download_tokens SET uses=uses+1 WHERE token=?', [$t]);
    q('INSERT INTO downloads_log (platform,email,ip) VALUES (?,?,?)', [$r['platform'], $r['email'], $ip]);
    $path = $d['filename'] ? FILES_DIR . '/' . basename($d['filename']) : '';
    if ($path && is_file($path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        while (ob_get_level()) ob_end_clean();
        readfile($path); exit;
    }
    $u = dl_target($d);
    if ($u) { header('Location: ' . $u); exit; }
    dl_fail('Fichier indisponible.', 404);
} catch (Throwable $e) { dl_fail('Erreur serveur.', 500); }
