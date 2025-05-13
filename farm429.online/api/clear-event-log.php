<?php
require_once '../config/database.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}
$user_id = $_SESSION['user_id'];
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM event_log WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Журнал событий успешно очищен']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Ошибка при очистке журнала: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных при очистке журнала']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Общая ошибка при очистке журнала: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Произошла ошибка при очистке журнала']);
}