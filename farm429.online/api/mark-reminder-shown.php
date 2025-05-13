<?php
session_start();
require_once '../config/db_connect.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Необходима авторизация'
    ]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Метод не поддерживается'
    ]);
    exit;
}
$input_data = json_decode(file_get_contents('php://input'), true);
$reminder_id = isset($input_data['reminder_id']) ? intval($input_data['reminder_id']) : 0;
$user_id = $_SESSION['user_id'];
if ($reminder_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Неверный ID напоминания'
    ]);
    exit;
}
try {
    $db = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $check_sql = "SELECT r.id FROM planting_reminders r 
                 JOIN planting_events e ON r.event_id = e.id 
                 WHERE r.id = :reminder_id AND e.user_id = :user_id";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bindParam(':reminder_id', $reminder_id);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    if ($check_stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Напоминание не найдено или не принадлежит пользователю'
        ]);
        exit;
    }
    $update_sql = "UPDATE planting_reminders SET shown = true WHERE id = :reminder_id";
    $update_stmt = $db->prepare($update_sql);
    $update_stmt->bindParam(':reminder_id', $reminder_id);
    $update_stmt->execute();
    $stmtEventLog = $db->prepare("INSERT INTO event_log (user_id, event_type, event_description) VALUES (:user_id, 'device', :description)");
    $stmtEventLog->execute([
        ':user_id' => $user_id,
        ':description' => 'Напоминание отмечено как показанное (ID: ' . $reminder_id . ')'
    ]);
    echo json_encode([
        'success' => true,
        'message' => 'Напоминание отмечено как показанное'
    ]);
} catch (PDOException $e) {
    error_log('Ошибка при обновлении напоминания: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Произошла ошибка при обновлении напоминания: ' . $e->getMessage()
    ]);
}
?> 