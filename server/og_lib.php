<?php
/* Balises Open Graph / Twitter : aperçu riche dans WhatsApp, Facebook, Telegram, X, iMessage… */
function site_base(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/';
}
function og_tags(string $title, string $desc, string $page, ?string $image = null): string {
    $b = site_base(); $img = $image ?: $b . 'assets/og.jpg'; $url = $b . ltrim($page, '/');
    $e = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    return '<link rel="canonical" href="' . $e($url) . '"><meta property="og:type" content="website"><meta property="og:site_name" content="WDSCS Studio 3.0 — Wise Design">'
        . '<meta property="og:title" content="' . $e($title) . '"><meta property="og:description" content="' . $e(mb_strimwidth($desc, 0, 200, '…')) . '">'
        . '<meta property="og:url" content="' . $e($url) . '"><meta property="og:image" content="' . $e($img) . '"><meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">'
        . '<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="' . $e($title) . '"><meta name="twitter:description" content="' . $e(mb_strimwidth($desc, 0, 200, '…')) . '"><meta name="twitter:image" content="' . $e($img) . '">';
}
