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
if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse($success, $message, $data = []) {
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
        exit;
    }
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
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Необходима авторизация');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Метод не поддерживается');
}
$input_data = json_decode(file_get_contents('php://input'), true);
$mode_id = isset($input_data['mode_id']) ? intval($input_data['mode_id']) : 0;
$user_id = $_SESSION['user_id'];
if (!$mode_id) {
    sendJsonResponse(false, 'Неверный ID пресета');
}
try {
    if (!isset($pdo)) {
        throw new Exception("Ошибка подключения к базе данных.");
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();
    $get_mode_sql = "SELECT name FROM preset_modes WHERE id = :mode_id AND user_id = :user_id";
    $get_mode_stmt = $pdo->prepare($get_mode_sql);
    $get_mode_stmt->bindParam(':mode_id', $mode_id, PDO::PARAM_INT);
    $get_mode_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $get_mode_stmt->execute();
    $mode_info = $get_mode_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$mode_info) {
        $pdo->rollBack();
        sendJsonResponse(false, 'Пресет не найден или не принадлежит пользователю');
    }
    $delete_sql = "DELETE FROM preset_modes WHERE id = :mode_id AND user_id = :user_id";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->bindParam(':mode_id', $mode_id, PDO::PARAM_INT);
    $delete_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $delete_stmt->execute();
    safeLogEvent($pdo, $user_id, 'settings', 'Удален пресет: ' . $mode_info['name']);
    $pdo->commit();
    sendJsonResponse(true, 'Пресет успешно удален');
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Ошибка при удалении пресета: ' . $e->getMessage());
    sendJsonResponse(false, 'Ошибка базы данных при удалении пресета: ' . $e->getMessage());
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Общая ошибка при удалении пресета: ' . $e->getMessage());
    sendJsonResponse(false, 'Произошла ошибка при удалении пресета: ' . $e->getMessage());
}
?> 