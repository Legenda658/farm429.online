<?php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
try {
    $checkTableStmt = $pdo->query("SHOW CREATE TABLE event_log");
    $tableStructure = $checkTableStmt->fetch(PDO::FETCH_ASSOC);
    if (isset($tableStructure['Create Table']) && 
        (strpos($tableStructure['Create Table'], 'AUTO_INCREMENT') === false ||
         strpos($tableStructure['Create Table'], 'PRIMARY KEY') === false)) {
        $checkZeroIds = $pdo->query("SELECT COUNT(*) FROM event_log WHERE id = 0");
        $hasZeroIds = $checkZeroIds->fetchColumn() > 0;
        if ($hasZeroIds) {
            $tempIdQuery = $pdo->query("SELECT MIN(id) FROM event_log WHERE id < 0");
            $tempStartId = $tempIdQuery->fetchColumn();
            $tempStartId = $tempStartId ? $tempStartId - 1 : -1;
            $updateZeroIds = $pdo->prepare("
                UPDATE event_log 
                SET id = (SELECT @row_id := @row_id - 1) 
                WHERE id = 0
            ");
            $pdo->query("SET @row_id = " . $tempStartId);
            $updateZeroIds->execute();
        }
        $pdo->exec("ALTER TABLE event_log MODIFY id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY");
    }
    $checkPlantingTableStmt = $pdo->query("SHOW CREATE TABLE planting_events");
    $plantingTableStructure = $checkPlantingTableStmt->fetch(PDO::FETCH_ASSOC);
    if (isset($plantingTableStructure['Create Table']) && 
        (strpos($plantingTableStructure['Create Table'], 'AUTO_INCREMENT') === false ||
         strpos($plantingTableStructure['Create Table'], 'PRIMARY KEY') === false)) {
        $checkZeroIds = $pdo->query("SELECT COUNT(*) FROM planting_events WHERE id = 0");
        $hasZeroIds = $checkZeroIds->fetchColumn() > 0;
        if ($hasZeroIds) {
            $tempIdQuery = $pdo->query("SELECT MIN(id) FROM planting_events WHERE id < 0");
            $tempStartId = $tempIdQuery->fetchColumn();
            $tempStartId = $tempStartId ? $tempStartId - 1 : -1;
            $updateZeroIds = $pdo->prepare("
                UPDATE planting_events 
                SET id = (SELECT @row_id := @row_id - 1) 
                WHERE id = 0
            ");
            $pdo->query("SET @row_id = " . $tempStartId);
            $updateZeroIds->execute();
        }
        $pdo->exec("ALTER TABLE planting_events MODIFY id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
    }
    $checkRemindersTableStmt = $pdo->query("SHOW CREATE TABLE planting_reminders");
    $remindersTableStructure = $checkRemindersTableStmt->fetch(PDO::FETCH_ASSOC);
    if (isset($remindersTableStructure['Create Table']) && 
        (strpos($remindersTableStructure['Create Table'], 'AUTO_INCREMENT') === false ||
         strpos($remindersTableStructure['Create Table'], 'PRIMARY KEY') === false)) {
        $checkZeroIds = $pdo->query("SELECT COUNT(*) FROM planting_reminders WHERE id = 0");
        $hasZeroIds = $checkZeroIds->fetchColumn() > 0;
        if ($hasZeroIds) {
            $tempIdQuery = $pdo->query("SELECT MIN(id) FROM planting_reminders WHERE id < 0");
            $tempStartId = $tempIdQuery->fetchColumn();
            $tempStartId = $tempStartId ? $tempStartId - 1 : -1;
            $updateZeroIds = $pdo->prepare("
                UPDATE planting_reminders 
                SET id = (SELECT @row_id := @row_id - 1) 
                WHERE id = 0
            ");
            $pdo->query("SET @row_id = " . $tempStartId);
            $updateZeroIds->execute();
        }
        $pdo->exec("ALTER TABLE planting_reminders MODIFY id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
    }
} catch (Exception $e) {
    error_log("Ошибка при проверке/исправлении структуры таблиц: " . $e->getMessage());
}
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
function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Необходима авторизация');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Неверный метод запроса');
}
$event_id_to_update = isset($_POST['event_id']) ? intval($_POST['event_id']) : null;
$user_id = $_SESSION['user_id'];
$event_type = $_POST['event-type'] ?? '';
$plant_name = $_POST['plant-name'] ?? '';
$event_date = !empty($_POST['event-date']) ? $_POST['event-date'] : date('Y-m-d');
$event_time_input = $_POST['event-time'] ?? '';
$event_time = (!empty($event_time_input) && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $event_time_input)) ? $event_time_input : null;
$event_notes = $_POST['event-notes'] ?? '';
$has_reminder = ($event_type === 'reminder' || !empty($_POST['reminder-date']));
$reminder_date_input = $_POST['reminder-date'] ?? '';
$reminder_time_input = $_POST['reminder-time'] ?? '';
$reminder_date = null;
$reminder_time = null;
if ($has_reminder) {
    $reminder_date = !empty($reminder_date_input) ? $reminder_date_input : date('Y-m-d');
    $reminder_time = (!empty($reminder_time_input) && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $reminder_time_input)) ? $reminder_time_input : null;
}
if (empty($event_type) || empty($plant_name) || empty($event_date)) {
    sendJsonResponse(false, 'Пожалуйста, заполните все обязательные поля (Тип события, Название растения, Дата события)');
}
if ($event_type === 'reminder' && empty($reminder_date)) {
    sendJsonResponse(false, 'Для напоминания необходимо указать дату напоминания');
}
try {
    if (!isset($pdo)) {
        throw new Exception("Ошибка подключения к базе данных.");
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();
    if ($event_id_to_update) {
        $check_stmt = $pdo->prepare("SELECT id FROM planting_events WHERE id = :event_id AND user_id = :user_id");
        $check_stmt->bindParam(':event_id', $event_id_to_update, PDO::PARAM_INT);
        $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $check_stmt->execute();
        if ($check_stmt->rowCount() === 0) {
            throw new Exception("Событие для редактирования не найдено или не принадлежит вам.");
        }
        $update_event_sql = "UPDATE planting_events SET 
                                type = :type, 
                                plant_name = :plant_name, 
                                event_date = :event_date, 
                                event_time = :event_time, 
                                notes = :notes 
                             WHERE id = :event_id";
        $stmt = $pdo->prepare($update_event_sql);
        $stmt->bindParam(':type', $event_type);
        $stmt->bindParam(':plant_name', $plant_name);
        $stmt->bindParam(':event_date', $event_date);
        $stmt->bindParam(':event_time', $event_time, $event_time === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':notes', $event_notes);
        $stmt->bindParam(':event_id', $event_id_to_update, PDO::PARAM_INT);
        $stmt->execute();
        $delete_reminder_stmt = $pdo->prepare("DELETE FROM planting_reminders WHERE event_id = :event_id");
        $delete_reminder_stmt->bindParam(':event_id', $event_id_to_update, PDO::PARAM_INT);
        $delete_reminder_stmt->execute();
        if ($event_type === 'reminder' && $reminder_date) {
            $insert_reminder_stmt = $pdo->prepare("INSERT INTO planting_reminders 
                (event_id, reminder_date, reminder_time) 
                VALUES (:event_id, :reminder_date, :reminder_time)");
            $insert_reminder_stmt->bindParam(':event_id', $event_id_to_update, PDO::PARAM_INT);
            $insert_reminder_stmt->bindParam(':reminder_date', $reminder_date);
            $insert_reminder_stmt->bindParam(':reminder_time', $reminder_time, $reminder_time === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $insert_reminder_stmt->execute();
        }
        $final_event_id = $event_id_to_update;
        $success_message = 'Событие успешно обновлено';
    } else {
        $stmt = $pdo->prepare("INSERT INTO planting_events
            (user_id, type, plant_name, event_date, event_time, notes)
            VALUES (:user_id, :type, :plant_name, :event_date, :event_time, :notes)");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':type', $event_type);
        $stmt->bindParam(':plant_name', $plant_name);
        $stmt->bindParam(':event_date', $event_date);
        $stmt->bindParam(':event_time', $event_time, $event_time === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':notes', $event_notes);
        $stmt->execute();
        $final_event_id = $pdo->lastInsertId();
        if ($event_type === 'reminder' && $reminder_date) {
            $stmt = $pdo->prepare("INSERT INTO planting_reminders
                (event_id, reminder_date, reminder_time)
                VALUES (:event_id, :reminder_date, :reminder_time)");
            $stmt->bindParam(':event_id', $final_event_id, PDO::PARAM_INT);
            $stmt->bindParam(':reminder_date', $reminder_date);
            $stmt->bindParam(':reminder_time', $reminder_time, $reminder_time === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->execute();
        }
        $success_message = 'Событие успешно сохранено';
    }
    $event_type_map = [
        'planting' => 'device',
        'sprouting' => 'device',
        'watering' => 'device',
        'fertilizing' => 'device',
        'harvesting' => 'device',
        'reminder' => 'device',
        'other' => 'device'
    ];
    $log_event_type = $event_type_map[$event_type] ?? 'device';
    $log_description = ($event_id_to_update ? 'Обновлено событие' : 'Создано новое событие') . ': ' . $plant_name . ' на ' . $event_date;
    safeLogEvent($pdo, $user_id, $log_event_type, $log_description);
    $pdo->commit();
    sendJsonResponse(true, $success_message, ['event_id' => $final_event_id]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Ошибка при сохранении события: ' . $e->getMessage());
    sendJsonResponse(false, 'Произошла ошибка при сохранении события: ' . $e->getMessage());
} catch (Exception $e) { 
    error_log('Общая ошибка при сохранении события: ' . $e->getMessage());
    sendJsonResponse(false, 'Произошла ошибка: ' . $e->getMessage());
}
?> 