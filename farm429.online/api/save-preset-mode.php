<?php
require_once '../config/database.php';
session_start();
header('Content-Type: application/json');
try {
    $checkTableStmt = $pdo->query("SHOW CREATE TABLE event_log");
    $tableStructure = $checkTableStmt->fetch(PDO::FETCH_ASSOC);
    if (isset($tableStructure['Create Table']) && 
        (strpos($tableStructure['Create Table'], 'AUTO_INCREMENT') === false ||
         strpos($tableStructure['Create Table'], 'PRIMARY KEY') === false)) {
        $pdo->exec("ALTER TABLE event_log MODIFY id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY");
    }
    $checkPresetTableStmt = $pdo->query("SHOW CREATE TABLE preset_modes");
    $presetTableStructure = $checkPresetTableStmt->fetch(PDO::FETCH_ASSOC);
    if (isset($presetTableStructure['Create Table']) && 
        (strpos($presetTableStructure['Create Table'], 'AUTO_INCREMENT') === false ||
        strpos($presetTableStructure['Create Table'], '`id`') !== false && 
        strpos($presetTableStructure['Create Table'], 'AUTO_INCREMENT') === false)) {
        $checkZeroIds = $pdo->query("SELECT COUNT(*) FROM preset_modes WHERE id = 0");
        $hasZeroIds = $checkZeroIds->fetchColumn() > 0;
        if ($hasZeroIds) {
            $tempIdQuery = $pdo->query("SELECT MIN(id) FROM preset_modes WHERE id < 0");
            $tempStartId = $tempIdQuery->fetchColumn();
            $tempStartId = $tempStartId ? $tempStartId - 1 : -1;
            $updateZeroIds = $pdo->prepare("
                UPDATE preset_modes 
                SET id = (SELECT @row_id := @row_id - 1) 
                WHERE id = 0
            ");
            $pdo->query("SET @row_id = " . $tempStartId);
            $updateZeroIds->execute();
        }
        $pdo->exec("ALTER TABLE preset_modes MODIFY id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
    }
} catch (Exception $e) {
    error_log("Ошибка при проверке/исправлении структуры таблиц: " . $e->getMessage());
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}
$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php:
if (empty($data['name'])) {
    echo json_encode(['success' => false, 'message' => 'Укажите название режима']);
    exit;
}
if (!isset($data['temperature']) || !is_numeric($data['temperature'])) {
    echo json_encode(['success' => false, 'message' => 'Некорректное значение температуры']);
    exit;
}
$temperature = floatval($data['temperature']);
if ($temperature < 20 || $temperature > 50) {
    echo json_encode(['success' => false, 'message' => 'Температура должна быть от 20 до 50°C']);
    exit;
}
if (!isset($data['tolerance']) || !is_numeric($data['tolerance'])) {
    echo json_encode(['success' => false, 'message' => 'Некорректное значение допуска температуры']);
    exit;
}
$tolerance = floatval($data['tolerance']);
if ($tolerance < 1 || $tolerance > 5) {
    echo json_encode(['success' => false, 'message' => 'Допуск температуры должен быть от 1 до 5°C']);
    exit;
}
if (!isset($data['humidity']) || !is_numeric($data['humidity'])) {
    echo json_encode(['success' => false, 'message' => 'Некорректное значение влажности']);
    exit;
}
$humidity = intval($data['humidity']);
if (!ctype_digit((string)$data['humidity']) || $humidity < 30 || $humidity > 99) {
    echo json_encode(['success' => false, 'message' => 'Влажность должна быть целым числом от 30 до 99%']);
    exit;
}
if (!isset($data['humidity_tolerance']) || !is_numeric($data['humidity_tolerance'])) {
    echo json_encode(['success' => false, 'message' => 'Некорректное значение допуска влажности']);
    exit;
}
$humidity_tolerance = floatval($data['humidity_tolerance']);
if ($humidity_tolerance < 1 || $humidity_tolerance > 5) {
    echo json_encode(['success' => false, 'message' => 'Допуск влажности должен быть от 1 до 5%']);
    exit;
}
if (!isset($data['light_hours']) || !is_numeric($data['light_hours'])) {
    echo json_encode(['success' => false, 'message' => 'Некорректное значение часов освещения']);
    exit;
}
$light_hours = floatval($data['light_hours']);
if ($light_hours < 0 || $light_hours > 24) {
    echo json_encode(['success' => false, 'message' => 'Часы освещения должны быть от 0 до 24']);
    exit;
}
if (empty($data['light_start']) || !preg_match('/^\d{2}:\d{2}$/', $data['light_start'])) {
    echo json_encode(['success' => false, 'message' => 'Некорректное время начала освещения']);
    exit;
}
if (empty($data['light_end']) || !preg_match('/^\d{2}:\d{2}$/', $data['light_end'])) {
    echo json_encode(['success' => false, 'message' => 'Некорректное время окончания освещения']);
    exit;
}
$name = trim($data['name']);
$light_start = $data['light_start']; 
$light_end = $data['light_end'];     
try {
    $pdo->beginTransaction(); 
    $stmt = $pdo->prepare("
        INSERT INTO preset_modes (
            user_id, name, temperature, tolerance, 
            humidity, humidity_tolerance, light_hours, 
            light_start, light_end, created_at
        ) 
        VALUES (
            :user_id, :name, :temperature, :tolerance, 
            :humidity, :humidity_tolerance, :light_hours, 
            :light_start, :light_end, NOW()
        )
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':name' => $name,
        ':temperature' => $temperature,
        ':tolerance' => $tolerance,
        ':humidity' => $humidity,
        ':humidity_tolerance' => $humidity_tolerance,
        ':light_hours' => $light_hours,
        ':light_start' => $light_start,
        ':light_end' => $light_end
    ]);
    $newPresetId = $pdo->lastInsertId();
    function safeLogEvent($pdo, $user_id, $event_type, $description) {
        try {
            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM event_log 
                WHERE user_id = ? 
                AND event_type = ? 
                AND event_description = ?
                AND created_at > NOW() - INTERVAL 10 MINUTE
            ");
            $checkStmt->execute([$user_id, $event_type, $description]);
            $exists = $checkStmt->fetchColumn() > 0;
            if (!$exists) {
                $stmt = $pdo->prepare("
                    INSERT INTO event_log 
                    (user_id, event_type, event_description, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$user_id, $event_type, $description]);
            }
            return true;
        } catch (Exception $e) {
            error_log("Ошибка при логировании события: " . $e->getMessage());
            return false;
        }
    }
    safeLogEvent($pdo, $user_id, 'system', 'Сохранен режим: ' . $data['name']);
    $pdo->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Режим успешно сохранен',
        'presetId' => $newPresetId
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Ошибка сохранения режима: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении режима: ' . $e->getMessage()]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Общая ошибка: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Произошла ошибка при сохранении режима']);
}
?>