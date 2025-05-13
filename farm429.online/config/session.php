<?php
session_start();
$headers_file = __DIR__ . '/headers.php';
if (file_exists($headers_file)) {
    require_once $headers_file;
}
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'first_name' => $_SESSION['first_name'],
            'last_name' => $_SESSION['last_name'],
            'telegram' => $_SESSION['telegram']
        ];
    }
    return null;
}
function getGreeting() {
    $hour = date('H');
    if ($hour >= 5 && $hour < 12) {
        return 'Доброе утро';
    } elseif ($hour >= 12 && $hour < 18) {
        return 'Добрый день';
    } else {
        return 'Добрый вечер';
    }
} 