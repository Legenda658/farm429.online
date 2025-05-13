<?php
header_remove("X-Content-Type-Options");
require_once __DIR__ . '/security_bootstrap.php';
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header("HTTP/1.0 400 Bad Request");
    exit("Файл не указан");
}
$file = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $_GET['file']);
if (!preg_match('/\.css$/', $file)) {
    header("HTTP/1.0 400 Bad Request");
    exit("Только CSS файлы разрешены");
}
$file_path = dirname(__DIR__) . '/css/' . basename($file);
if (!file_exists($file_path) || !is_file($file_path)) {
    header("HTTP/1.0 404 Not Found");
    exit("Файл не найден");
}
header("Content-Type: text/css");
header("Cache-Control: public, max-age=31536000"); 
readfile($file_path); 