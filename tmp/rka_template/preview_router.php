<?php
// Loopback-only visual fixture, not an application route or authenticated data.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/lr-preview') {
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__ . '/ui/laba_rugi.html'); return;
}
if ($path === '/preview') {
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__ . '/ui/rka_kanwil_surabaya.html'); return;
}
$assets = ['app.css','app.min.css','app.js','app.min.js','url-mask.min.js','required-markers.min.js'];
if (str_starts_with($path, '/assets/') && in_array(substr($path,8),$assets,true)) {
    header('Content-Type: ' . (str_ends_with($path,'.css') ? 'text/css' : 'text/javascript'));
    readfile(__DIR__ . '/../../public' . $path); return;
}
http_response_code(404);
