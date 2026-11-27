<?php
require __DIR__ . '/_common.php';
$in = input();
[$s, $dev] = check_license($in, false);
$u = $in['usage'] ?? [];
$takes = max(0, min(100000, (int)($u['takes'] ?? 0)));
$mins = max(0, min(100000, (int)($u['mins'] ?? 0)));
$sess = max(0, min(100000, (int)($u['sessions'] ?? 0)));
if ($takes || $mins) q('INSERT INTO usage_log (sub_id,device_id,takes,mins,sessions) VALUES (?,?,?,?,?)', [$s['id'], $dev, $takes, $mins, $sess]);
require_once __DIR__ . '/../ai_lib.php';
out(['ok' => true, 'plan' => $s['plan'], 'expires' => strtotime($s['expires_at'] . ' UTC'), 'ai' => ['enabled' => setting('ai_enabled', '1') === '1', 'models' => ai_models_available()]]);
