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
if (!isset($data['humidity']) || !isset($data['tolerance'])) {
    echo json_encode(['success' => false, 'message' => 'Не все данные предоставлены']);
    exit;
}
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO humidity_settings (user_id, humidity, tolerance) VALUES (:user_id, :humidity, :tolerance)");
    $stmt->execute([
        ':user_id' => $user_id,
        ':humidity' => $data['humidity'],
        ':tolerance' => $data['tolerance']
    ]);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM farm_status WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $exists = $stmt->fetchColumn() > 0;
    if ($exists) {
        $stmt = $pdo->prepare("UPDATE farm_status SET humidity = :humidity WHERE user_id = :user_id");
        $stmt->execute([
            ':user_id' => $user_id,
            ':humidity' => $data['humidity']
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO farm_status (user_id, humidity, created_at) VALUES (:user_id, :humidity, NOW())");
        $stmt->execute([
            ':user_id' => $user_id,
            ':humidity' => $data['humidity']
        ]);
    }
    $stmtEventLog = $pdo->prepare("INSERT INTO event_log (user_id, event_type, event_description) VALUES (:user_id, 'humidity', :description)");
    $stmtEventLog->execute([
        ':user_id' => $user_id,
        ':description' => 'Установлено новое значение влажности: ' . $data['humidity'] . '%'
    ]);
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении: ' . $e->getMessage()]);
}