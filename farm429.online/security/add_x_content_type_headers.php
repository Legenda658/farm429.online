<?php
function add_security_headers() {
    $request_uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/\.([a-zA-Z0-9]+)(?:\?.*)?$/', $request_uri, $matches)) {
        $extension = strtolower($matches[1]);
        switch ($extension) {
            case 'css':
                header("Content-Type: text/css");
                header("Cache-Control: public, max-age=2592000"); 
                break;
            case 'js':
                header("Content-Type: application/javascript");
                header("Cache-Control: public, max-age=2592000"); 
                break;
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
            case 'webmanifest':
                header("Content-Type: application/manifest+json");
                header("Cache-Control: public, max-age=604800"); 
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
}
add_security_headers(); 