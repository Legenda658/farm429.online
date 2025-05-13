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
} catch (Exception $e) {
    error_log("Ошибка при проверке структуры таблицы event_log: " . $e->getMessage());
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
if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse($success, $message, $data = []) {
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
        exit;
    }
}
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Необходима авторизация');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Метод не поддерживается');
}
$input_data = json_decode(file_get_contents('php://input'), true);
$event_id = isset($input_data['event_id']) ? intval($input_data['event_id']) : 0;
$user_id = $_SESSION['user_id'];
if ($event_id <= 0) {
    sendJsonResponse(false, 'Неверный ID события');
}
try {
    if (!isset($pdo)) {
        throw new Exception("Ошибка подключения к базе данных.");
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();
    $event_info_sql = "SELECT type, plant_name FROM planting_events WHERE id = :event_id AND user_id = :user_id";
    $event_info_stmt = $pdo->prepare($event_info_sql);
    $event_info_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $event_info_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $event_info_stmt->execute();
    $event_info = $event_info_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$event_info) {
        $pdo->rollBack();
        sendJsonResponse(false, 'Событие не найдено или не принадлежит пользователю');
    }
    $delete_reminders_sql = "DELETE FROM planting_reminders WHERE event_id = :event_id";
    $delete_reminders_stmt = $pdo->prepare($delete_reminders_sql);
    $delete_reminders_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $delete_reminders_stmt->execute();
    $delete_event_sql = "DELETE FROM planting_events WHERE id = :event_id";
    $delete_event_stmt = $pdo->prepare($delete_event_sql);
    $delete_event_stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $delete_event_stmt->execute();
    $log_description = "Удалено событие посадки: " . $event_info['plant_name'] . " (тип: " . $event_info['type'] . ")";
    safeLogEvent($pdo, $user_id, 'device', $log_description);
    $pdo->commit();
    sendJsonResponse(true, 'Событие успешно удалено');
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Ошибка при удалении события: ' . $e->getMessage());
    sendJsonResponse(false, 'Ошибка базы данных при удалении события: ' . $e->getMessage());
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Общая ошибка при удалении события: ' . $e->getMessage());
    sendJsonResponse(false, 'Произошла ошибка при удалении события: ' . $e->getMessage());
}
?> 