<?php
/* WDSCS Creative Market : tables, droits d'accès, stockage sécurisé des compositions HTML/JSON/ZIP. */
const MARKET_DIR = __DIR__ . '/files/market';
const MARKET_CATS = ['tv' => 'TV Graphics', 'news' => 'News & Sport', 'church' => 'Church', 'gospel' => 'Gospel', 'events' => 'Events', 'cinematic' => 'Cinematic', 'social' => 'Social Media'];
const MARKET_EXT = ['html', 'htm', 'css', 'js', 'json', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'woff', 'woff2', 'ttf', 'otf', 'txt', 'md'];

function ensure_market_tables(): void {
    q("CREATE TABLE IF NOT EXISTS market_items (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(160) NOT NULL, category VARCHAR(20) NOT NULL DEFAULT 'church', format VARCHAR(8) NOT NULL DEFAULT 'html', resolution VARCHAR(12) NOT NULL DEFAULT 'FHD', tier ENUM('free','pro','premium','single') NOT NULL DEFAULT 'pro', price DECIMAL(10,2) NOT NULL DEFAULT 0, description TEXT NULL, tags VARCHAR(200) NULL, source VARCHAR(80) NOT NULL DEFAULT '', pay_url VARCHAR(255) NULL, preview VARCHAR(120) NOT NULL DEFAULT '', pw INT NOT NULL DEFAULT 1280, ph INT NOT NULL DEFAULT 720, downloads INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (active, category)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    try { q("ALTER TABLE market_items ADD COLUMN pay_url VARCHAR(255) NULL"); } catch (Throwable $e) {}
    q("CREATE TABLE IF NOT EXISTS market_grants (item_id INT NOT NULL, sub_id INT NOT NULL, amount DECIMAL(10,2) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (item_id, sub_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    q("CREATE TABLE IF NOT EXISTS market_log (id INT AUTO_INCREMENT PRIMARY KEY, item_id INT NOT NULL, sub_id INT NULL, ip VARCHAR(45) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (item_id), INDEX (sub_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    q("CREATE TABLE IF NOT EXISTS market_fav (item_id INT NOT NULL, sub_id INT NOT NULL, PRIMARY KEY (item_id, sub_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function market_sub(string $key) {
    $key = strtoupper(trim($key)); if ($key === '') return null;
    $s = one("SELECT s.*, u.name uname FROM subscriptions s JOIN users u ON u.id=s.user_id WHERE s.license_key=? AND s.status='active' AND s.expires_at>UTC_TIMESTAMP()", [$key]);
    return $s ?: null;
}
function market_is_premium(array $s): bool {
    $list = array_filter(array_map('trim', explode(',', (string)setting('market_premium_plans', ''))));
    if ($list) return in_array($s['plan'], $list, true);
    return plan_months($s['plan']) >= 12;
}
/** Droit d'accès : free = tous ; pro = abonnement actif ; premium = formule premium ; single = achat accordé. */
function market_can(array $it, ?array $sub): bool {
    if ($it['tier'] === 'free') return true;
    if (!$sub) return false;
    if (one('SELECT 1 x FROM market_grants WHERE item_id=? AND sub_id=?', [$it['id'], $sub['id']])) return true;
    if ($it['tier'] === 'pro') return true;
    if ($it['tier'] === 'premium') return market_is_premium($sub);
    return false;
}
function market_dir(int $id): string { return MARKET_DIR . '/' . $id; }
function market_rm(string $d): void {
    if (!is_dir($d)) return;
    foreach (scandir($d) as $f) { if ($f === '.' || $f === '..') continue; $p = "$d/$f"; is_dir($p) ? market_rm($p) : @unlink($p); }
    @rmdir($d);
}
/** Valide un chemin relatif d'archive : pas de .., extensions autorisées uniquement. */
function market_safe_rel(string $n): ?string {
    $n = str_replace('\\', '/', $n);
    if ($n === '' || substr($n, -1) === '/' || strpos($n, '..') !== false || $n[0] === '/' || preg_match('/[\x00-\x1f]/', $n)) return null;
    $e = strtolower(pathinfo($n, PATHINFO_EXTENSION));
    return in_array($e, MARKET_EXT, true) ? $n : null;
}
/** Enveloppe une conception JSON (wdscsDesigns) en page HTML autonome pour l'aperçu. */
function market_json_preview(array $j): ?array {
    if (isset($j['presets'][0]) && is_array($j['presets'][0])) return market_preset_preview($j);
    $d = $j['designs'][0] ?? (isset($j['html']) ? $j : null);
    if (!$d || !isset($d['html'])) return null;
    $w = (int)($d['width'] ?? 1280) ?: 1280; $h = (int)($d['height'] ?? 720) ?: 720;
    $base = '.lt-anchor{position:absolute;left:110px;bottom:115px}.hf-a{animation-duration:.75s;animation-fill-mode:both;animation-timing-function:cubic-bezier(.16,1,.3,1);animation-delay:var(--d,0s)}@keyframes a1{from{opacity:0;transform:translateX(-60px)}}@keyframes a2{from{opacity:0;transform:translateX(60px)}}@keyframes a3{from{opacity:0;transform:translateY(45px)}}@keyframes a4{from{opacity:0;transform:translateY(-45px)}}@keyframes a5{from{clip-path:inset(0 100% 0 0)}}@keyframes a6{from{transform:scaleX(0)}}@keyframes a7{0%{opacity:0;transform:scale(.7)}80%{opacity:1;transform:scale(1.04)}}@keyframes a8{from{opacity:0}}.anim-r{animation-name:a1}.anim-l{animation-name:a2}.anim-u{animation-name:a3}.anim-d{animation-name:a4}.anim-wipe{animation-name:a5}.anim-sx{animation-name:a6;transform-origin:left center}.anim-pop{animation-name:a7}.anim-fade{animation-name:a8}';
    $fonts = ''; $fam = array_unique(array_filter(array_map('strval', array_values($d['fonts'] ?? []))));
    if ($fam) $fonts = '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?' . implode('&', array_map(fn($f) => 'family=' . str_replace('%20', '+', rawurlencode($f)), $fam)) . '&display=swap">';
    $html = '<!doctype html><html><head><meta charset="utf-8">' . $fonts . '<style>html,body{margin:0;width:' . $w . 'px;height:' . $h . 'px;overflow:hidden;background:radial-gradient(circle at 55% 38%,#2d4b73,#0d182a 70%,#070d17);color:#fff}' . $base . ($d['css'] ?? '') . '</style></head><body>' . $d['html'] . '</body></html>';
    return [$html, $w, $h];
}

/** Aperçu simplifié d'un pack wisechurch.composition.v1 (premier preset, textes d'exemple). */
function market_preset_preview(array $j): ?array {
    $W = (int)($j['stage']['referenceWidth'] ?? 1920) ?: 1920; $H = (int)($j['stage']['referenceHeight'] ?? 1080) ?: 1080; $u = $W / 100;
    $css = ''; $body = '';
    foreach (array_slice($j['presets'], 0, 4) as $i => $p) {
        if (!is_array($p)) continue;
        $s = $p['style'] ?? []; $ps = $s['position'] ?? []; $an = $p['animation'] ?? []; $tm = $p['timing'] ?? [];
        $f = fn($k, $d) => htmlspecialchars(preg_match('/^\{\{.*\}\}$/', (string)($p['fields'][$k] ?? '')) ? $d : (string)($p['fields'][$k] ?? $d), ENT_QUOTES);
        $st = (float)($tm['startSec'] ?? 0) + $i * 3.2;
        $col = fn($k, $d) => preg_match('/^[#a-z0-9(),.% -]+$/i', (string)($s[$k] ?? '')) ? $s[$k] : $d;
        $css .= '.p' . $i . '{position:absolute;left:' . round(($ps['leftCqw'] ?? 4) * $u) . 'px;bottom:' . round(($ps['bottomCqh'] ?? 7) * $H / 100 + $i * 0) . 'px;max-width:' . round(($ps['maxWidthCqw'] ?? 60) * $u) . 'px;background:' . $col('surfaceColor', 'rgba(15,23,42,.9)') . ';border-left:' . round(.35 * $u) . 'px solid ' . $col('accentColor', '#2563eb') . ';border-radius:' . round(($s['radiusCqw'] ?? .5) * $u) . 'px;padding:' . round(.9 * $u) . 'px ' . round(1.6 * $u) . 'px;color:' . $col('textColor', '#fff') . ';animation:pi .8s ease ' . $st . 's both,po .5s ease ' . ($st + 2.4) . 's forwards;opacity:0}.p' . $i . ' i{display:block;font-style:normal;font-weight:800;font-size:' . round(($s['titleSizeCqw'] ?? 1.8) * $u) . 'px}.p' . $i . ' em{display:block;font-style:normal;font-size:' . round(($s['subtitleSizeCqw'] ?? 1.1) * $u) . 'px;color:' . $col('subtextColor', '#cbd5e1') . '}';
        $body .= '<div class="p' . $i . '"><i>' . $f('title', 'Prophète Josias') . '</i><em>' . $f('subtitle', 'Pasteur principal') . '</em></div>';
    }
    $html = '<!doctype html><html><head><meta charset="utf-8"><style>html,body{margin:0;width:' . $W . 'px;height:' . $H . 'px;overflow:hidden;background:radial-gradient(circle at 55% 38%,#2d4b73,#0d182a 70%,#070d17);font-family:system-ui,sans-serif}@keyframes pi{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:none}}@keyframes po{to{opacity:0}}' . $css . '</style></head><body>' . $body . '</body></html>';
    return [$html, $W, $H];
}
