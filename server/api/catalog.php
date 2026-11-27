<?php
require __DIR__ . '/_common.php';
require_once __DIR__ . '/../catalog_lib.php';
ensure_catalog_tables();
$in = input();
check_license($in, false);               // réservé aux abonnés actifs
$items = all('SELECT id,kind,title,lang,url,info FROM catalog WHERE active=1 ORDER BY kind, sort, title');
out(['ok' => true, 'items' => $items]);
