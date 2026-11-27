<?php
// Synthèse vocale dans le cloud : la régie envoie un texte, le panel renvoie un fichier audio (WAV/MP3). La clé reste sur le serveur.
// Fournisseurs essayés : Google Gemini (TTS multilingue FR/ES/EN) puis OpenAI (tts-1).
require __DIR__ . '/_common.php';
require_once __DIR__ . '/../ai_lib.php';
$in = input();
[$s] = check_license($in, false);
if (setting('ai_enabled', '1') !== '1') out(['ok' => false, 'error' => 'ai_disabled']);
$text = trim((string)($in['text'] ?? ''));
if ($text === '' || mb_strlen($text) > 3000) out(['ok' => false, 'error' => 'bad_text']);
$lang = substr(preg_replace('#[^a-z-]#', '', strtolower((string)($in['lang'] ?? 'fr'))), 0, 2);
$cands = all("SELECT p.* FROM ai_providers p WHERE p.enabled=1 AND p.api_key IS NOT NULL AND p.api_key<>'' AND (p.type='gemini' OR (p.type='openai' AND p.endpoint LIKE '%openai.com%')) ORDER BY (p.type='gemini') DESC, p.priority ASC LIMIT 4");
if (!$cands) out(['ok' => false, 'error' => 'no_tts_provider']);
set_time_limit(120);
function wav_wrap(string $pcm, int $rate = 24000): string {
    $n = strlen($pcm);
    return 'RIFF' . pack('V', 36 + $n) . 'WAVEfmt ' . pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16) . 'data' . pack('V', $n) . $pcm;
}
$last = '';
foreach ($cands as $p) {
    $base = rtrim($p['endpoint'], '/'); $key = dec($p['api_key']);
    if ($p['type'] === 'gemini') {
        $voice = ['fr' => 'Kore', 'es' => 'Kore', 'en' => 'Puck'][$lang] ?? 'Kore';
        $body = json_encode(['contents' => [['parts' => [['text' => $text]]]],
            'generationConfig' => ['responseModalities' => ['AUDIO'], 'speechConfig' => ['voiceConfig' => ['prebuiltVoiceConfig' => ['voiceName' => $voice]]]]]);
        foreach (['gemini-2.5-flash-preview-tts', 'gemini-2.5-pro-preview-tts'] as $model) {
            [$c, $raw, $err] = ai_http($base . '/models/' . $model . ':generateContent', 'POST', ['x-goog-api-key: ' . $key, 'Content-Type: application/json'], $body, 90);
            $j = json_decode($raw, true);
            $d = $j['candidates'][0]['content']['parts'][0]['inlineData']['data'] ?? '';
            if ($c === 200 && $d !== '') {
                q('INSERT INTO ai_log (sub_id,model,ok) VALUES (?,?,1)', [$s['id'], $model]);
                out(['ok' => true, 'mime' => 'audio/wav', 'b64' => base64_encode(wav_wrap(base64_decode($d))), 'model' => $p['name'] . ' / ' . $model]);
            }
            $last = "HTTP $c " . substr($raw ?: $err, 0, 160);
        }
    } else {
        $body = json_encode(['model' => 'tts-1', 'input' => $text, 'voice' => 'nova', 'response_format' => 'mp3']);
        [$c, $raw, $err] = ai_http($base . '/audio/speech', 'POST', ['Authorization: Bearer ' . $key, 'Content-Type: application/json'], $body, 90);
        if ($c === 200 && $raw !== '') {
            q('INSERT INTO ai_log (sub_id,model,ok) VALUES (?,?,1)', [$s['id'], 'tts-1']);
            out(['ok' => true, 'mime' => 'audio/mpeg', 'b64' => base64_encode($raw), 'model' => $p['name'] . ' / tts-1']);
        }
        $last = "HTTP $c " . substr($raw ?: $err, 0, 160);
    }
}
out(['ok' => false, 'error' => 'tts_failed: ' . $last]);
