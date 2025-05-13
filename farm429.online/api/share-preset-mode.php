<?php
session_start();
require_once '../config/database.php';
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
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
$modeId = $input['mode_id'] ?? 0;
$modeId = intval($modeId);
if (!$modeId) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID режима']);
    exit;
}
$user_id = $_SESSION['user_id'];
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT * FROM preset_modes WHERE id = ? AND user_id = ?");
    $stmt->execute([$modeId, $user_id]);
    $mode = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$mode) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Режим не найден или вам не принадлежит']);
        exit;
    }
    $tableInfo = $pdo->query("SHOW COLUMNS FROM preset_modes LIKE 'share_code'");
    if ($tableInfo->rowCount() === 0) {
        $pdo->exec("ALTER TABLE preset_modes ADD COLUMN share_code VARCHAR(8) NULL");
    }
    if (!empty($mode['share_code'])) {
        $pdo->commit();
        echo json_encode(['success' => true, 'share_code' => $mode['share_code']]);
        exit;
    }
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $shareCode = '';
    for ($i = 0; $i < 8; $i++) {
        $shareCode .= $characters[random_int(0, $charactersLength - 1)];
    }
    $codeExists = true;
    while ($codeExists) {
        $check = $pdo->prepare("SELECT id FROM preset_modes WHERE share_code = ?");
        $check->execute([$shareCode]);
        if (!$check->fetch()) {
            $codeExists = false;
        } else {
            $shareCode = '';
            for ($i = 0; $i < 8; $i++) {
                $shareCode .= $characters[random_int(0, $charactersLength - 1)];
            }
        }
    }
    $updateStmt = $pdo->prepare("UPDATE preset_modes SET share_code = ? WHERE id = ?");
    $updateStmt->execute([$shareCode, $modeId]);
    safeLogEvent($pdo, $user_id, 'system', 'Предоставлен доступ к режиму: ' . $mode['name']);
    $pdo->commit();
    echo json_encode(['success' => true, 'share_code' => $shareCode]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Ошибка при создании кода доступа: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных при создании кода доступа: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Общая ошибка: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Произошла ошибка при создании кода доступа: ' . $e->getMessage()]);
}