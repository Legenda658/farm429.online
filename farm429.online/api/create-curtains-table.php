<?php
require_once '../config/database.php';
session_start();
header('Content-Type: application/json');
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'curtains_schedule'
    ");
    $stmt->execute();
    $tableExists = (bool) $stmt->fetchColumn();
    if (!$tableExists) {
        $pdo->exec("
            CREATE TABLE `curtains_schedule` (
              `id` int NOT NULL AUTO_INCREMENT,
              `user_id` int NOT NULL,
              `required_hours` decimal(4,2) DEFAULT NULL,
              `start_time` time NOT NULL,
              `end_time` time NOT NULL,
              `is_exception` tinyint(1) DEFAULT '0',
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");
        $checkColumnStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'farm_status' 
            AND COLUMN_NAME = 'curtains_level'
        ");
        $checkColumnStmt->execute();
        $columnExists = (bool) $checkColumnStmt->fetchColumn();
        if (!$columnExists) {
            $pdo->exec("ALTER TABLE farm_status ADD COLUMN curtains_level DECIMAL(4,2) DEFAULT NULL AFTER light_level");
        }
        echo json_encode(['success' => true, 'message' => 'Таблица curtains_schedule успешно создана']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Таблица curtains_schedule уже существует']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка при создании таблицы: ' . $e->getMessage()]);
} 