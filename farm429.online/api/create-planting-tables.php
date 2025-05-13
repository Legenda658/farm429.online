<?php
session_start();
require_once '../config/db_connect.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}
try {
    $db = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $queryEvents = "CREATE TABLE IF NOT EXISTS planting_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(20) NOT NULL,
        plant_name VARCHAR(100) NOT NULL,
        event_date DATE NOT NULL,
        event_time TIME NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $queryReminders = "CREATE TABLE IF NOT EXISTS planting_reminders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        reminder_date DATE NOT NULL,
        reminder_time TIME NULL,
        is_shown TINYINT(1) DEFAULT 0,
        FOREIGN KEY (event_id) REFERENCES planting_events(id) ON DELETE CASCADE
    )";
    $db->exec($queryEvents);
    $db->exec($queryReminders);
    echo json_encode(['success' => true, 'message' => 'Таблицы для календаря посадки успешно созданы']);
} catch (PDOException $e) {
    error_log('Ошибка при создании таблиц календаря посадки: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Произошла ошибка при создании таблиц: ' . $e->getMessage()]);
}
?> 