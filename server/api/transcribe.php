<?php
// Transcription Whisper dans le cloud : la régie envoie l'audio (WAV 16 kHz mono) ; la clé du fournisseur (Groq / OpenAI / compatible) reste sur le serveur.
require __DIR__ . '/_common.php';
require_once __DIR__ . '/../ai_lib.php';
$in = input();
[$s] = check_license($in, false);
if (setting('ai_enabled', '1') !== '1') out(['ok' => false, 'error' => 'ai_disabled']);
$b64 = (string)($in['audio']['b64'] ?? '');
if ($b64 === '' || strlen($b64) > 26000000) out(['ok' => false, 'error' => 'bad_audio']);
$lang = preg_replace('#[^a-z-]#', '', strtolower((string)($in['lang'] ?? '')));
$cands = all("SELECT p.* FROM ai_providers p WHERE p.enabled=1 AND p.api_key IS NOT NULL AND p.api_key<>'' AND p.type='openai'
    ORDER BY (p.endpoint LIKE '%groq.com%' OR p.endpoint LIKE '%openai.com%') DESC, p.priority ASC LIMIT 4");
if (!$cands) out(['ok' => false, 'error' => 'no_whisper_provider']);
$tmp = tempnam(sys_get_temp_dir(), 'wsp') . '.wav';
file_put_contents($tmp, base64_decode($b64));
set_time_limit(300);
$last = '';
foreach ($cands as $p) {
    $base = rtrim($p['endpoint'], '/');
    $m = one("SELECT model FROM ai_models WHERE provider_id=? AND model LIKE '%whisper%' AND enabled=1 ORDER BY priority ASC LIMIT 1", [$p['id']]);
    $model = $m['model'] ?? (strpos($base, 'groq') !== false ? 'whisper-large-v3-turbo' : 'whisper-1');
    $f = ['file' => new CURLFile($tmp, 'audio/wav', 'audio.wav'), 'model' => $model, 'response_format' => 'verbose_json',
          'timestamp_granularities[]' => 'word'];
    if ($lang !== '' && $lang !== 'auto') $f['language'] = substr($lang, 0, 2);
    $ch = curl_init($base . '/audio/transcriptions');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $f, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 240,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . dec($p['api_key'])]]);
    $raw = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($code === 200 && ($j = json_decode($raw, true))) {
        @unlink($tmp);
        q('INSERT INTO ai_log (sub_id,model,ok) VALUES (?,?,1)', [$s['id'], $model]);
        out(['ok' => true, 'text' => $j['text'] ?? '', 'words' => $j['words'] ?? [], 'segments' => $j['segments'] ?? [], 'model' => $p['name'] . ' / ' . $model]);
    }
    $last = "HTTP $code " . substr((string)$raw, 0, 160);
}
@unlink($tmp);
out(['ok' => false, 'error' => 'whisper_failed: ' . $last]);
