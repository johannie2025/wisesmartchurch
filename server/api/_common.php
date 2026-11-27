<?php
require_once __DIR__ . '/../lib.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
function out(array $a) { echo json_encode($a); exit; }
function input(): array { $j = json_decode(file_get_contents('php://input'), true); return is_array($j) ? $j : []; }

/** Valide la clé + enregistre/vérifie l'appareil. Renvoie la ligne d'abonnement (+ user). */
function check_license(array $in, bool $register) {
    $key = strtoupper(trim($in['key'] ?? ''));
    $dev = substr(preg_replace('/[^A-Za-z0-9_-]/', '', $in['device'] ?? ''), 0, 60);
    if (!$key || !$dev) out(['ok' => false, 'error' => 'bad_request']);
    $s = one('SELECT s.*, u.name AS uname FROM subscriptions s JOIN users u ON u.id=s.user_id WHERE s.license_key=?', [$key]);
    if (!$s) out(['ok' => false, 'error' => 'unknown']);
    if ($s['status'] !== 'active') out(['ok' => false, 'error' => 'suspended']);
    if (strtotime($s['expires_at'] . ' UTC') < time()) out(['ok' => false, 'error' => 'expired']);
    $d = one('SELECT id FROM devices WHERE sub_id=? AND device_id=?', [$s['id'], $dev]);
    if (!$d) {
        $n = (int)one('SELECT COUNT(*) c FROM devices WHERE sub_id=?', [$s['id']])['c'];
        if (!$register || $n >= (int)$s['max_devices']) out(['ok' => false, 'error' => 'device_limit']);
        q('INSERT INTO devices (sub_id,device_id,platform,version) VALUES (?,?,?,?)', [$s['id'], $dev, substr($in['platform'] ?? '', 0, 20), substr($in['version'] ?? '', 0, 20)]);
    } else {
        q('UPDATE devices SET last_seen=UTC_TIMESTAMP(), platform=COALESCE(NULLIF(?,\'\'),platform) WHERE id=?', [substr($in['platform'] ?? '', 0, 20), $d['id']]);
    }
    return [$s, $dev];
}
