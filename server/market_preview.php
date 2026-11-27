<?php
/* Aperçu isolé d'une composition : market_preview.php/<id>/<fichier>. CSP + sandbox : aucun accès au site, au stockage ni aux cookies. */
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/market_lib.php';
$p = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'), 2);
$id = (int)($p[0] ?? 0); $rel = market_safe_rel(rawurldecode($p[1] ?? ''));
function pv_404() { http_response_code(404); exit('404'); }
if (!$id || $rel === null) pv_404();
try { ensure_market_tables(); $it = one('SELECT id FROM market_items WHERE id=? AND active=1', [$id]); } catch (Throwable $e) { pv_404(); }
if (!$it) pv_404();
$base = realpath(market_dir($id)); $path = $base ? realpath($base . '/' . $rel) : false;
if (!$path || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) pv_404();
$mime = ['html' => 'text/html; charset=utf-8', 'htm' => 'text/html; charset=utf-8', 'css' => 'text/css', 'js' => 'application/javascript', 'json' => 'application/json', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf', 'txt' => 'text/plain; charset=utf-8', 'md' => 'text/plain; charset=utf-8'];
header('Content-Type: ' . ($mime[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
header("Content-Security-Policy: sandbox allow-scripts; default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com data: 'self'; img-src 'self' data: blob:; media-src 'self'; connect-src 'none'; frame-ancestors 'self'");
header('X-Content-Type-Options: nosniff'); header('Cache-Control: public, max-age=300');
readfile($path);
