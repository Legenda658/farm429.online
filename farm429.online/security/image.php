<?php
header_remove("X-Content-Type-Options");
require_once __DIR__ . '/security_bootstrap.php';
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header("HTTP/1.0 400 Bad Request");
    exit("Файл не указан");
}
$file = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $_GET['file']);
if (!preg_match('/\.(jpg|jpeg|png|gif|webp|ico|svg)$/', $file, $matches)) {
    header("HTTP/1.0 400 Bad Request");
    exit("Только изображения разрешены");
}
$extension = strtolower($matches[1]);
$root_dir = dirname(__DIR__);
if (strpos($file, 'avatars/') === 0) {
    $file_path = $root_dir . '/uploads/' . $file;
    error_log("Запрос аватара: " . $file . ", полный путь: " . $file_path);
} elseif (strpos($file, 'farm_photos/') === 0) {
    $file_path = $root_dir . '/uploads/' . $file;
    error_log("Запрос фото фермы: " . $file . ", полный путь: " . $file_path);
} elseif (strpos($file, 'icon/') === 0) {
    $file_path = $root_dir . '/' . $file;
    error_log("Запрос иконки: " . $file . ", полный путь: " . $file_path);
} else {
    $file_path = $root_dir . '/uploads/' . $file;
    error_log("Запрос другого файла: " . $file . ", полный путь: " . $file_path);
}
if (!file_exists($file_path) || !is_file($file_path)) {
    error_log("Файл не найден: " . $file_path);
    header("HTTP/1.0 404 Not Found");
    header("Cache-Control: public, max-age=86400"); 
    exit("Файл не найден");
}
switch ($extension) {
    case 'jpg':
    case 'jpeg':
        header("Content-Type: image/jpeg");
        break;
    case 'png':
        header("Content-Type: image/png");
        break;
    case 'gif':
        header("Content-Type: image/gif");
        break;
    case 'webp':
        header("Content-Type: image/webp");
        break;
    case 'ico':
        header("Content-Type: image/x-icon");
        break;
    case 'svg':
        header("Content-Type: image/svg+xml");
        break;
}
header("Cache-Control: public, max-age=31536000"); 
header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT"); 
$filesize = filesize($file_path);
if ($filesize !== false) {
    header("Content-Length: " . $filesize);
}
readfile($file_path); 