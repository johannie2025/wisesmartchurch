<?php
// Proxy IA : la régie autorisée (licence valide) appelle les modèles configurés dans le panel ; les clés API ne quittent jamais le serveur.
require __DIR__ . '/_common.php';
require_once __DIR__ . '/../ai_lib.php';
$in = input();
[$s] = check_license($in, false);
if (setting('ai_enabled', '1') !== '1') out(['ok' => false, 'error' => 'ai_disabled']);
$limit = (int)setting('ai_daily_limit', '300');
$used = (int)one("SELECT COUNT(*) c FROM ai_log WHERE sub_id=? AND ok=1 AND created_at>=DATE_FORMAT(UTC_TIMESTAMP(),'%Y-%m-%d')", [$s['id']])['c'];
if ($limit > 0 && $used >= $limit) out(['ok' => false, 'error' => 'quota_daily']);
$prompt = (string)($in['prompt'] ?? '');
if ($prompt === '' || strlen($prompt) > 60000) out(['ok' => false, 'error' => 'bad_prompt']);
$audio = null;
if (!empty($in['audio']['b64'])) {
    if (strlen($in['audio']['b64']) > 26000000) out(['ok' => false, 'error' => 'audio_too_big']);
    $audio = ['mime' => preg_replace('#[^a-z0-9/+.-]#i', '', $in['audio']['mime'] ?? 'audio/webm'), 'b64' => $in['audio']['b64']];
}
set_time_limit(180);
[$ok, $txt, $model] = ai_complete($prompt, (string)($in['system'] ?? ''), !empty($in['json']), $audio, (int)$s['id']);
out($ok ? ['ok' => true, 'text' => $txt, 'model' => $model] : ['ok' => false, 'error' => 'no_model: ' . $txt]);
