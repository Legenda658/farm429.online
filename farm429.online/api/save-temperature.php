<?php
require_once '../config/database.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
if (!isset($data['temperature']) || !isset($data['tolerance'])) {
    echo json_encode(['success' => false, 'message' => 'Не все данные предоставлены']);
    exit;
}
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO temperature_settings (user_id, temperature, tolerance) VALUES (:user_id, :temperature, :tolerance)");
    $stmt->execute([
        ':user_id' => $user_id,
        ':temperature' => $data['temperature'],
        ':tolerance' => $data['tolerance']
    ]);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM farm_status WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $exists = $stmt->fetchColumn() > 0;
    if ($exists) {
        $stmt = $pdo->prepare("UPDATE farm_status SET temperature = :temperature WHERE user_id = :user_id");
        $stmt->execute([
            ':user_id' => $user_id,
            ':temperature' => $data['temperature']
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO farm_status (user_id, temperature, created_at) VALUES (:user_id, :temperature, NOW())");
        $stmt->execute([
            ':user_id' => $user_id,
            ':temperature' => $data['temperature']
        ]);
    }
    $stmtEventLog = $pdo->prepare("INSERT INTO event_log (user_id, event_type, event_description) VALUES (:user_id, 'temperature', :description)");
    $stmtEventLog->execute([
        ':user_id' => $user_id,
        ':description' => 'Установлено новое значение температуры: ' . $data['temperature'] . '°C'
    ]);
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении: ' . $e->getMessage()]);
}