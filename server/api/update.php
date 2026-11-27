<?php
require __DIR__ . '/_common.php';
require_once __DIR__ . '/../catalog_lib.php';
ensure_catalog_tables();
$in = input();
check_license($in, false);               // notification réservée aux abonnés actifs
$pf = preg_replace('/[^a-z]/', '', strtolower($in['platform'] ?? ''));
$map = ['desktop' => 'exe', 'android' => 'apk', 'web' => 'ios', 'exe' => 'exe', 'apk' => 'apk', 'mac' => 'mac'];
$pf = $map[$pf] ?? 'exe';
$r = one('SELECT * FROM releases WHERE platform=? ORDER BY id DESC LIMIT 1', [$pf]);
if (!$r) out(['ok' => true, 'latest' => null]);
$d = one('SELECT external_url FROM downloads WHERE platform=?', [$pf]);
$base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
out(['ok' => true, 'latest' => ['version' => $r['version'], 'mandatory' => (int)$r['mandatory'], 'notes' => ['fr' => $r['notes_fr'], 'es' => $r['notes_es'], 'en' => $r['notes_en']], 'url' => ($d && $d['external_url']) ? $d['external_url'] : $base . '/#download', 'date' => $r['created_at']]]);
