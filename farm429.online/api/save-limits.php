<?php
require_once '../config/database.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Для сохранения данных необходимо авторизоваться']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}
try {
    try {
        $maxIdQuery = "SELECT MAX(id) as max_id FROM event_log";
        $maxIdResult = $pdo->query($maxIdQuery);
        $maxId = 1; 
        if ($maxIdResult && $row = $maxIdResult->fetch(PDO::FETCH_ASSOC)) {
            $maxId = max(1, (int)$row['max_id'] + 1); 
        }
        $alterQuery = "ALTER TABLE event_log AUTO_INCREMENT = ?";
        $stmt = $pdo->prepare($alterQuery);
        $stmt->bindValue(1, $maxId, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();
    } catch (PDOException $e) {
        error_log("Error fixing event_log table: " . $e->getMessage());
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'alarm_thresholds'
    ");
    $stmt->execute();
    $tableExists = (bool) $stmt->fetchColumn();
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
    $currentValues = [];
    $stmt = $pdo->prepare("
        SELECT parameter_type, min_limit, max_limit, target_value
        FROM alarm_thresholds
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentValues[$row['parameter_type']] = $row;
    }
    if (isset($data['temperature'])) {
        $hasTargetValue = isset($data['temperature']['target']);
        $targetTemp = $hasTargetValue ? $data['temperature']['target'] : null;
        if (!$hasTargetValue) {
            $stmt = $pdo->prepare("
                SELECT target_value 
                FROM alarm_thresholds 
                WHERE user_id = ? AND parameter_type = 'temperature'
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $existingTarget = $stmt->fetchColumn();
            if ($existingTarget) {
                $targetTemp = $existingTarget;
                if ($targetTemp < $data['temperature']['min']) {
                    $targetTemp = $data['temperature']['min'];
                } else if ($targetTemp > $data['temperature']['max']) {
                    $targetTemp = $data['temperature']['max'];
                }
            } else {
                $targetTemp = ($data['temperature']['min'] + $data['temperature']['max']) / 2;
            }
        }
        $hasTolerance = isset($data['temperature']['tolerance']);
        $tolerance = $hasTolerance ? $data['temperature']['tolerance'] : null;
        if (!$hasTolerance) {
            $stmt = $pdo->prepare("
                SELECT tolerance 
                FROM alarm_thresholds 
                WHERE user_id = ? AND parameter_type = 'temperature'
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $existingTolerance = $stmt->fetchColumn();
            $tolerance = $existingTolerance ?: 1.0;
        }
        $stmt = $pdo->prepare("
            INSERT INTO alarm_thresholds 
            (user_id, parameter_type, min_limit, max_limit, target_value, tolerance) 
            VALUES (?, 'temperature', ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            min_limit = VALUES(min_limit), 
            max_limit = VALUES(max_limit), 
            target_value = VALUES(target_value),
            tolerance = VALUES(tolerance)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $data['temperature']['min'],
            $data['temperature']['max'],
            $targetTemp,
            $tolerance
        ]);
    }
    if (isset($data['humidity'])) {
        $hasTargetValue = isset($data['humidity']['target']);
        $targetHumidity = $hasTargetValue ? $data['humidity']['target'] : null;
        if (!$hasTargetValue) {
            $stmt = $pdo->prepare("
                SELECT target_value 
                FROM alarm_thresholds 
                WHERE user_id = ? AND parameter_type = 'humidity_air'
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $existingTarget = $stmt->fetchColumn();
            if ($existingTarget) {
                $targetHumidity = $existingTarget;
                if ($targetHumidity < $data['humidity']['min']) {
                    $targetHumidity = $data['humidity']['min'];
                } else if ($targetHumidity > $data['humidity']['max']) {
                    $targetHumidity = $data['humidity']['max'];
                }
            } else {
                $targetHumidity = ($data['humidity']['min'] + $data['humidity']['max']) / 2;
            }
        }
        $hasTolerance = isset($data['humidity']['tolerance']);
        $tolerance = $hasTolerance ? $data['humidity']['tolerance'] : null;
        if (!$hasTolerance) {
            $stmt = $pdo->prepare("
                SELECT tolerance 
                FROM alarm_thresholds 
                WHERE user_id = ? AND parameter_type = 'humidity_air'
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $existingTolerance = $stmt->fetchColumn();
            $tolerance = $existingTolerance ?: 1.0;
        }
        $stmt = $pdo->prepare("
            INSERT INTO alarm_thresholds 
            (user_id, parameter_type, min_limit, max_limit, target_value, tolerance) 
            VALUES (?, 'humidity_air', ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            min_limit = VALUES(min_limit), 
            max_limit = VALUES(max_limit), 
            target_value = VALUES(target_value),
            tolerance = VALUES(tolerance)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $data['humidity']['min'],
            $data['humidity']['max'],
            $targetHumidity,
            $tolerance
        ]);
    }
    if (isset($data['soil_moisture'])) {
        $targetSoilMoisture = ($data['soil_moisture']['min'] + $data['soil_moisture']['max']) / 2;
        $stmt = $pdo->prepare("
            INSERT INTO alarm_thresholds 
            (user_id, parameter_type, min_limit, max_limit, target_value) 
            VALUES (?, 'humidity_soil', ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            min_limit = VALUES(min_limit), 
            max_limit = VALUES(max_limit), 
            target_value = VALUES(target_value)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $data['soil_moisture']['min'],
            $data['soil_moisture']['max'],
            $targetSoilMoisture
        ]);
    }
    if (isset($data['co2'])) {
        $targetCO2 = ($data['co2']['min'] + $data['co2']['max']) / 2;
        $stmt = $pdo->prepare("
            INSERT INTO alarm_thresholds 
            (user_id, parameter_type, min_limit, max_limit, target_value) 
            VALUES (?, 'co2', ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            min_limit = VALUES(min_limit), 
            max_limit = VALUES(max_limit), 
            target_value = VALUES(target_value)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $data['co2']['min'],
            $data['co2']['max'],
            $targetCO2
        ]);
    }
    $stmt = $pdo->prepare("
        SELECT temperature, humidity, soil_moisture, co2
        FROM sensor_data
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $sensorData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sensorData) {
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
        if (isset($data['temperature']) && isset($sensorData['temperature'])) {
            if ($sensorData['temperature'] > $data['temperature']['max'] || 
                $sensorData['temperature'] < $data['temperature']['min']) {
                $description = 'Тревога: температура ' . $sensorData['temperature'] . '°C вышла за установленные пределы (' . 
                               $data['temperature']['min'] . '°C - ' . $data['temperature']['max'] . '°C)';
                safeLogEvent($pdo, $_SESSION['user_id'], 'temperature', $description);
            }
        }
        if (isset($data['humidity']) && isset($sensorData['humidity'])) {
            if ($sensorData['humidity'] > $data['humidity']['max'] || 
                $sensorData['humidity'] < $data['humidity']['min']) {
                $description = 'Тревога: влажность воздуха ' . $sensorData['humidity'] . '% вышла за установленные пределы (' . 
                               $data['humidity']['min'] . '% - ' . $data['humidity']['max'] . '%)';
                safeLogEvent($pdo, $_SESSION['user_id'], 'humidity', $description);
            }
        }
        if (isset($data['soil_moisture']) && isset($sensorData['soil_moisture'])) {
            if ($sensorData['soil_moisture'] > $data['soil_moisture']['max'] || 
                $sensorData['soil_moisture'] < $data['soil_moisture']['min']) {
                $description = 'Тревога: влажность почвы ' . $sensorData['soil_moisture'] . '% вышла за установленные пределы (' . 
                               $data['soil_moisture']['min'] . '% - ' . $data['soil_moisture']['max'] . '%)';
                safeLogEvent($pdo, $_SESSION['user_id'], 'soil_moisture', $description);
            }
        }
        if (isset($data['co2']) && isset($sensorData['co2'])) {
            if ($sensorData['co2'] > $data['co2']['max'] || 
                $sensorData['co2'] < $data['co2']['min']) {
                $description = 'Тревога: уровень CO2 ' . $sensorData['co2'] . ' ppm вышел за установленные пределы (' . 
                               $data['co2']['min'] . ' ppm - ' . $data['co2']['max'] . ' ppm)';
                safeLogEvent($pdo, $_SESSION['user_id'], 'co2', $description);
            }
        }
    }
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error in save-limits.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?> 