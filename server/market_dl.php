<?php
/* Téléchargement d'une composition selon les droits (gratuit / licence / achat individuel). POST id + license. */
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/market_lib.php';
function mdl_fail(string $m, int $c = 403) { http_response_code($c); header('Content-Type: text/plain; charset=utf-8'); exit($m); }
try { ensure_market_tables(); } catch (Throwable $e) { mdl_fail('Service indisponible', 500); }
$id = (int)($_REQUEST['id'] ?? 0);
$it = one('SELECT * FROM market_items WHERE id=? AND active=1', [$id]);
if (!$it) mdl_fail('Produit introuvable', 404);
$sub = market_sub($_POST['license'] ?? '');
if (!market_can($it, $sub)) {
    mdl_fail($it['tier'] === 'single' ? 'Ce pack est vendu séparément : achetez-le ou demandez son activation sur votre licence.' : ($sub ? 'Votre formule ne donne pas accès à ce pack.' : 'Licence active requise pour ce pack.'));
}
if ($it['tier'] !== 'free' && $_SERVER['REQUEST_METHOD'] !== 'POST') mdl_fail('Utilisez le bouton Télécharger de la boutique.', 405);
$dir = market_dir($id); $f = ['zip' => 'source.zip', 'json' => 'source.json', 'html' => 'index.html'][$it['format']] ?? '';
$path = $dir . '/' . $f;
if (!is_file($path)) mdl_fail('Fichier indisponible', 404);
q('UPDATE market_items SET downloads=downloads+1 WHERE id=?', [$id]);
q('INSERT INTO market_log (item_id,sub_id,ip) VALUES (?,?,?)', [$id, $sub['id'] ?? null, client_ip()]);
$name = preg_replace('/[^A-Za-z0-9_-]+/', '_', $it['title']) ?: 'template';
header('Content-Type: ' . ['zip' => 'application/zip', 'json' => 'application/json', 'html' => 'text/html; charset=utf-8'][$it['format']]);
header('Content-Disposition: attachment; filename="' . $name . '.' . $it['format'] . '"'); header('Content-Length: ' . filesize($path)); header('X-Content-Type-Options: nosniff');
readfile($path);
