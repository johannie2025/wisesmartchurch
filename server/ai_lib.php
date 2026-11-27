<?php
/* Fournisseurs IA : endpoints automatiques (presets), récupération des modèles, appel avec rotation + cooldown. */
require_once __DIR__ . '/lib.php';

const AI_PRESETS = [
    'gemini'     => ['Google Gemini (free tier)', 'gemini', 'https://generativelanguage.googleapis.com/v1beta', 1],
    'openrouter' => ['OpenRouter (modèles :free)', 'openai', 'https://openrouter.ai/api/v1', 1],
    'groq'       => ['Groq (free tier)', 'openai', 'https://api.groq.com/openai/v1', 0],
    'mistral'    => ['Mistral (free tier)', 'openai', 'https://api.mistral.ai/v1', 0],
    'cerebras'   => ['Cerebras (free tier)', 'openai', 'https://api.cerebras.ai/v1', 0],
    'sambanova'  => ['SambaNova (free tier)', 'openai', 'https://api.sambanova.ai/v1', 0],
    'github'     => ['GitHub Models (gratuit)', 'openai', 'https://models.github.ai/inference', 0],
    'custom'     => ['Personnalisé (compatible OpenAI)', 'openai', '', 0],
];

function ai_http(string $url, string $method = 'GET', array $headers = [], ?string $body = null, int $timeout = 60): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers, CURLOPT_FOLLOWLOCATION => false]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return [$code, $raw === false ? '' : (string)$raw, $err];
}

/** Liste des modèles « chat » d'un fournisseur (gratuits uniquement pour OpenRouter / free_only). Lance RuntimeException. */
function ai_fetch_models(array $p): array {
    $key = dec($p['api_key']);
    $base = rtrim($p['endpoint'], '/');
    $out = [];
    if ($p['type'] === 'gemini') {
        $tok = ''; $n = 0;
        do {
            [$c, $raw, $err] = ai_http($base . '/models?pageSize=200' . ($tok ? '&pageToken=' . urlencode($tok) : ''), 'GET', ['x-goog-api-key: ' . $key]);
            if ($c !== 200) throw new RuntimeException($err ?: "HTTP $c " . substr($raw, 0, 120));
            $j = json_decode($raw, true) ?: [];
            foreach ($j['models'] ?? [] as $m) {
                if (!in_array('generateContent', $m['supportedGenerationMethods'] ?? [], true)) continue;
                $id = preg_replace('#^models/#', '', $m['name']);
                if (preg_match('/(embed|imagen|veo|tts|aqa|image|live|audio|robotics|computer-use)/i', $id)) continue;
                $out[$id] = 1;
            }
            $tok = $j['nextPageToken'] ?? ''; $n++;
        } while ($tok && $n < 5);
        return array_keys($out);
    }
    $h = $key ? ['Authorization: Bearer ' . $key] : [];
    [$c, $raw, $err] = ai_http($base . '/models', 'GET', $h);
    if ($c !== 200) throw new RuntimeException($err ?: "HTTP $c " . substr($raw, 0, 120));
    $j = json_decode($raw, true) ?: [];
    $list = $j['data'] ?? $j['models'] ?? (isset($j[0]) ? $j : []);
    $isOR = strpos($base, 'openrouter.ai') !== false;
    foreach ($list as $m) {
        $id = is_array($m) ? ($m['id'] ?? $m['name'] ?? '') : (string)$m;
        if (!$id || preg_match('/(whisper|embed|tts|guard|moderation|image|rerank|transcri|speech|dall)/i', $id)) continue;
        if ($isOR || !empty($p['free_only'])) {
            if ($isOR) {
                $free = isset($m['pricing']) && (float)($m['pricing']['prompt'] ?? 1) == 0 && (float)($m['pricing']['completion'] ?? 1) == 0;
                if (!$free && !preg_match('/:free$/', $id)) continue;
            }
        }
        $out[$id] = 1;
    }
    return array_keys($out);
}

/** Enregistre les modèles récupérés (ajoute les nouveaux, conserve les réglages existants, retire ceux qui ont disparu). */
function ai_store_models(array $p, array $ids): int {
    $audio = $p['type'] === 'gemini' ? 1 : 0;
    $n = 0;
    foreach ($ids as $i => $id) {
        $q = q('INSERT IGNORE INTO ai_models (provider_id,model,enabled,audio,priority) VALUES (?,?,?,?,?)', [$p['id'], $id, $i < 12 ? 1 : 0, $audio, 10]);
        $n += $q->rowCount();
    }
    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        q("DELETE FROM ai_models WHERE provider_id=? AND model NOT IN ($ph)", array_merge([$p['id']], $ids));
    }
    q('UPDATE ai_providers SET models_fetched_at=UTC_TIMESTAMP(), last_error=NULL WHERE id=?', [$p['id']]);
    return $n;
}

/** Appel d'un modèle. RuntimeException(message, code HTTP). */
function ai_call(array $p, string $model, string $prompt, string $system, bool $json, ?array $audio): string {
    $key = dec($p['api_key']);
    $base = rtrim($p['endpoint'], '/');
    if ($p['type'] === 'gemini') {
        $parts = [];
        if ($audio) $parts[] = ['inline_data' => ['mime_type' => $audio['mime'], 'data' => $audio['b64']]];
        $parts[] = ['text' => $prompt];
        $body = ['contents' => [['parts' => $parts]]];
        if ($system !== '') $body['systemInstruction'] = ['parts' => [['text' => $system]]];
        if ($json) $body['generationConfig'] = ['responseMimeType' => 'application/json'];
        [$c, $raw, $err] = ai_http($base . '/models/' . rawurlencode($model) . ':generateContent', 'POST',
            ['Content-Type: application/json', 'x-goog-api-key: ' . $key], json_encode($body), 90);
        if ($c !== 200) throw new RuntimeException($err ?: substr($raw, 0, 200), $c ?: 599);
        $j = json_decode($raw, true);
        $t = '';
        foreach ($j['candidates'][0]['content']['parts'] ?? [] as $pt) $t .= $pt['text'] ?? '';
        if ($t === '') throw new RuntimeException('réponse vide', 502);
        return $t;
    }
    if ($audio) throw new RuntimeException('audio non supporté', 400);
    $msgs = [];
    if ($system !== '') $msgs[] = ['role' => 'system', 'content' => $system];
    $msgs[] = ['role' => 'user', 'content' => $prompt];
    $h = ['Content-Type: application/json'];
    if ($key) $h[] = 'Authorization: Bearer ' . $key;
    [$c, $raw, $err] = ai_http($base . '/chat/completions', 'POST', $h, json_encode(['model' => $model, 'messages' => $msgs]), 90);
    if ($c !== 200) throw new RuntimeException($err ?: substr($raw, 0, 200), $c ?: 599);
    $j = json_decode($raw, true);
    $t = $j['choices'][0]['message']['content'] ?? '';
    if ($t === '') throw new RuntimeException('réponse vide', 502);
    return $t;
}

function ai_models_available(): int {
    return (int)one("SELECT COUNT(*) c FROM ai_models m JOIN ai_providers p ON p.id=m.provider_id
        WHERE p.enabled=1 AND p.api_key IS NOT NULL AND p.api_key<>'' AND m.enabled=1 AND (m.cooldown_until IS NULL OR m.cooldown_until<UTC_TIMESTAMP())")['c'];
}

/**
 * Rotation automatique : modèles activés hors cooldown, par priorité puis du moins récemment utilisé.
 * Cooldown : 429 → ai_cooldown_min ; 401/403 → 30 min sur tout le fournisseur ; 400/404 → 60 min ; autre → 1 min.
 * Renvoie [ok, texte|erreur, modèle].
 */
function ai_complete(string $prompt, string $system, bool $json, ?array $audio, int $subId): array {
    $cool = max(1, (int)setting('ai_cooldown_min', '5'));
    $cands = all("SELECT m.id mid, m.model, p.* FROM ai_models m JOIN ai_providers p ON p.id=m.provider_id
        WHERE p.enabled=1 AND p.api_key IS NOT NULL AND p.api_key<>'' AND m.enabled=1
          AND (m.cooldown_until IS NULL OR m.cooldown_until<UTC_TIMESTAMP()) " . ($audio ? 'AND m.audio=1' : '') . "
        ORDER BY p.priority ASC, m.priority ASC, (m.last_used IS NULL) DESC, m.last_used ASC LIMIT 40");
    if (!$cands) return [false, 'aucun modèle disponible', ''];
    $tried = 0; $last = '';
    foreach ($cands as $c) {
        if (++$tried > 8) break;
        try {
            $txt = ai_call($c, $c['model'], $prompt, $system, $json, $audio);
            q('UPDATE ai_models SET last_used=UTC_TIMESTAMP(), fails=0, ok_count=ok_count+1 WHERE id=?', [$c['mid']]);
            q('INSERT INTO ai_log (sub_id,model,ok) VALUES (?,?,1)', [$subId, $c['model']]);
            return [true, $txt, $c['name'] . ' / ' . $c['model']];
        } catch (RuntimeException $e) {
            $code = (int)$e->getCode(); $last = $e->getMessage();
            if ($code === 429) q('UPDATE ai_models SET cooldown_until=DATE_ADD(UTC_TIMESTAMP(),INTERVAL ? MINUTE), fails=fails+1 WHERE id=?', [$cool, $c['mid']]);
            elseif ($code === 401 || $code === 403) { q('UPDATE ai_models SET cooldown_until=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 MINUTE) WHERE provider_id=?', [$c['id']]); q('UPDATE ai_providers SET last_error=? WHERE id=?', ["HTTP $code : clé refusée ?", $c['id']]); }
            elseif ($code === 400 || $code === 404) q('UPDATE ai_models SET cooldown_until=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 60 MINUTE), fails=fails+1 WHERE id=?', [$c['mid']]);
            else q('UPDATE ai_models SET cooldown_until=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 MINUTE), fails=fails+1 WHERE id=?', [$c['mid']]);
            q('INSERT INTO ai_log (sub_id,model,ok) VALUES (?,?,0)', [$subId, $c['model']]);
        }
    }
    return [false, 'tous les modèles ont échoué (' . substr($last, 0, 120) . ')', ''];
}
