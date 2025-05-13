<?php
header_remove("X-Content-Type-Options");
require_once __DIR__ . '/security_bootstrap.php';
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header("HTTP/1.0 400 Bad Request");
    exit("Файл не указан");
}
$file = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $_GET['file']);
if (!preg_match('/\.webmanifest$/', $file)) {
    header("HTTP/1.0 400 Bad Request");
    exit("Только webmanifest файлы разрешены");
}
$file_path = dirname(__DIR__) . '/icon/' . basename($file);
if (!file_exists($file_path) || !is_file($file_path)) {
    header("HTTP/1.0 404 Not Found");
    exit("Файл не найден");
}
header("Content-Type: application/manifest+json");
header("Cache-Control: public, max-age=604800"); 
readfile($file_path); 