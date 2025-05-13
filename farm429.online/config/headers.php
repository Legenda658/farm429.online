<?php
header("X-Content-Type-Options: nosniff");
function set_ajax_cache_headers($can_cache = false, $cache_time = 300) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        if ($can_cache) {
            header("Cache-Control: private, max-age=$cache_time");
            header("Expires: " . gmdate("D, d M Y H:i:s", time() + $cache_time) . " GMT");
        } else {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
    }
}
$request_uri = $_SERVER['REQUEST_URI'];
if (preg_match('/\.([a-zA-Z0-9]+)(?:\?.*)?$/', $request_uri, $matches)) {
    $extension = strtolower($matches[1]);
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            header("Content-Type: image/jpeg");
            header("Cache-Control: public, max-age=31536000"); 
            break;
        case 'png':
            header("Content-Type: image/png");
            header("Cache-Control: public, max-age=31536000"); 
            break;
        case 'gif':
            header("Content-Type: image/gif");
            header("Cache-Control: public, max-age=31536000"); 
            break;
        case 'webp':
            header("Content-Type: image/webp");
            header("Cache-Control: public, max-age=31536000"); 
            break;
        case 'ico':
            header("Content-Type: image/x-icon");
            header("Cache-Control: public, max-age=31536000"); 
            break;
        case 'svg':
            header("Content-Type: image/svg+xml");
            header("Cache-Control: public, max-age=31536000"); 
            break;
        case 'css':
            header("Content-Type: text/css");
            header("Cache-Control: public, max-age=2592000"); 
            break;
        case 'js':
            header("Content-Type: application/javascript");
            header("Cache-Control: public, max-age=2592000"); 
            break;
        case 'woff':
            header("Content-Type: font/woff");
            header("Cache-Control: public, max-age=31536000"); 
            break;
        case 'woff2':
            header("Content-Type: font/woff2");
            header("Cache-Control: public, max-age=31536000"); 
            break;
        case 'ttf':
            header("Content-Type: font/ttf");
            header("Cache-Control: public, max-age=31536000"); 
            break;
        case 'otf':
            header("Content-Type: font/otf");
            header("Cache-Control: public, max-age=31536000"); 
            break;
        case 'eot':
            header("Content-Type: application/vnd.ms-fontobject");
            header("Cache-Control: public, max-age=31536000"); 
            break;
        case 'webmanifest':
            header("Content-Type: application/manifest+json");
            header("Cache-Control: public, max-age=604800"); 
            break;
        case 'json':
            header("Content-Type: application/json");
            header("Cache-Control: public, max-age=604800"); 
            break;
        case 'xml':
            header("Content-Type: text/xml");
            header("Cache-Control: public, max-age=604800"); 
            break;
    }
}
if (strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("X-Content-Type-Options: nosniff");
}
