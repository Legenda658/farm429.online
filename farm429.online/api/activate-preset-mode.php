<?php
require_once '../config/database.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
$mode_id = $input['mode_id'] ?? 0;
if (empty($mode_id) || !is_numeric($mode_id)) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID режима']);
    exit;
}
$user_id = $_SESSION['user_id'];
try {
    try {
        $stmt = $pdo->prepare("
            SHOW KEYS FROM event_log 
            WHERE Key_name = 'PRIMARY'
        ");
        $stmt->execute();
        $hasPrimaryKey = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$hasPrimaryKey) {
            $stmt = $pdo->prepare("
                SHOW COLUMNS FROM event_log 
                LIKE 'id'
            ");
            $stmt->execute();
            $hasIdColumn = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
            if ($hasIdColumn) {
                $pdo->exec("
                    ALTER TABLE event_log 
                    MODIFY id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY
                ");
            } else {
                $pdo->exec("
                    ALTER TABLE event_log 
                    ADD COLUMN id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST
                ");
            }
        }
    } catch (PDOException $e) {
        error_log("Error fixing event_log table: " . $e->getMessage());
    }
    function safeLogEvent($pdo, $userId, $eventType, $description) {
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM event_log 
                WHERE user_id = ? 
                AND event_type = ? 
                AND event_description = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            ");
            $stmt->execute([$userId, $eventType, $description]);
            $count = $stmt->fetchColumn();
            if ($count == 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO event_log (user_id, event_type, event_description) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$userId, $eventType, $description]);
            }
        } catch (PDOException $e) {
            error_log("Error logging event: " . $e->getMessage());
        }
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT * FROM preset_modes WHERE id = ? AND user_id = ?");
    $stmt->execute([$mode_id, $user_id]);
    $mode = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$mode) {
        throw new Exception('Режим не найден или вам не принадлежит');
    }
    $checkThresholdTable = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'alarm_thresholds'
    ");
    $checkThresholdTable->execute();
    $tableExists = (bool) $checkThresholdTable->fetchColumn();
    if (!$tableExists) {
        $pdo->exec("
            CREATE TABLE `alarm_thresholds` (
              `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `parameter_type` ENUM('temperature', 'humidity_air', 'humidity_soil', 'co2') NOT NULL,
              `min_limit` DECIMAL(8,2) NOT NULL,
              `max_limit` DECIMAL(8,2) NOT NULL,
              `target_value` DECIMAL(8,2) DEFAULT NULL COMMENT 'Целевое значение (если необходимо)',
              `tolerance` DECIMAL(5,2) DEFAULT 1.00 COMMENT 'Допустимое отклонение',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY `user_parameter_unique` (`user_id`, `parameter_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
    $temp_min_limit = max(0, $mode['temperature'] - $mode['tolerance']);
    $temp_max_limit = $mode['temperature'] + $mode['tolerance'];
    $humidity_min_limit = max(0, $mode['humidity'] - $mode['humidity_tolerance']);
    $humidity_max_limit = min(100, $mode['humidity'] + $mode['humidity_tolerance']);
    $stmtTemp = $pdo->prepare("
        INSERT INTO alarm_thresholds 
        (user_id, parameter_type, min_limit, max_limit, target_value, tolerance) 
        VALUES (?, 'temperature', ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        min_limit = VALUES(min_limit),
        max_limit = VALUES(max_limit),
        target_value = VALUES(target_value),
        tolerance = VALUES(tolerance)
    ");
    $stmtTemp->execute([
        $user_id,
        $temp_min_limit,
        $temp_max_limit,
        $mode['temperature'],
        $mode['tolerance']
    ]);
    $stmtHumidity = $pdo->prepare("
        INSERT INTO alarm_thresholds 
        (user_id, parameter_type, min_limit, max_limit, target_value, tolerance) 
        VALUES (?, 'humidity_air', ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        min_limit = VALUES(min_limit),
        max_limit = VALUES(max_limit),
        target_value = VALUES(target_value),
        tolerance = VALUES(tolerance)
    ");
    $stmtHumidity->execute([
        $user_id,
        $humidity_min_limit,
        $humidity_max_limit,
        $mode['humidity'],
        $mode['humidity_tolerance']
    ]);
    $checkLighting = $pdo->prepare("SELECT COUNT(*) FROM lighting_schedule WHERE user_id = ? AND is_exception = 0");
    $checkLighting->execute([$user_id]);
    if ($checkLighting->fetchColumn() == 0) {
        $createLighting = $pdo->prepare("
            INSERT INTO lighting_schedule 
            (user_id, required_hours, start_time, end_time, is_exception) 
            VALUES (?, ?, ?, ?, 0)
        ");
        $createLighting->execute([
            $user_id, 
            $mode['light_hours'], 
            $mode['light_start'], 
            $mode['light_end']
        ]);
    } else {
        $stmtLighting = $pdo->prepare("
            UPDATE lighting_schedule 
            SET required_hours = ?, start_time = ?, end_time = ? 
            WHERE user_id = ? AND is_exception = 0
        ");
        $stmtLighting->execute([
            $mode['light_hours'],
            $mode['light_start'],
            $mode['light_end'],
            $user_id
        ]);
    }
    safeLogEvent($pdo, $user_id, 'device', 'Применен режим: ' . $mode['name']);
    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Режим успешно активирован',
        'mode' => [
            'name' => $mode['name'],
            'temperature' => $mode['temperature'],
            'humidity' => $mode['humidity'],
            'light_hours' => $mode['light_hours']
        ]
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Детальная ошибка активации режима: ' . $e->getMessage());
    error_log('SQL State: ' . $e->errorInfo[0]);
    error_log('Error Code: ' . $e->errorInfo[1]);
    error_log('Error Message: ' . $e->errorInfo[2]);
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Общая ошибка: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}