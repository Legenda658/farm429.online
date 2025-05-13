<?php
$host = 'localhost';
$dbname = 'u3073667_Farmsite';
$username = 'u3073667_Farmsite';
$password = 'Slavik658!';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET SESSION SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
} catch(PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
} 